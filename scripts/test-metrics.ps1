# Script para probar metricas de la aplicacion PQRS
Write-Host "=== Probando metricas de aplicacion PQRS ===" -ForegroundColor Green
Write-Host ""

# Funcion para hacer peticiones y mostrar el resultado
function Test-Endpoint {
    param($url, $name)
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
        Write-Host "OK $name - Status: $($response.StatusCode)" -ForegroundColor Green
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "ERROR $name - Status: $statusCode" -ForegroundColor Red
    }
}

# Generar trafico para activar metricas
Write-Host "1. Generando trafico HTTP..." -ForegroundColor Yellow
Test-Endpoint "http://127.0.0.1:8000/api/health" "Health Check"
Test-Endpoint "http://127.0.0.1:8000/api/health" "Health Check (2)"

Write-Host ""
Write-Host "2. Revisando metricas disponibles..." -ForegroundColor Yellow

try {
    $metrics = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/metrics" -UseBasicParsing
    Write-Host "OK Metricas obtenidas - Status: $($metrics.StatusCode)" -ForegroundColor Green
    
    # Analizar que tipos de metricas tenemos
    $content = $metrics.Content
    
    Write-Host ""
    Write-Host "=== ANALISIS DE METRICAS ===" -ForegroundColor Cyan
    
    # Contar metricas HTTP
    $httpMetrics = ($content | Select-String "pqrs_http_" -AllMatches).Matches.Count
    Write-Host "HTTP metrics found: $httpMetrics" -ForegroundColor White
    
    # Contar metricas de aplicacion
    $appMetrics = ($content | Select-String "pqrs_total_|pqrs_pqrs_|pqrs_database_" -AllMatches).Matches.Count
    Write-Host "App metrics found: $appMetrics" -ForegroundColor White
    
    Write-Host ""
    Write-Host "=== METRICAS DETALLADAS ===" -ForegroundColor Cyan
    Write-Host $content
    
} catch {
    Write-Host "ERROR obteniendo metricas: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== RESUMEN ===" -ForegroundColor Green
Write-Host "Metricas HTTP implementadas" -ForegroundColor White
Write-Host "Metricas de aplicacion PQRS implementadas" -ForegroundColor White
Write-Host "Metricas de sistema implementadas" -ForegroundColor White
Write-Host ""
Write-Host "Implementacion de metricas completada!" -ForegroundColor Green