Param(
    [string]$Cluster = 'pqrs-cluster',
    [string]$Service = 'pqrs-service',
    [string]$TaskArn = '',
    [string]$Region = 'us-east-1',
    [int]$LogLines = 200,
    [int]$TimeoutSeconds = 30
)

Set-StrictMode -Version Latest

function Invoke-Aws {
    param(
        [string[]]$Arguments
    )
    Write-Host "[aws] $($Arguments -join ' ')"
    $out = & aws @Arguments 2>&1
    $ec = $LASTEXITCODE
    if ($ec -ne 0) {
        throw "AWS CLI failed (exit $ec): $out"
    }
    return $out
}

Write-Host "[validate-swagger] Cluster:$Cluster Service:$Service Region:$Region"
aws configure set region $Region | Out-Null

try {
    if (-not [string]::IsNullOrWhiteSpace($TaskArn)) {
        Write-Host "Using provided TaskArn: $TaskArn"
    } else {
        Write-Host 'Looking for running task for service...'
    $task = Invoke-Aws -Arguments @('ecs','list-tasks','--cluster',$Cluster,'--service-name',$Service,'--desired-status','RUNNING','--output','json') | ConvertFrom-Json
        if ($task.taskArns -and $task.taskArns.Count -gt 0) {
            $TaskArn = $task.taskArns[0]
            Write-Host "Found running task: $TaskArn"
        } else {
            Write-Host 'No running task found; trying stopped tasks (most recent)'
            $taskStopped = Invoke-Aws -Arguments @('ecs','list-tasks','--cluster',$Cluster,'--service-name',$Service,'--desired-status','STOPPED','--output','json') | ConvertFrom-Json
            if ($taskStopped.taskArns -and $taskStopped.taskArns.Count -gt 0) {
                $TaskArn = $taskStopped.taskArns[0]
                Write-Host "Found stopped task: $TaskArn"
            } else {
                throw 'No tasks found for the service.'
            }
        }
    }

    Write-Host '[validate-swagger] Describing task...'
    # Poll task status until it becomes RUNNING or STOPPED (or timeout)
    $pollSeconds = 60
    $pollInterval = 5
    $elapsed = 0
    $taskObj = $null
    while ($elapsed -lt $pollSeconds) {
        $descrRaw = Invoke-Aws -Arguments @('ecs','describe-tasks','--cluster',$Cluster,'--tasks',$TaskArn,'--output','json')
        $descr = $descrRaw | ConvertFrom-Json
        $taskObj = $descr.tasks[0]
        if ($taskObj -ne $null) {
            Write-Host "TaskArn: $($taskObj.taskArn)"
            Write-Host "LastStatus: $($taskObj.lastStatus)  DesiredStatus: $($taskObj.desiredStatus)  HealthStatus: $($taskObj.healthStatus)"
            # Stop if RUNNING or STOPPED
            if ($taskObj.lastStatus -eq 'RUNNING' -or $taskObj.lastStatus -eq 'STOPPED') { break }
        }
        Start-Sleep -Seconds $pollInterval
        $elapsed += $pollInterval
        Write-Host "[validate-swagger] Waiting for task to reach RUNNING/STOPPED (elapsed ${elapsed}s)..."
    }
    if (-not $taskObj) { throw 'Failed to parse task description' }
    # stoppedReason may not be present unless stopped
    if ($taskObj.PSObject.Properties.Match('stoppedReason').Count -gt 0 -and $taskObj.stoppedReason) { Write-Host "StoppedReason: $($taskObj.stoppedReason)" }

    Write-Host 'Containers:'
    foreach ($c in $taskObj.containers) {
        $contReason = $null
        if ($c.PSObject.Properties.Match('reason').Count -gt 0) { $contReason = $c.reason }
        if (-not $contReason -and $c.PSObject.Properties.Match('exitReason').Count -gt 0) { $contReason = $c.exitReason }
        $exitCodeDisplay = 'N/A'
        if ($c.PSObject.Properties.Match('exitCode').Count -gt 0) { $exitCodeDisplay = $c.exitCode }
        Write-Host " - Name: $($c.name) | ExitCode: $exitCodeDisplay | Reason: $contReason | LastStatus: $($c.lastStatus)"
    }

    # Fetch CloudWatch logs if configured
    $logGroup = '/ecs/pqrs'
    Write-Host "[validate-swagger] Gathering CloudWatch logs from group $logGroup (last $LogLines lines)"
    # Try to find streams that include the task id
    $taskId = ($TaskArn -split '/')[-1]
    $streamPrefix = "ecs"
        $streamsOut = Invoke-Aws -Arguments @('logs','describe-log-streams','--log-group-name',$logGroup,'--order-by','LastEventTime','--descending','--limit','50','--output','json')
    $streams = $streamsOut | ConvertFrom-Json
    $matching = @()
    foreach ($s in $streams.logStreams) {
        if ($s.logStreamName -match $taskId -or $s.logStreamName -match $streamPrefix) { $matching += $s.logStreamName }
    }
    $matching = $matching | Select-Object -Unique
    if ($matching.Count -eq 0) { Write-Host "No matching log streams found under $logGroup (returned $($streams.logStreams.Count) streams)." }
    foreach ($ls in $matching) {
        Write-Host "-- Showing last events from stream: $ls --"
        try {
            $eventsOut = Invoke-Aws -Arguments @('logs','get-log-events','--log-group-name',$logGroup,'--log-stream-name',$ls,'--limit',$LogLines,'--output','json')
            $events = $eventsOut | ConvertFrom-Json
            foreach ($e in $events.events) {
                $ts = (Get-Date -Date (Get-Date 1970-01-01).AddMilliseconds($e.timestamp)).ToString('s')
                $msg = $e.message -replace "\r?\n"," `n "
                Write-Host "[$ts] $msg"
            }
        } catch {
            Write-Host ("Failed to get events for stream {0}:" -f $ls) $_
        }
    }

    # Try to get public IP via network interface
    $eniId = $null
    foreach ($att in $taskObj.attachments) {
        foreach ($d in $att.details) {
            if ($d.name -eq 'networkInterfaceId') { $eniId = $d.value }
        }
    }
    $publicIp = $null
    $privateIp = $null
    if ($eniId) {
        Write-Host "Found ENI: $eniId -> describing..."
        $eniOut = Invoke-Aws -Arguments @('ec2','describe-network-interfaces','--network-interface-ids',$eniId,'--output','json')
        $eni = $eniOut | ConvertFrom-Json
        if ($eni.NetworkInterfaces.Count -gt 0) {
            $ni = $eni.NetworkInterfaces[0]
            $publicIp = $ni.Association.PublicIp
            $privateIp = $ni.PrivateIpAddress
            Write-Host "ENI PublicIp: $publicIp  PrivateIp: $privateIp"
        }
    } else {
        Write-Host 'No ENI id found in task attachments.'
    }

    # If no public IP, try to search ENIs in task's containers' networkInterfaces
    if (-not $publicIp -and $taskObj.containers) {
        foreach ($c in $taskObj.containers) {
            if ($c.networkInterfaces) {
                foreach ($ni in $c.networkInterfaces) {
                    if ($ni.privateIpv4Address) { $privateIp = $ni.privateIpv4Address }
                    if ($ni.association -and $ni.association.publicIpv4Address) { $publicIp = $ni.association.publicIpv4Address }
                }
            }
        }
    }

    if ($publicIp) { Write-Host "Public IP found: $publicIp" } else { Write-Host "No public IP found; using private IP if present: $privateIp" }

    # Endpoints to check
    $hostsToCheck = @()
    if ($publicIp) { $hostsToCheck += @{ Host = $publicIp; Port = 8000 } ;} 
    #if ($privateIp) { $hostsToCheck += @{ Host = $privateIp; Port = 8000 } }

    if ($hostsToCheck.Count -eq 0) {
        Write-Host 'No host IPs to test for HTTP connectivity. If your service is behind a load balancer, test the ALB DNS externally.'
    }

    foreach ($h in $hostsToCheck) {
        $targetHost = $h.Host
        $port = $h.Port
        Write-Host "Testing TCP connectivity to $($targetHost):$port"
        $tcp = Test-NetConnection -ComputerName $targetHost -Port $port -InformationLevel Detailed
        Write-Host ($tcp | Out-String)

        # Try swagger UI and JSON
        $urls = @(
            "http://$($targetHost):$port/api/documentation",
            "http://$($targetHost):$port/docs"
        )
        foreach ($u in $urls) {
            Write-Host "GET $u"
            try {
                $resp = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec $TimeoutSeconds -ErrorAction Stop
                Write-Host " - Status: $($resp.StatusCode)"
                $body = $resp.Content
                if ($body.Length -gt 500) { $body = $body.Substring(0,500) + '... [truncated]' }
                Write-Host $body
            } catch {
                Write-Host " - Request failed: $($_.Exception.Message)"
            }
        }
    }

    Write-Host '[validate-swagger] Done. Summary above: check exit codes, logs and whether the host is reachable on ports 8000/80.'
} catch {
    Write-Error "Validation failed: $_"
    exit 2
}

exit 0
