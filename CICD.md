# 🚀 CI/CD Implementation Guide - API PQRS

## 📖 Overview

This document provides a comprehensive guide for the Continuous Integration and Continuous Deployment (CI/CD) implementation using GitHub Actions for the Laravel-based API PQRS project.

## 🏗️ Architecture

### Migration from Jenkins to GitHub Actions
- **Previous:** Jenkins pipeline with manual deployment
- **Current:** GitHub Actions with automated AWS ECS deployment
- **Benefits:** Better integration, faster feedback, easier maintenance

### Core Components
1. **GitHub Actions Workflows** - CI/CD pipelines
2. **AWS ECS** - Container orchestration for deployment  
3. **AWS ECR** - Docker container registry
4. **Local Testing** - act tool for local workflow execution

## 📁 File Structure

```
.github/
├── workflows/
│   ├── ci-cd.yml              # Main production pipeline
│   ├── pr-validation.yml      # Pull request validation
│   ├── local-test-php82.yml   # Local testing with act
│   └── README.md              # Workflows documentation
├── act-events/
│   ├── pull_request.json      # Mock PR events for act
│   └── push.json              # Mock push events for act
└── copilot-instructions.md    # AI assistant instructions

.aws/
├── task-definition.json       # ECS task definition
├── appspec.yml               # CodeDeploy specification  
└── buildspec.yml            # CodeBuild specification

# Local testing configuration
.actrc                        # act tool configuration
.env.act.example             # Environment template for local testing
```

## 🔧 Workflows Detailed

### 1. Main CI/CD Pipeline (`ci-cd.yml`)

**Purpose:** Complete CI/CD pipeline for production deployments

**Triggers:**
- Push to `main`, `master`, `develop` branches
- Manual workflow dispatch

**Key Features:**
- PHP 8.2 setup with all required extensions
- Parallel code quality checks (syntax, composer, security)
- Comprehensive Laravel testing
- Swagger documentation generation
- Docker image build and push to ECR
- Automated deployment to AWS ECS
- Environment-specific deployments (staging/production)
- Slack notifications

**Jobs Flow:**
```
Setup → PHP Setup → Code Quality (parallel) → App Config → Tests → Docs → Deploy → Notify
```

### 2. PR Validation (`pr-validation.yml`)

**Purpose:** Fast validation for pull requests

**Triggers:**
- Pull requests to `main`, `master`, `develop`

**Features:**
- Lightweight validation (< 5 minutes)
- Code coverage reporting
- Automated PR comments with results
- Security audit
- Style checking

### 3. Local Testing (`local-test-php82.yml`)

**Purpose:** Local development testing using act tool

**Features:**
- Compatible with act (local GitHub Actions runner)
- Basic functionality validation
- Laravel project detection
- Environment verification

## 🔐 Security & Secrets

### Required GitHub Secrets

```bash
# AWS Deployment (Required)
AWS_ACCESS_KEY_ID=AKIA...
AWS_SECRET_ACCESS_KEY=...
AWS_REGION=us-east-1

# AWS Resources
ECR_REPOSITORY=your-ecr-repo-name
ECS_CLUSTER_PROD=your-production-cluster
ECS_CLUSTER_STAGING=your-staging-cluster
ECS_SERVICE_PROD=your-production-service
ECS_SERVICE_STAGING=your-staging-service

# Optional
SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

### Security Best Practices
- ✅ Secrets are never logged or exposed
- ✅ IAM roles follow least privilege principle
- ✅ Container images are scanned for vulnerabilities
- ✅ Dependencies are audited for security issues

## 🖥️ Local Development

### Prerequisites
```bash
# Install act tool for local testing
# Windows (using Chocolatey)
choco install act-cli

# Or download from GitHub releases
# Place in C:\tools\act\act.exe
```

### Local Testing Commands
```bash
# Test main workflow locally
act --job test --workflows .github/workflows/local-test-php82.yml --env-file .env.act

# List available workflows
act --list

# Test specific job
act --job setup

# Use custom container
act --container-architecture linux/amd64
```

### Environment Setup
1. Copy `.env.act.example` to `.env.act`
2. Update variables for your local environment
3. Configure `.actrc` for consistent settings

## 🚀 Deployment Strategy

### Branch-based Deployment
- **`develop`** → Staging environment
- **`main/master`** → Production environment
- **Feature branches** → No deployment (validation only)

### Deployment Process
1. **Build Phase:**
   - Code checkout and setup
   - Dependency installation
   - Test execution
   - Docker image creation

2. **Deploy Phase:**
   - Push image to ECR
   - Update ECS task definition
   - Rolling deployment to ECS cluster
   - Health checks and verification

3. **Verification:**
   - Service health validation
   - Smoke tests
   - Rollback on failure

## 📊 Monitoring & Notifications

### Slack Integration
- ✅ Build start notifications
- ✅ Success/failure alerts with details
- ✅ Deployment status updates
- ✅ Direct links to logs and actions

### Metrics Tracking
- Build duration
- Test coverage
- Deployment frequency
- Success/failure rates

## 🔧 Troubleshooting

### Common Issues

**1. PHP Version Compatibility**
```bash
# All workflows use PHP 8.2
# Update composer.json if needed
"require": {
    "php": "^8.2"
}
```

**2. Local act Testing**
```bash
# Container issues
act --container-architecture linux/amd64 --pull=false

# Environment issues  
act --env-file .env.act --verbose
```

**3. AWS Deployment Failures**
- Check AWS credentials and permissions
- Verify ECS cluster and service names
- Review CloudWatch logs for container issues

### Debug Commands
```bash
# View workflow logs
gh run list
gh run view <run-id>

# Local debugging
act --verbose --dry-run
```

## 🎯 Best Practices

### Code Quality
- All code must pass quality gates
- Tests are required for new features
- Documentation is auto-generated and versioned
- Security scans are mandatory

### Workflow Management
- Use descriptive commit messages
- Tag releases properly
- Monitor build performance
- Regular dependency updates

### Infrastructure
- Infrastructure as Code (CloudFormation/Terraform)
- Environment parity (dev/staging/prod)
- Automated backups and disaster recovery
- Cost optimization monitoring

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [act Local Runner](https://github.com/nektos/act)
- [AWS ECS Documentation](https://docs.aws.amazon.com/ecs/)
- [Laravel Testing Guide](https://laravel.com/docs/testing)

---

**Last Updated:** October 2025  
**Version:** 2.0.0  
**Maintainer:** Development Team