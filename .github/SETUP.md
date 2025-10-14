# GitHub Actions Configuration for API PQRS

## Environment Variables Template

Copy this template and create your own `.env.local` for local testing:

```bash
# Application Configuration
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:your_app_key_here

# Database Configuration  
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# AWS Configuration (for deployment)
AWS_REGION=us-east-1
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key

# Container Configuration
CONTAINER_NAME=pqrs-api-container
ECR_REPOSITORY=pqrs-api-repo
ECS_CLUSTER_PROD=pqrs-cluster-prod
ECS_CLUSTER_STAGING=pqrs-cluster-staging
ECS_SERVICE_PROD=pqrs-service-prod
ECS_SERVICE_STAGING=pqrs-service-staging
ECS_TASK_DEFINITION=.aws/task-definition.json

# PHP Configuration
PHP_VERSION=8.2
```

## Required GitHub Repository Settings

### 1. Branch Protection Rules

Configure these rules for `main` and `develop` branches:

- ✅ Require pull request reviews before merging
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging
- ✅ Include administrators in restrictions

### 2. Actions Permissions

Go to Settings → Actions → General:

- ✅ Allow all actions and reusable workflows
- ✅ Allow actions created by GitHub
- ✅ Allow actions by Marketplace verified creators

### 3. Environments

Create these environments in Settings → Environments:

#### Production Environment
- **Name:** `production`
- **Protection Rules:**
  - Required reviewers: 1-2 team members
  - Deployment branches: `main` only
  - Environment secrets: Production AWS credentials

#### Staging Environment  
- **Name:** `staging`
- **Protection Rules:**
  - Deployment branches: `develop` and `main`
  - Environment secrets: Staging AWS credentials

## Security Checklist

### Secrets Management
- [ ] All sensitive data stored in GitHub Secrets
- [ ] No hardcoded credentials in code
- [ ] Separate secrets for staging/production
- [ ] Regular rotation of access keys

### AWS IAM Permissions
Create IAM user with minimal required permissions:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ecr:GetAuthorizationToken",
                "ecr:BatchCheckLayerAvailability",
                "ecr:GetDownloadUrlForLayer",
                "ecr:BatchGetImage",
                "ecr:InitiateLayerUpload",
                "ecr:UploadLayerPart",
                "ecr:CompleteLayerUpload",
                "ecr:PutImage"
            ],
            "Resource": "*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "ecs:UpdateService",
                "ecs:DescribeServices",
                "ecs:DescribeTaskDefinition",
                "ecs:RegisterTaskDefinition"
            ],
            "Resource": "*"
        }
    ]
}
```