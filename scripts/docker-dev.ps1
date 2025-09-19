# ====================================
# Script para gestionar entorno de desarrollo Docker
# ====================================

param(
    [Parameter(Mandatory=$false)]
    [string]$Command = "help"
)

# Configuración
$COMPOSE_FILE = "docker-compose.dev.yml"
$ENV_FILE = ".env.docker.dev"

# Colores para output
$RED = "`e[0;31m"
$GREEN = "`e[0;32m"
$YELLOW = "`e[1;33m"
$BLUE = "`e[0;34m"
$NC = "`e[0m" # No Color

# Funciones de utilidad
function Write-Info {
    param([string]$Message)
    Write-Host "${BLUE}[INFO]${NC} $Message"
}

function Write-Success {
    param([string]$Message)
    Write-Host "${GREEN}[SUCCESS]${NC} $Message"
}

function Write-Warning {
    param([string]$Message)
    Write-Host "${YELLOW}[WARNING]${NC} $Message"
}

function Write-Error {
    param([string]$Message)
    Write-Host "${RED}[ERROR]${NC} $Message"
}

# Verificar que Docker está ejecutándose
function Test-Docker {
    try {
        $null = docker info 2>$null
        return $true
    }
    catch {
        return $false
    }
}

# Función para inicializar el proyecto
function Initialize-Project {
    Write-Info "Inicializando proyecto PQRS API para desarrollo..."

    # Copiar archivo de entorno si no existe
    if (!(Test-Path .env)) {
        if (Test-Path $ENV_FILE) {
            Copy-Item $ENV_FILE .env
            Write-Success "Archivo .env creado desde $ENV_FILE"
        }
        else {
            Write-Warning "No se encontró $ENV_FILE, usando .env.example"
            Copy-Item .env.example .env
        }
    }

    # Crear red Docker si no existe
    try {
        docker network create pqrs-network 2>$null | Out-Null
    }
    catch {
        # La red ya existe, continuar
    }

    Write-Success "Inicialización completada"
}

# Función para construir las imágenes
function Build-Images {
    Write-Info "Construyendo imágenes Docker..."
    docker-compose -f $COMPOSE_FILE build --no-cache
    Write-Success "Imágenes construidas exitosamente"
}

# Función para iniciar los servicios
function Start-Services {
    Write-Info "Iniciando servicios de desarrollo..."
    docker-compose -f $COMPOSE_FILE up -d

    # Esperar que los servicios estén listos
    Write-Info "Esperando que los servicios estén listos..."
    Start-Sleep -Seconds 10

    # Ejecutar migraciones
    Invoke-Migrations

    Write-Success "Servicios iniciados. Accede a:"
    Write-Host "  - API: http://localhost:8000"
    Write-Host "  - MailHog: http://localhost:8025"
    Write-Host "  - Base de datos: localhost:3306"
}

# Función para parar los servicios
function Stop-Services {
    Write-Info "Deteniendo servicios..."
    docker-compose -f $COMPOSE_FILE down
    Write-Success "Servicios detenidos"
}

# Función para ejecutar migraciones
function Invoke-Migrations {
    Write-Info "Ejecutando migraciones..."
    docker-compose -f $COMPOSE_FILE exec app php artisan migrate --force
    Write-Success "Migraciones ejecutadas"
}

# Función para ejecutar seeders
function Invoke-Seeders {
    Write-Info "Ejecutando seeders..."
    docker-compose -f $COMPOSE_FILE exec app php artisan db:seed --force
    Write-Success "Seeders ejecutados"
}

# Función para acceder al contenedor
function Enter-Container {
    Write-Info "Accediendo al contenedor de la aplicación..."
    docker-compose -f $COMPOSE_FILE exec app bash
}

# Función para ver logs
function Show-Logs {
    param([string]$Service = "app")
    Write-Info "Mostrando logs del servicio: $Service"
    docker-compose -f $COMPOSE_FILE logs -f $Service
}

# Función para ejecutar tests
function Run-Tests {
    Write-Info "Ejecutando tests..."
    docker-compose -f $COMPOSE_FILE exec app php artisan test
}

# Función para limpiar todo
function Clean-All {
    Write-Warning "¿Estás seguro de que quieres limpiar todos los contenedores y volúmenes? (y/N)"
    $response = Read-Host
    if ($response -match "^[Yy]$") {
        Write-Info "Limpiando contenedores, imágenes y volúmenes..."
        docker-compose -f $COMPOSE_FILE down -v --rmi all
        docker system prune -f
        Write-Success "Limpieza completada"
    }
    else {
        Write-Info "Operación cancelada"
    }
}

# Función para mostrar ayuda
function Show-Help {
    Write-Host "Script de gestión para PQRS API - Desarrollo con Docker"
    Write-Host ""
    Write-Host "Uso: .\docker-dev.ps1 {comando}"
    Write-Host ""
    Write-Host "Comandos disponibles:"
    Write-Host "  init      - Inicializar proyecto (crear .env, red Docker)"
    Write-Host "  build     - Construir imágenes Docker"
    Write-Host "  up        - Iniciar todos los servicios"
    Write-Host "  down      - Detener todos los servicios"
    Write-Host "  migrate   - Ejecutar migraciones de base de datos"
    Write-Host "  seed      - Ejecutar seeders"
    Write-Host "  shell     - Acceder al contenedor de la aplicación"
    Write-Host "  logs      - Ver logs (logs [servicio])"
    Write-Host "  test      - Ejecutar tests"
    Write-Host "  clean     - Limpiar contenedores y volúmenes"
    Write-Host "  help      - Mostrar esta ayuda"
    Write-Host ""
    Write-Host "Ejemplos:"
    Write-Host "  .\docker-dev.ps1 init"
    Write-Host "  .\docker-dev.ps1 build"
    Write-Host "  .\docker-dev.ps1 up"
    Write-Host "  .\docker-dev.ps1 logs app"
    Write-Host "  .\docker-dev.ps1 shell"
}

# Verificar Docker antes de ejecutar cualquier comando
if (!(Test-Docker)) {
    Write-Error "Docker no está ejecutándose. Por favor, inicia Docker Desktop."
    exit 1
}

# Procesamiento de comandos
switch ($Command) {
    "init" {
        Initialize-Project
    }
    "build" {
        Build-Images
    }
    "up" {
        Start-Services
    }
    "down" {
        Stop-Services
    }
    "migrate" {
        Invoke-Migrations
    }
    "seed" {
        Invoke-Seeders
    }
    "shell" {
        Enter-Container
    }
    "logs" {
        Show-Logs $args[0]
    }
    "test" {
        Run-Tests
    }
    "clean" {
        Clean-All
    }
    "help" {
        Show-Help
    }
    default {
        Show-Help
    }
}