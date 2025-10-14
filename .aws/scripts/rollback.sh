#!/bin/bash

# Rollback script for AWS ECS
# This script handles rollback to the previous task definition

set -e

# Configuration
CLUSTER_NAME=${ECS_CLUSTER:-"pqrs-cluster"}
SERVICE_NAME=${ECS_SERVICE:-"pqrs-service"}
AWS_REGION=${AWS_REGION:-"us-east-1"}

echo "🔄 Starting rollback process..."
echo "Cluster: $CLUSTER_NAME"
echo "Service: $SERVICE_NAME"
echo "Region: $AWS_REGION"

# Function to get previous task definition
get_previous_task_definition() {
    local cluster=$1
    local service=$2
    
    # Get current service task definition
    local current_task_def=$(aws ecs describe-services \
        --cluster "$cluster" \
        --services "$service" \
        --region "$AWS_REGION" \
        --query 'services[0].taskDefinition' \
        --output text)
        
    echo "Current task definition: $current_task_def"
    
    # Extract family name from current task definition
    local family=$(echo "$current_task_def" | cut -d'/' -f2 | cut -d':' -f1)
    
    # Get the previous revision
    local previous_task_def=$(aws ecs list-task-definitions \
        --family-prefix "$family" \
        --status ACTIVE \
        --sort DESC \
        --region "$AWS_REGION" \
        --query 'taskDefinitionArns[1]' \
        --output text)
        
    if [ "$previous_task_def" = "None" ] || [ -z "$previous_task_def" ]; then
        echo "❌ No previous task definition found!"
        return 1
    fi
    
    echo "Previous task definition: $previous_task_def"
    echo "$previous_task_def"
}

# Function to wait for rollback to complete
wait_for_rollback() {
    local cluster=$1
    local service=$2
    local max_attempts=20
    local attempt=1
    
    echo "⏳ Waiting for rollback to complete..."
    
    while [ $attempt -le $max_attempts ]; do
        local deployment_status=$(aws ecs describe-services \
            --cluster "$cluster" \
            --services "$service" \
            --region "$AWS_REGION" \
            --query 'services[0].deployments[?status==`RUNNING`].status' \
            --output text)
            
        if [ "$deployment_status" = "RUNNING" ]; then
            echo "✅ Rollback completed successfully!"
            return 0
        fi
        
        echo "⏳ Rollback in progress... (attempt $attempt/$max_attempts)"
        sleep 15
        ((attempt++))
    done
    
    echo "❌ Rollback timed out!"
    return 1
}

# Main rollback process
main() {
    # Get previous task definition
    echo "🔍 Finding previous task definition..."
    PREVIOUS_TASK_DEF=$(get_previous_task_definition "$CLUSTER_NAME" "$SERVICE_NAME")
    
    if [ $? -ne 0 ]; then
        echo "❌ Cannot determine previous task definition!"
        exit 1
    fi
    
    # Confirm rollback
    echo "⚠️  About to rollback to: $PREVIOUS_TASK_DEF"
    echo "⚠️  This will replace the current running version!"
    
    # In CI/CD, we skip confirmation. For manual use, uncomment:
    # read -p "Do you want to continue? (y/N) " -n 1 -r
    # echo
    # if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    #     echo "Rollback cancelled."
    #     exit 0
    # fi
    
    # Perform rollback
    echo "🔄 Rolling back ECS service..."
    aws ecs update-service \
        --cluster "$CLUSTER_NAME" \
        --service "$SERVICE_NAME" \
        --task-definition "$PREVIOUS_TASK_DEF" \
        --region "$AWS_REGION" \
        --force-new-deployment > /dev/null
        
    echo "✅ Rollback initiated"
    
    # Wait for rollback to complete
    if wait_for_rollback "$CLUSTER_NAME" "$SERVICE_NAME"; then
        echo "🎉 Rollback completed successfully!"
        
        # Final health check
        local running_count=$(aws ecs describe-services \
            --cluster "$CLUSTER_NAME" \
            --services "$SERVICE_NAME" \
            --region "$AWS_REGION" \
            --query 'services[0].runningCount' \
            --output text)
            
        echo "Running tasks after rollback: $running_count"
        
        if [ "$running_count" -gt 0 ]; then
            echo "✅ Service is running after rollback!"
        else
            echo "⚠️  Service may not be running properly after rollback!"
        fi
        
        exit 0
    else
        echo "❌ Rollback failed!"
        exit 1
    fi
}

# Check if we have AWS CLI access
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI is not installed or not in PATH!"
    exit 1
fi

# Check AWS credentials
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS credentials are not configured properly!"
    exit 1
fi

# Run main function
main