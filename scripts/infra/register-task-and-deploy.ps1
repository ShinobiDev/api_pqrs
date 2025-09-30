Param(
    [string]$Cluster = 'pqrs-cluster',
    [string]$Service = 'pqrs-service',
    [string]$Image,
    [string]$TaskRoleArn = '',
    [string]$ExecutionRoleArn = '',
    [string]$Region = 'us-east-1',
    [string]$TaskDefTemplate = './scripts/infra/ecs-task-def.json.tpl',
    [string]$SubnetIds = '',
    [string]$SecurityGroupIds = '',
    [int]$DesiredCount = 1
)

Set-StrictMode -Version Latest

if (-not $Image) { Write-Error 'Image is required (ECR_URI:TAG)'; exit 1 }

Write-Host "[register-task] Using cluster:$Cluster service:$Service image:$Image"
aws configure set region $Region | Out-Null

Write-Host '[register-task] Building task definition object...'

$containerDef = @{
    name = 'pqrs-app'
    image = $Image
    essential = $true
    portMappings = @(@{ containerPort = 8000; protocol = 'tcp' })
    environment = @()
    logConfiguration = @{
        logDriver = 'awslogs'
        options = @{
            'awslogs-group' = '/ecs/pqrs'
            'awslogs-region' = $Region
            'awslogs-stream-prefix' = 'ecs'
        }
    }
}

$taskObj = @{
    family = 'pqrs-task'
    networkMode = 'awsvpc'
    requiresCompatibilities = @('FARGATE')
    cpu = '512'
    memory = '1024'
    executionRoleArn = if ($ExecutionRoleArn -ne '') { $ExecutionRoleArn } else { $null }
    taskRoleArn = if ($TaskRoleArn -ne '') { $TaskRoleArn } else { $null }
    containerDefinitions = @($containerDef)
}


$taskJson = $taskObj | ConvertTo-Json -Depth 10

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$tmp = Join-Path $scriptDir 'ecs-task-def.tmp.json'
# use UTF8 without BOM to avoid aws cli file:// parsing issues
$utf8NoBOM = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($tmp, $taskJson, $utf8NoBOM)

# debug copy
$debugPath = Join-Path $scriptDir 'ecs-task-def.debug.json'
[System.IO.File]::WriteAllText($debugPath, $taskJson, $utf8NoBOM)

Write-Host '[register-task] Generated task JSON (first 1000 chars):'
Write-Host ($taskJson.Substring(0, [Math]::Min(1000, $taskJson.Length)))

Write-Host '[register-task] Writing container definitions to file (JSON array)...'
$containersFile = Join-Path $scriptDir 'container-defs.json'
# Serialize each container definition individually and join to force a top-level JSON array
$containerJsonItems = $taskObj.containerDefinitions | ForEach-Object { ($_ | ConvertTo-Json -Depth 10 -Compress) }
$containersJson = "[" + ($containerJsonItems -join ',') + "]"
[System.IO.File]::WriteAllText($containersFile, $containersJson, $utf8NoBOM)

function Get-RoleNameFromArn($arn) {
    if (-not $arn) { return $null }
    $parts = $arn -split '/'
    return $parts[-1]
}

if ($ExecutionRoleArn -ne '') {
    $execRoleName = Get-RoleNameFromArn $ExecutionRoleArn
    Write-Host "[register-task] Validating execution role exists: $execRoleName"
    $roleCheck = aws iam get-role --role-name $execRoleName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Execution role '$ExecutionRoleArn' not found or not accessible. AWS returned: $roleCheck"
        exit 1
    }
}

if ($TaskRoleArn -ne '') {
    $taskRoleName = Get-RoleNameFromArn $TaskRoleArn
    Write-Host "[register-task] Validating task role exists: $taskRoleName"
    $roleCheck = aws iam get-role --role-name $taskRoleName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Task role '$TaskRoleArn' not found or not accessible. AWS returned: $roleCheck"
        exit 1
    }
}

# Ensure CloudWatch Logs groups referenced by container definitions exist
Write-Host '[register-task] Ensuring CloudWatch log groups exist (so ECS can create streams)...'
$logGroups = @()
foreach ($cd in $taskObj.containerDefinitions) {
    if ($null -ne $cd.logConfiguration -and $cd.logConfiguration.logDriver -eq 'awslogs') {
        $opts = $cd.logConfiguration.options
        if ($opts -and $opts.'awslogs-group') { $logGroups += $opts.'awslogs-group' }
    }
}

