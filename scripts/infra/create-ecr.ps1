Param(
    [string]$RepoName = "pqrs-api",
    [string]$Region = "us-east-1"
)

Set-StrictMode -Version Latest

Write-Host "[create-ecr] Using repo: $RepoName in region $Region"

# Ensure AWS region
aws configure set region $Region | Out-Null

# Try describe
$describe = aws ecr describe-repositories --repository-names $RepoName --region $Region --output json 2>$null
if ($LASTEXITCODE -eq 0 -and $describe) {
    $descObj = $describe | ConvertFrom-Json
    $repoUri = $descObj.repositories[0].repositoryUri
    Write-Host $repoUri
    exit 0
}

Write-Host "[create-ecr] Repository not found. Creating $RepoName..."
$create = aws ecr create-repository --repository-name $RepoName --region $Region --output json 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to create ECR repository: $create"
    exit 1
}
$createObj = $create | ConvertFrom-Json
$repoUri = $createObj.repository.repositoryUri
Write-Host $repoUri
exit 0
