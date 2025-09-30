Param(
    [string]$AlbName = "pqrs-alb",
    [string]$VpcId,
    [string[]]$SubnetIds,
    [string]$Region = "us-east-1"
)

Set-StrictMode -Version Latest
if (-not $VpcId -or -not $SubnetIds) { Write-Error "VpcId and SubnetIds are required to create ALB"; exit 1 }

Write-Host "[create-alb] Creating ALB $AlbName in VPC $VpcId"
aws configure set region $Region | Out-Null

$subnets = $SubnetIds -join ","
$create = aws elbv2 create-load-balancer --name $AlbName --subnets $subnets --scheme internet-facing --type application --output json 2>&1
if ($LASTEXITCODE -ne 0) { Write-Error "Failed to create ALB: $create"; exit 1 }
$createObj = $create | ConvertFrom-Json
$dns = $createObj.LoadBalancers[0].DNSName
Write-Host "[create-alb] Created ALB DNS: $dns"
Write-Host $dns
exit 0