# If no execution role ARN provided, try to create or reuse a role for Fargate task execution
if ([string]::IsNullOrWhiteSpace($ExecutionRoleArn)) {
        $generatedRoleName = "ecsTaskExecutionRole-pqrs"
        Write-Host "[register-task] No execution role provided. Attempting to find or create role: $generatedRoleName"
        $getRole = aws iam get-role --role-name $generatedRoleName 2>&1
        if ($LASTEXITCODE -ne 0) {
                Write-Host "[register-task] Role not found; creating IAM role $generatedRoleName"
                $trustPolicy = @'
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Principal": {"Service": "ecs-tasks.amazonaws.com"},
            "Action": "sts:AssumeRole"
        }
    ]
}
'@
                $tmpPolicy = Join-Path $scriptDir 'trust-policy.json'
                [System.IO.File]::WriteAllText($tmpPolicy, $trustPolicy, $utf8NoBOM)
                $createRole = aws iam create-role --role-name $generatedRoleName --assume-role-policy-document file://$tmpPolicy 2>&1
                if ($LASTEXITCODE -ne 0) {
                        Write-Error "Failed to create execution role: $createRole"
                        Remove-Item -Path $tmpPolicy -ErrorAction SilentlyContinue
                        exit 1
                }
                Remove-Item -Path $tmpPolicy -ErrorAction SilentlyContinue
                Write-Host "[register-task] Attaching AmazonECSTaskExecutionRolePolicy to $generatedRoleName"
                $attach = aws iam attach-role-policy --role-name $generatedRoleName --policy-arn arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy 2>&1
                if ($LASTEXITCODE -ne 0) { Write-Error "Failed to attach execution policy: $attach"; exit 1 }
                # wait a moment for eventual IAM eventual consistency
                Start-Sleep -Seconds 3
                $roleInfo = aws iam get-role --role-name $generatedRoleName --output json 2>&1
                if ($LASTEXITCODE -ne 0) { Write-Error "Failed to get created role info: $roleInfo"; exit 1 }
                $roleObj = $roleInfo | ConvertFrom-Json
                $ExecutionRoleArn = $roleObj.Role.Arn
                Write-Host "[register-task] Created execution role ARN: $ExecutionRoleArn"
        } else {
                Write-Host "[register-task] Found existing role $generatedRoleName; using it"
                $roleInfo = aws iam get-role --role-name $generatedRoleName --output json 2>&1
                $roleObj = $roleInfo | ConvertFrom-Json
                $ExecutionRoleArn = $roleObj.Role.Arn
                Write-Host "[register-task] Using execution role ARN: $ExecutionRoleArn"
        }
}
$logGroups = $logGroups | Sort-Object -Unique
foreach ($lg in $logGroups) {
    if ([string]::IsNullOrWhiteSpace($lg)) { continue }
    Write-Host "[register-task] Ensuring log group: $lg"
    $createLg = aws logs create-log-group --log-group-name $lg 2>&1
    if ($LASTEXITCODE -ne 0) {
        # If it already exists that's fine; otherwise verify by describing
        if ($createLg -match 'ResourceAlreadyExistsException') {
            Write-Host "[register-task] Log group $lg already exists"
        } else {
            Write-Host "[register-task] create-log-group returned: $createLg"
            $desc = aws logs describe-log-groups --log-group-name-prefix $lg --output text 2>&1
            if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($desc) -or ($desc -notmatch [regex]::Escape($lg))) {
                Write-Error "Failed to ensure log group $lg exists: $createLg"
                exit 1
            }
            Write-Host "[register-task] Verified log group exists: $lg"
        }
    } else {
        Write-Host "[register-task] Created log group $lg"
        # set a sane retention (optional)
        aws logs put-retention-policy --log-group-name $lg --retention-in-days 14 | Out-Null
    }
}

 $cmd = @(
    'ecs', 'register-task-definition',
    '--family', $taskObj.family,
    '--network-mode', $taskObj.networkMode,
    '--requires-compatibilities', ($taskObj.requiresCompatibilities -join ' '),
    '--cpu', $taskObj.cpu,
    '--memory', $taskObj.memory,
    '--container-definitions', ("file://" + ($containersFile -replace '\\','/')),
    '--output', 'json'
)

if ($ExecutionRoleArn -ne '') { $cmd += @('--execution-role-arn', $ExecutionRoleArn) }
if ($TaskRoleArn -ne '') { $cmd += @('--task-role-arn', $TaskRoleArn) }

Write-Host "[register-task] Running: aws $($cmd -join ' ')"
$reg = aws @cmd 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to register task definition: $reg"
    Remove-Item -Path $tmp -ErrorAction SilentlyContinue
    Remove-Item -Path $containersFile -ErrorAction SilentlyContinue
    exit 1
}

# cleanup
Remove-Item -Path $tmp -ErrorAction SilentlyContinue
Remove-Item -Path $containersFile -ErrorAction SilentlyContinue

$regObj = $reg | ConvertFrom-Json
$taskDefArn = $regObj.taskDefinition.taskDefinitionArn
Write-Host "[register-task] Registered: $taskDefArn"

Write-Host '[register-task] Updating service to use new task definition...'

