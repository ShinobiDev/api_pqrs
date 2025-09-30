Param(
    [string]$EcrUri,
    [string]$Tag = "latest",
    [string]$Dockerfile = "Dockerfile",
    [string]$Context = "."
)

Set-StrictMode -Version Latest

if (-not $EcrUri) {
    Write-Error "EcrUri is required"
    exit 1
}

$registry = $EcrUri.Split('/')[0]
Write-Host "[build-and-push] Login to ECR registry: $registry"
aws ecr get-login-password | docker login --username AWS --password-stdin $registry
if ($LASTEXITCODE -ne 0) { Write-Error "Docker login failed"; exit 1 }

$image = "${EcrUri}:${Tag}"
Write-Host "[build-and-push] Building image $image"
docker build -t $image -f $Dockerfile $Context
if ($LASTEXITCODE -ne 0) { Write-Error "Docker build failed"; exit 1 }

Write-Host "[build-and-push] Pushing image $image"
docker push $image
if ($LASTEXITCODE -ne 0) { Write-Error "Docker push failed"; exit 1 }

Write-Host $image
exit 0
