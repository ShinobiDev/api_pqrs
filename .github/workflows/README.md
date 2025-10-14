# 🚀 GitHub Actions Workflows

Este directorio contiene los workflows de CI/CD para el proyecto API PQRS, migrados desde Jenkins a GitHub Actions.

## 📋 Workflows Disponibles

### 1. `ci-cd.yml` - Pipeline Principal
**Triggers:** Push a `main`, `master`, `develop`

**Características:**
- ✅ Setup completo de PHP 8.1
- ✅ Análisis de calidad de código paralelo
- ✅ Tests automatizados con Laravel
- ✅ Generación de documentación API
- ✅ Deploy automático a AWS ECS
- ✅ Notificaciones a Slack
- ✅ Soporte para staging y production

**Jobs:**
1. **Setup**: Detección de branch y configuración inicial
2. **PHP Setup**: Configuración del entorno PHP
3. **Code Quality**: Análisis paralelo (sintaxis, composer, security)
4. **App Config**: Configuración de Laravel
5. **Tests**: Ejecución de tests con PHPUnit
6. **Docs**: Generación de documentación Swagger
7. **Deploy**: Build Docker + Deploy a ECS
8. **Notify**: Notificaciones de resultados

### 2. `pr-validation.yml` - Validación de Pull Requests
**Triggers:** Pull Request a `main`, `master`, `develop`

**Características:**
- ⚡ Validación rápida para PRs
- 📊 Coverage de código
- 💬 Comentarios automáticos en PR
- 🔒 Auditoría de seguridad

## ⚙️ Configuración Requerida

### 1. 🔑 GitHub Secrets
Configura estos secrets en tu repositorio:

```bash
# AWS Credentials (requeridos para deploy)
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key

# Slack (opcional)
SLACK_WEBHOOK_URL=your_slack_webhook_url
```

### 2. 🏗️ AWS Infrastructure
Antes de usar los workflows, asegúrate de tener:

#### ECR Repository
```bash
aws ecr create-repository \
  --repository-name pqrs-api \
  --region us-east-1
```

#### ECS Cluster y Service
```bash
# Crear cluster
aws ecs create-cluster --cluster-name pqrs-cluster

# Crear service (después de task definition)
aws ecs create-service \
  --cluster pqrs-cluster \
  --service-name pqrs-service \
  --task-definition pqrs-api-task \
  --desired-count 1 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=[subnet-xxx],securityGroups=[sg-xxx],assignPublicIp=ENABLED}"
```

#### Task Definition
Edita `.aws/task-definition.json` y reemplaza:
- `YOUR_ACCOUNT_ID` con tu AWS Account ID
- `YOUR_ECR_URI` con tu ECR repository URI
- `YOUR_RDS_ENDPOINT` con tu RDS endpoint

### 3. 🔐 AWS Secrets Manager
Crea estos secrets para la aplicación:

```bash
# APP_KEY
aws secretsmanager create-secret \
  --name "pqrs-api/app-key" \
  --description "Laravel APP_KEY" \
  --secret-string "base64:your_generated_key"

# Database credentials
aws secretsmanager create-secret \
  --name "pqrs-api/db-username" \
  --secret-string "your_db_user"

aws secretsmanager create-secret \
  --name "pqrs-api/db-password" \
  --secret-string "your_db_password"
```

### 4. 📊 CloudWatch Logs
```bash
aws logs create-log-group --log-group-name /ecs/pqrs-api
```

## 🎯 Deployment Strategy

### Branch Strategy
- **`main/master`** → 🚀 Production deployment
- **`develop`** → 🧪 Staging deployment
- **Feature branches** → ✅ Tests only (no deployment)

### Environment Configuration
| Environment | ECS Cluster | ECS Service | Deploy Trigger |
|-------------|-------------|-------------|----------------|
| Production | `pqrs-cluster` | `pqrs-service` | Push to `main/master` |
| Staging | `pqrs-cluster-staging` | `pqrs-service-staging` | Push to `develop` |

## 🔧 Customización

### Variables de Entorno
Modifica las variables en `ci-cd.yml`:

```yaml
env:
  AWS_REGION: us-east-1          # Tu región AWS
  ECR_REPOSITORY: pqrs-api       # Nombre de tu ECR repo
  PHP_VERSION: '8.1'             # Versión de PHP
  CONTAINER_NAME: pqrs-api-container  # Nombre del container
```

### Modificar Tests
Los tests se ejecutan con:
```bash
php artisan test --env=testing
```

Para añadir coverage:
```bash
php artisan test --env=testing --coverage --min=80
```

### Notificaciones Slack
1. Crear Slack App y obtener webhook URL
2. Añadir `SLACK_WEBHOOK_URL` a GitHub Secrets
3. Los mensajes se envían automáticamente

## 🐛 Troubleshooting

### Tests Fallando
```bash
# Verificar configuración local
cp .env.example .env.testing
php artisan key:generate --env=testing
php artisan migrate:fresh --seed --env=testing
php artisan test --env=testing
```

### Deploy Fallando
1. Verificar AWS credentials en GitHub Secrets
2. Verificar que ECR repository existe
3. Verificar que ECS cluster y service están configurados
4. Revisar task definition en `.aws/task-definition.json`

### Permisos AWS
Tu usuario/role de AWS necesita estos permisos:
- ECR: `GetAuthorizationToken`, `BatchCheckLayerAvailability`, `GetDownloadUrlForLayer`, `BatchGetImage`, `PutImage`
- ECS: `RegisterTaskDefinition`, `UpdateService`, `DescribeServices`
- Secrets Manager: `GetSecretValue`
- CloudWatch: `CreateLogStream`, `PutLogEvents`

## 📈 Monitoring

### Métricas Disponibles
- ✅ Build success rate
- ⏱️ Build duration
- 🧪 Test coverage
- 🚀 Deployment frequency
- 🔄 Lead time for changes

### Logs
- **GitHub Actions**: En la pestaña Actions del repositorio
- **ECS Tasks**: CloudWatch Logs `/ecs/pqrs-api`
- **Application**: Logs de Laravel en ECS

## 🔄 Migración desde Jenkins

### Diferencias Principales
| Aspecto | Jenkins | GitHub Actions |
|---------|---------|----------------|
| **Triggers** | Polling + Webhooks | Push/PR events |
| **Parallelization** | Pipeline stages | Matrix strategy |
| **Caching** | Manual | Built-in cache action |
| **Secrets** | Jenkins credentials | GitHub Secrets |
| **Notifications** | Manual setup | Integrated actions |

### Beneficios
- ✅ **Integración nativa** con GitHub
- ✅ **Caching automático** de dependencies
- ✅ **Matrix builds** para testing paralelo
- ✅ **Mejor visibilidad** en PRs
- ✅ **Marketplace de actions** extenso
- ✅ **Costs más predecibles**

---

📝 **Nota**: Estos workflows están basados en el Jenkinsfile original pero optimizados para GitHub Actions con mejores prácticas y características nativas de la plataforma.