Write-Host "[register-task] Checking cluster $Cluster exists..."
$clusterInfo = aws ecs describe-clusters --clusters $Cluster --query 'clusters[0].status' --output text 2>&1
if ($LASTEXITCODE -ne 0 -or $clusterInfo -eq 'None') {
    Write-Host "[register-task] Cluster '$Cluster' not found; creating cluster..."
    $createCluster = aws ecs create-cluster --cluster-name $Cluster 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Error "Failed to create cluster: $createCluster"; exit 1 }
    Write-Host '[register-task] Cluster created'
} else {
    Write-Host "[register-task] Cluster exists (status: $clusterInfo)"
}

Write-Host "[register-task] Checking service $Service in cluster $Cluster..."
$svc = aws ecs describe-services --cluster $Cluster --services $Service --query 'services[0].status' --output text 2>&1
if ($LASTEXITCODE -ne 0 -or $svc -eq 'None') {
    Write-Host "[register-task] Service '$Service' not found in cluster '$Cluster'."
    if ($SubnetIds -ne '' -and $SecurityGroupIds -ne '') {
        $subnetArray = $SubnetIds -split ',' | ForEach-Object { $_.Trim() }
        $sgArray = $SecurityGroupIds -split ',' | ForEach-Object { $_.Trim() }
    } else {
        Write-Host '[register-task] No SubnetIds/SecurityGroupIds provided — attempting to auto-discover default VPC and subnets'
        $vpcId = aws ec2 describe-vpcs --filters Name=isDefault,Values=true --query 'Vpcs[0].VpcId' --output text 2>&1
        if ($LASTEXITCODE -ne 0 -or $vpcId -eq 'None' -or [string]::IsNullOrWhiteSpace($vpcId)) {
            Write-Host '[register-task] No default VPC found; picking first VPC'
            $vpcId = aws ec2 describe-vpcs --query 'Vpcs[0].VpcId' --output text 2>&1
            if ($LASTEXITCODE -ne 0 -or $vpcId -eq 'None' -or [string]::IsNullOrWhiteSpace($vpcId)) {
                Write-Error 'Unable to determine a VPC to use for creating the service. Provide -SubnetIds and -SecurityGroupIds manually.'; exit 1
            }
        }
        Write-Host "[register-task] Using VPC: $vpcId"

    $subs = aws ec2 describe-subnets --filters Name=vpc-id,Values=$vpcId --query 'Subnets[].SubnetId' --output text 2>&1
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($subs)) { Write-Error "Failed to list subnets for VPC $vpcId"; exit 1 }
    $subnetArray = @($subs -split '\s+' | Where-Object { $_ -ne '' })
    # limit to first two subnets if more returned
    if (@($subnetArray).Count -gt 2) { $subnetArray = $subnetArray[0..1] }
    Write-Host "[register-task] Selected subnets: $($subnetArray -join ',')"

        $sg = aws ec2 describe-security-groups --filters "Name=vpc-id,Values=$vpcId" "Name=group-name,Values=default" --query 'SecurityGroups[0].GroupId' --output text 2>&1
        if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($sg) -or $sg -eq 'None') {
            Write-Host '[register-task] Default security group not found — picking first security group in VPC'
            $sg = aws ec2 describe-security-groups --filters "Name=vpc-id,Values=$vpcId" --query 'SecurityGroups[0].GroupId' --output text 2>&1
            if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($sg) -or $sg -eq 'None') { Write-Error "Unable to determine a security group for VPC $vpcId"; exit 1 }
        }
        $sgArray = @($sg)
        Write-Host "[register-task] Using security group: $sg"
    }

    Write-Host "[register-task] Creating service '$Service' with FARGATE networking..."
    $netObj = @{ awsvpcConfiguration = @{ subnets = $subnetArray; securityGroups = $sgArray; assignPublicIp = 'ENABLED' } }
    $netJson = $netObj | ConvertTo-Json -Compress -Depth 5
    $netFile = Join-Path $scriptDir 'network-config.json'
    [System.IO.File]::WriteAllText($netFile, $netJson, $utf8NoBOM)

    $createSvcCmd = @(
        'ecs','create-service',
        '--cluster', $Cluster,
        '--service-name', $Service,
        '--task-definition', $taskDefArn,
        '--desired-count', $DesiredCount.ToString(),
        '--launch-type', 'FARGATE',
        '--network-configuration', ("file://" + ($netFile -replace '\\','/')),
        '--output', 'json'
    )
    Write-Host "[register-task] Running: aws $($createSvcCmd -join ' ')"
    $createSvc = aws @createSvcCmd 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Error "Failed to create service: $createSvc"; exit 1 }
    Write-Host "[register-task] Service created and deployment started"
    exit 0
} else {
    Write-Host "[register-task] Service exists (status: $svc). Updating to new task definition..."
    $upd = aws ecs update-service --cluster $Cluster --service $Service --task-definition $taskDefArn --force-new-deployment 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Error "Failed to update service: $upd"; exit 1 }
    Write-Host "[register-task] Service update initiated"
}

exit 0
