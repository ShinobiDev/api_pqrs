# 🐳 Guía de Inicio Rápido - PQRS API con Docker

Esta guía te ayudará a configurar y ejecutar la aplicación PQRS API usando Docker para desarrollo.

## 📋 Prerrequisitos

- **Docker Desktop** instalado y ejecutándose
- **Windows PowerShell** (viene incluido en Windows)
- **Git** (opcional, para clonar el repositorio)

## 🚀 Inicio Rápido

### 1. Inicializar el proyecto

```powershell
.\scripts\docker-dev.ps1 init
```

Este comando:
- Crea el archivo `.env` desde `.env.docker.dev`
- Crea la red Docker personalizada `pqrs-network`

### 2. Construir las imágenes

```powershell
.\scripts\docker-dev.ps1 build
```

Este comando construye la imagen Docker multi-stage para desarrollo.

### 3. Iniciar todos los servicios

```powershell
.\scripts\docker-dev.ps1 up
```

Este comando inicia todos los servicios:
- **Aplicación Laravel** (puerto 8000)
- **MySQL 8.0** (puerto 3306)
- **Redis 7** (puerto 6379)
- **Nginx** (puerto 80)
- **MailHog** (puertos 1025/8025)

### 4. Verificar que todo funciona

```powershell
# Ver estado de los contenedores
docker-compose -f docker-compose.dev.yml ps

# Ver logs de la aplicación
.\scripts\docker-dev.ps1 logs app

# Acceder al contenedor
.\scripts\docker-dev.ps1 shell
```

## 🌐 URLs de Acceso

Una vez que los servicios estén ejecutándose:

- **API Principal**: http://localhost:8000
- **MailHog (Testing de emails)**: http://localhost:8025
- **Base de datos MySQL**: localhost:3306
  - Usuario: `pqrs_user`
  - Password: `secret`
  - Base de datos: `pqrs_db`
- **Redis**: localhost:6379
  - Password: `redis_secret`

## 🛠️ Comandos Útiles

### Gestión de base de datos

```powershell
# Ejecutar migraciones
.\scripts\docker-dev.ps1 migrate

# Ejecutar seeders
.\scripts\docker-dev.ps1 seed
```

### Desarrollo

```powershell
# Ejecutar tests
.\scripts\docker-dev.ps1 test

# Acceder al shell del contenedor
.\scripts\docker-dev.ps1 shell

# Ver logs en tiempo real
.\scripts\docker-dev.ps1 logs app
.\scripts\docker-dev.ps1 logs mysql
.\scripts\docker-dev.ps1 logs redis
```

### Limpieza

```powershell
# Detener servicios
.\scripts\docker-dev.ps1 down

# Limpiar todo (contenedores, imágenes, volúmenes)
.\scripts\docker-dev.ps1 clean
```

## 📁 Estructura de Archivos

```
├── Dockerfile                    # Multi-stage build
├── docker-compose.dev.yml       # Configuración desarrollo
├── .env.docker.dev             # Variables desarrollo
├── .env.docker.prod            # Variables producción
├── .dockerignore               # Exclusiones Docker
├── docker/
│   ├── nginx/
│   │   └── nginx.dev.conf      # Config Nginx desarrollo
│   └── mysql/
│       └── my.cnf              # Config MySQL desarrollo
└── scripts/
    └── docker-dev.ps1          # Script automatización
```

## 🔧 Solución de Problemas

### Error: "Docker no está ejecutándose"
- Asegúrate de que Docker Desktop esté abierto y ejecutándose
- Reinicia Docker Desktop si es necesario

### Error: "Puerto ya en uso"
- Verifica qué aplicación está usando el puerto
- Cambia los puertos en `docker-compose.dev.yml` si es necesario

### Error: "No se puede conectar a MySQL"
- Espera a que el healthcheck de MySQL pase (puede tomar hasta 40 segundos)
- Verifica los logs: `.\scripts\docker-dev.ps1 logs mysql`

### Error: "Permisos denegados"
- Asegúrate de ejecutar PowerShell como administrador
- Verifica que no haya archivos bloqueados por Windows

## 📚 Comandos Docker Directos

Si prefieres usar Docker Compose directamente:

```powershell
# Construir
docker-compose -f docker-compose.dev.yml build

# Iniciar
docker-compose -f docker-compose.dev.yml up -d

# Detener
docker-compose -f docker-compose.dev.yml down

# Ver logs
docker-compose -f docker-compose.dev.yml logs -f

# Acceder al contenedor
docker-compose -f docker-compose.dev.yml exec app bash
```

## 🔒 Variables de Entorno

El archivo `.env.docker.dev` contiene todas las variables necesarias para desarrollo. Las más importantes:

```bash
APP_NAME="PQRS API Dev"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=pqrs_db
DB_USERNAME=pqrs_user
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=redis_secret
```

## 📞 Soporte

Si encuentras problemas:

1. Verifica los logs: `.\scripts\docker-dev.ps1 logs`
2. Reinicia los servicios: `.\scripts\docker-dev.ps1 down && .\scripts\docker-dev.ps1 up`
3. Verifica la configuración de Docker Desktop
4. Consulta la documentación en `docs/containerization/`

---

¡Listo! Tu entorno de desarrollo con Docker está configurado. 🎉