# 🧪 Guía Completa: Testing Local de GitHub Actions

Este documento te guía paso a paso para probar localmente las GitHub Actions del proyecto API PQRS usando `act`.

## 📋 Resumen Rápido

```bash
# Instalar act (Windows)
# Descargar desde: https://github.com/nektos/act/releases
# Extraer en C:\tools\act\

# Configurar y probar
cd E:\MohanSoft\Dev\2025\pqrs\api_pqrs
cp .env.act.example .env.act
.\scripts\test-actions-simple.ps1 test
```

## 🛠️ Instalación Completa

### 1. Instalar act

**Método 1: Descarga Manual (Recomendado para Windows)**
```powershell
# Crear directorio
mkdir C:\tools\act -Force
cd C:\tools\act

# Descargar última versión
Invoke-WebRequest -Uri "https://github.com/nektos/act/releases/latest/download/act_Windows_x86_64.zip" -OutFile "act.zip"

# Extraer
Expand-Archive -Path "act.zip" -DestinationPath "." -Force

# Verificar instalación
C:\tools\act\act.exe --version
```

**Método 2: Chocolatey (si tienes chocolatey)**
```powershell
choco install act-cli
```

**Método 3: winget (Windows 11)**
```powershell
winget install nektos.act
```

### 2. Verificar Docker

```powershell
# Asegurar que Docker Desktop esté corriendo
docker --version
docker info
```

### 3. Configurar el Proyecto

```powershell
# Ir al directorio del proyecto
cd E:\MohanSoft\Dev\2025\pqrs\api_pqrs

# Copiar archivo de configuración
cp .env.act.example .env.act

# Editar .env.act con tus valores (opcional para testing básico)
notepad .env.act
```

## 🚀 Ejecutar Tests

### Scripts Helper Disponibles

```powershell
# Ver ayuda
.\scripts\test-actions-simple.ps1 help

# Listar workflows disponibles
.\scripts\test-actions-simple.ps1 list

# Test básico (recomendado para primera ejecución)
.\scripts\test-actions-simple.ps1 test

# Test de PR validation
.\scripts\test-actions-simple.ps1 pr

# Test completo de CI/CD (requiere configuración AWS)
.\scripts\test-actions-simple.ps1 full

# Dry run (ver qué se ejecutaría sin ejecutar)
.\scripts\test-actions-simple.ps1 test -DryRun

# Ejecutar solo un job específico
.\scripts\test-actions-simple.ps1 test -Job "local-test"

# Verbose output
.\scripts\test-actions-simple.ps1 test -ShowVerbose
```

### Comandos act Directos

```powershell
# Comando básico
C:\tools\act\act.exe -W .github/workflows/local-test.yml

# Con archivo de evento específico
C:\tools\act\act.exe -W .github/workflows/local-test.yml -e .github/act-events/push.json

# Listar todos los jobs sin ejecutar
C:\tools\act\act.exe -W .github/workflows/ci-cd.yml --list

# Ejecutar job específico
C:\tools\act\act.exe -W .github/workflows/ci-cd.yml -j setup

# Dry run
C:\tools\act\act.exe -W .github/workflows/local-test.yml --dry-run
```

## 📁 Estructura de Archivos Creados

```
.github/
├── workflows/
│   ├── ci-cd.yml              # Pipeline principal completo
│   ├── pr-validation.yml      # Validación de Pull Requests
│   ├── local-test.yml         # Workflow simplificado para testing local
│   └── README.md              # Documentación de workflows
├── act-events/
│   ├── push.json              # Evento de push simulado
│   └── pull_request.json      # Evento de PR simulado
└── copilot-instructions.md    # Instrucciones existentes

.aws/
└── task-definition.json       # Definición de tarea ECS

scripts/
├── test-actions-simple.ps1    # Script helper principal
└── test-actions.ps1           # Script helper complejo (backup)

.env.act.example               # Plantilla de configuración
.env.act                       # Configuración local (auto-generado)
.actrc                         # Configuración de act
```

## 🔧 Workflows Disponibles

### 1. `local-test.yml` - Test Rápido ⚡
**Propósito**: Validación rápida para desarrollo local
**Tiempo**: ~5-10 minutos
**Incluye**:
- ✅ Setup PHP 8.1
- ✅ Instalación de dependencias
- ✅ Verificación de sintaxis
- ✅ Validación de Composer
- ✅ Configuración básica de Laravel
- ✅ Tests básicos

