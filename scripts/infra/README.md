# Infra scripts (AWS CLI + PowerShell)
```markdown
# Infra — scripts para levantar la infraestructura (AWS + PowerShell)

Este directorio contiene varios scripts en PowerShell para automatizar tareas en AWS:
- crear o recuperar repositorios ECR
- construir y push de imágenes Docker a ECR
- registrar definiciones de tarea ECS y actualizar servicios
- crear un Application Load Balancer (ALB)

Los ejemplos y pasos rápidos siguientes están pensados para ejecutarse en Windows PowerShell (powershell.exe / v5.1). Si usas PowerShell Core (pwsh) o un agente de CI, adapta el intérprete según tu entorno.

Requisitos previos
- Docker (desktop o engine) instalado y disponible en PATH
- AWS CLI v2 instalada y configurada (o credenciales en variables de entorno)
- PowerShell (en Windows, powershell.exe funciona)
- Permisos IAM apropiados para crear ECR, ECS, ALB y actualizar servicios

Configurar credenciales (opciones)
- Usar el helper interactivo de AWS:
```powershell
aws configure
```
- O exportar variables en la sesión de PowerShell:
```powershell
$env:AWS_ACCESS_KEY_ID = 'AKIA...'
$env:AWS_SECRET_ACCESS_KEY = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCY...'
$env:AWS_DEFAULT_REGION = 'us-east-1'
```

Flujo rápido (paso a paso)

1) Crear (o recuperar) el repositorio ECR
```powershell
.\create-ecr.ps1 -RepoName pqrs-api -Region us-east-1
```
El script imprimirá la URI del repositorio (p. ej. 012345678901.dkr.ecr.us-east-1.amazonaws.com/pqrs-api). Puedes capturarla en una variable:
```powershell
$ecrUri = .\create-ecr.ps1 -RepoName pqrs-api -Region us-east-1
Write-Output $ecrUri
```

2) (Opcional) Login manual a ECR si el script no lo hace
```powershell
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 012345678901.dkr.ecr.us-east-1.amazonaws.com
```

3) Construir la imagen y hacer push a ECR
```powershell
.\build-and-push.ps1 -EcrUri 012345678901.dkr.ecr.us-east-1.amazonaws.com/pqrs-api -Tag dev-123
```
O usando la variable devuelta anteriormente:
```powershell
.\build-and-push.ps1 -EcrUri $ecrUri -Tag dev-123
```

4) Registrar la definición de tarea y desplegar la nueva imagen (ECS)
El script espera una plantilla `ecs-task-def.json.tpl` en la que sustituye el campo de imagen. Ejemplo:
```powershell
.\register-task-and-deploy.ps1 -Cluster pqrs-cluster -Service pqrs-service -Image "012345678901.dkr.ecr.us-east-1.amazonaws.com/pqrs-api:dev-123" -ExecutionRoleArn "arn:aws:iam::012345678901:role/ecsTaskExecutionRole"
```
Revisa `ecs-task-def.json.tpl` para añadir variables sensibles vía SSM/Secrets Manager en lugar de inyectarlas en claro.

5) Crear ALB (si se necesita)
```powershell
.\create-alb.ps1 -AlbName pqrs-alb -VpcId vpc-012345 -SubnetIds @('subnet-012','subnet-345')
```
Dependiendo del script, puede aceptar parámetros adicionales como SecurityGroupIds; revisa la ayuda del script con `-Help`.

Comprobaciones rápidas y debugging
- Verifica que la imagen existe en ECR: desde AWS Console o `aws ecr describe-images --repository-name pqrs-api --region us-east-1`
- Si el push falla por permisos: comprueba la política de IAM del usuario/role y que `ecr:GetAuthorizationToken` está permitido
- Si el deploy de ECS no actualiza la tarea: revisa CloudWatch Events / logs y el id de la definición de tarea registrada

Uso local alternativo (docker-compose)
Si sólo quieres levantar el API localmente para desarrollo, usa el `docker-compose.dev.yml` en la raíz del proyecto:
```powershell
docker compose -f ..\..\docker-compose.dev.yml up --build -d
```
(Ejecuta desde `scripts/infra` o ajusta la ruta al fichero)

Notas para CI / Jenkins
- En Jenkins establece las variables de entorno: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` antes de ejecutar los scripts
- Usa tags reproducibles: `-Tag $BUILD_NUMBER` o `-Tag mybranch-commitsha`
- El agente debe tener Docker y AWS CLI disponibles. En Windows agents usa pwsh si los scripts requieren PowerShell Core.

Buenas prácticas
- No poner secretos ni credenciales en archivos de tarea ECS: usa SSM Parameter Store o Secrets Manager
- Mantén plantillas de task definitions en control de versiones (ej. `ecs-task-def.json.tpl`) y parametrízalas desde CI

Si quieres que añada ejemplos concretos para Jenkinsfile o un ejemplo de `ecs-task-def.json.tpl`, dime y lo añado aquí.

```
