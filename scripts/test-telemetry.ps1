Write-Host "Verificando instrumentacion OpenTelemetry..." -ForegroundColor Green

$baseUrl = "http://127.0.0.1:8000"

try {
    Write-Host "1) Probando /api/metrics (primeras 20 lineas)" -ForegroundColor Yellow
    $response = Invoke-WebRequest -Uri "$baseUrl/api/metrics" -UseBasicParsing
    Write-Host "Status:" $response.StatusCode
    ($response.Content -split "`n" | Select-Object -First 20) -join "`n"
    
    Write-Host "2) Generando trafico: /api/health x 5" -ForegroundColor Yellow
    for ($i = 1; $i -le 5; $i++) {
        Invoke-WebRequest -Uri "$baseUrl/api/health" -UseBasicParsing | Out-Null
        Write-Host "Request $i completed"
    }
    
    Write-Host "3) Verificando metricas http_requests_total despues del trafico" -ForegroundColor Yellow
    $metricsResponse = Invoke-WebRequest -Uri "$baseUrl/api/metrics" -UseBasicParsing
    $httpMetrics = $metricsResponse.Content -split "`n" | Where-Object { $_ -match "http_requests_total" }
    $httpMetrics | ForEach-Object { Write-Host $_ }
    
    Write-Host "4) Probando /api/health" -ForegroundColor Yellow
    $healthResponse = Invoke-WebRequest -Uri "$baseUrl/api/health" -UseBasicParsing
    Write-Host "Health Status:" $healthResponse.StatusCode
    Write-Host "Health Content:" $healthResponse.Content
    
    Write-Host "Verificacion completada!" -ForegroundColor Green
} catch {
    Write-Host "Error:" $_.Exception.Message -ForegroundColor Red
}