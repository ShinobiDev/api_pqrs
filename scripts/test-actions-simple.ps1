# Act Testing Helper Script - Simple Version
# Script to facilitate local GitHub Actions testing

param(
    [Parameter(Position=0)]
    [ValidateSet("test", "pr", "full", "list", "help")]
    [string]$Action = "help",
    
    [string]$Job = "",
    [switch]$DryRun,
    [switch]$ShowVerbose
)

function Write-Info($Text) {
    Write-Host "[INFO] $Text" -ForegroundColor Cyan
}

function Write-Success($Text) {
    Write-Host "[SUCCESS] $Text" -ForegroundColor Green
}

function Write-Warning($Text) {
    Write-Host "[WARNING] $Text" -ForegroundColor Yellow
}

function Write-Error($Text) {
    Write-Host "[ERROR] $Text" -ForegroundColor Red
}

function Show-Help {
    Write-Info "Act Testing Helper - API PQRS"
    Write-Host "================================="
    Write-Host ""
    Write-Host "USAGE:"
    Write-Host "  .\scripts\test-actions.ps1 <action> [options]"
    Write-Host ""
    Write-Host "ACTIONS:"
    Write-Host "  test    - Run local test workflow (recommended)"
    Write-Host "  pr      - Test PR validation workflow"
    Write-Host "  full    - Run full CI/CD workflow"
    Write-Host "  list    - List available workflows"
    Write-Host "  help    - Show this help"
    Write-Host ""
    Write-Host "OPTIONS:"
    Write-Host "  -Job <name>     - Run specific job only"
    Write-Host "  -DryRun         - Show command without executing"
    Write-Host "  -ShowVerbose    - Show verbose output"
    Write-Host ""
    Write-Host "EXAMPLES:"
    Write-Host "  .\scripts\test-actions.ps1 test"
    Write-Host "  .\scripts\test-actions.ps1 pr"
    Write-Host "  .\scripts\test-actions.ps1 full -Job setup"
}

function Test-Prerequisites {
    Write-Info "Checking prerequisites..."
    
    # Check act
    $actPath = "C:\tools\act\act.exe"
    if (Test-Path $actPath) {
        $actVersion = & $actPath --version 2>$null
        Write-Success "act installed: $actVersion"
        $global:ActPath = $actPath
    } else {
        Write-Error "act not found at $actPath. Please install act first."
        return $false
    }
    
    # Check Docker
    try {
        & docker info 2>$null | Out-Null
        Write-Success "Docker is running"
    } catch {
        Write-Error "Docker not running. Please start Docker Desktop."
        return $false
    }
    
    # Check .env.act
    if (Test-Path ".env.act") {
        Write-Success ".env.act configuration found"
    } else {
        Write-Warning ".env.act not found. Creating from example..."
        Copy-Item ".env.act.example" ".env.act"
        Write-Warning "Please edit .env.act with your values."
    }
    
    return $true
}

function Invoke-ActTest($Workflow, $EventType, $JobName) {
    $actCmd = if ($global:ActPath) { $global:ActPath } else { "C:\tools\act\act.exe" }
    $cmd = "$actCmd -W .github/workflows/$Workflow -e .github/act-events/$EventType.json"
    
    if ($JobName) {
        $cmd += " -j $JobName"
    }
    
    if ($ShowVerbose) {
        $cmd += " --verbose"
    }
    
    if ($DryRun) {
        $cmd += " --dry-run"
    }
    
    Write-Info "Running: $cmd"
    
    if ($DryRun) {
        Write-Warning "DRY RUN - Command shown above but not executed"
        return
    }
    
    try {
        Invoke-Expression $cmd
        Write-Success "Command completed successfully!"
    } catch {
        Write-Error "Command failed: $_"
    }
}

# Main script logic
Write-Info "GitHub Actions Local Testing"
Write-Host "====================================="

switch ($Action) {
    "help" { 
        Show-Help 
        exit 0
    }
    
    "list" {
        Write-Info "Available Workflows:"
        Get-ChildItem ".github/workflows/*.yml" | ForEach-Object {
            Write-Host "  $($_.BaseName)"
        }
        
        Write-Info "Available Events:"
        Get-ChildItem ".github/act-events/*.json" | ForEach-Object {
            Write-Host "  $($_.BaseName)"
        }
        exit 0
    }
    
    "test" {
        if (-not (Test-Prerequisites)) { exit 1 }
        Write-Info "Running Local Test Workflow..."
        Invoke-ActTest "local-test.yml" "push" $Job
    }
    
    "pr" {
        if (-not (Test-Prerequisites)) { exit 1 }
        Write-Info "Running PR Validation Workflow..."
        Invoke-ActTest "pr-validation.yml" "pull_request" $Job
    }
    
    "full" {
        if (-not (Test-Prerequisites)) { exit 1 }
        Write-Info "Running Full CI/CD Workflow..."
        Write-Warning "This requires AWS configuration and takes longer"
        Invoke-ActTest "ci-cd.yml" "push" $Job
    }
    
    default {
        Write-Error "Unknown action: $Action"
        Show-Help
        exit 1
    }
}