### 2. `pr-validation.yml` - Validación de PR 🔍
**Propósito**: Validación completa para Pull Requests
**Tiempo**: ~10-15 minutos
**Incluye**:
- ✅ Todo lo del test local
- ✅ Coverage de código
- ✅ Comentarios automáticos en PR
- ✅ Auditoría de seguridad

### 3. `ci-cd.yml` - Pipeline Completo 🚀
**Propósito**: Pipeline completo de CI/CD
**Tiempo**: ~20-30 minutos
**Incluye**:
- ✅ Todos los tests
- ✅ Build Docker
- ✅ Deploy a AWS ECS
- ✅ Notificaciones

## ⚙️ Configuración Avanzada

### Variables de Entorno (.env.act)

```env
# AWS Configuration (para deploy testing)
AWS_REGION=us-east-1
AWS_ACCESS_KEY_ID=test-key          # No usar credenciales reales para testing local
AWS_SECRET_ACCESS_KEY=test-secret

# ECR/ECS Configuration
ECR_REPOSITORY=pqrs-api-test
ECS_CLUSTER_PROD=pqrs-cluster-test
ECS_SERVICE_PROD=pqrs-service-test

# PHP Configuration
PHP_VERSION=8.1

# Application Configuration
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Optional: GitHub Token para API calls
# GITHUB_TOKEN=ghp_your_token_here

# Optional: Slack notifications
# SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

### Personalizar Workflows

Para modificar los workflows para tu entorno:

1. **Editar variables en workflow files**:
   ```yaml
   env:
     PHP_VERSION: '8.2'        # Cambiar versión PHP
     AWS_REGION: eu-west-1     # Cambiar región
   ```

2. **Modificar task definition**:
   - Editar `.aws/task-definition.json`
   - Actualizar ARNs, nombres de containers, etc.

3. **Agregar secrets locales**:
   ```env
   # En .env.act
   NUEVA_VARIABLE=valor
   ```

## 🐛 Troubleshooting

### Problemas Comunes

**1. "act not found"**
```powershell
# Verificar instalación
Test-Path "C:\tools\act\act.exe"

# Usar ruta absoluta
C:\tools\act\act.exe --version
```

**2. "Docker daemon not running"**
```powershell
# Iniciar Docker Desktop
Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"

# Verificar estado
docker info
```

**3. "Unable to find image"**
```powershell
# Primera ejecución descarga imágenes (~300MB)
# Solo esperar que termine la descarga
```

**4. "Permission denied"**
```powershell
# Ejecutar PowerShell como administrador si hay problemas de permisos
```

**5. Tests fallan en Laravel**
```powershell
# Verificar que composer.json existe y es válido
composer validate

# Verificar que .env.example existe
Test-Path .env.example
```

### Logs y Debugging

```powershell
# Ejecutar con verbose
.\scripts\test-actions-simple.ps1 test -ShowVerbose

# Ver logs de Docker
docker logs <container_id>

# Ver workflows sin ejecutar
C:\tools\act\act.exe -W .github/workflows/local-test.yml --list
```

## 📊 Comparación con GitHub Actions Real

| Aspecto | act (Local) | GitHub Actions (Cloud) |
|---------|-------------|-------------------------|
| **Velocidad** | ⚡ Más rápido (sin queue) | 🐌 Depende de queue |
| **Costo** | 💰 Gratis | 💰 Consumo de minutos |
| **Debugging** | 🔍 Fácil debugging local | 🔍 Solo logs remotos |
| **Recursos** | 💾 Limitado por tu máquina | 💾 Recursos dedicados |
| **Secrets** | 🔐 Variables locales | 🔐 GitHub Secrets |
| **Integraciones** | ⚠️ Limitadas | ✅ Completas |

## 🎯 Workflow Recomendado de Desarrollo

1. **Desarrollo Local**:
   ```powershell
   # Test rápido durante desarrollo
   .\scripts\test-actions-simple.ps1 test
   ```

2. **Antes de PR**:
   ```powershell
   # Test completo de PR
   .\scripts\test-actions-simple.ps1 pr
   ```

3. **Validación Final**:
   ```powershell
   # Test del pipeline completo (opcional)
   .\scripts\test-actions-simple.ps1 full -DryRun
   ```

4. **Push a GitHub**:
   - GitHub Actions ejecuta automáticamente
   - Compara resultados local vs cloud

## 📚 Recursos Adicionales

- [act Documentation](https://github.com/nektos/act)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- [Laravel Testing](https://laravel.com/docs/testing)

---

💡 **Tip**: Usa `act` para desarrollo iterativo rápido, pero siempre valida en GitHub Actions real antes de hacer merge a ramas principales.