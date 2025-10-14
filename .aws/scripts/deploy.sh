#!/bin/bash

# Deploy script for AWS ECS
# This script handles the deployment process for the PQRS API

set -e

# Configuration
CLUSTER_NAME=${ECS_CLUSTER:-"pqrs-cluster"}
SERVICE_NAME=${ECS_SERVICE:-"pqrs-service"}
TASK_DEFINITION=${ECS_TASK_DEFINITION:-".aws/task-definition.json"}
AWS_REGION=${AWS_REGION:-"us-east-1"}

echo "🚀 Starting deployment to AWS ECS..."
echo "Cluster: $CLUSTER_NAME"
echo "Service: $SERVICE_NAME"
echo "Region: $AWS_REGION"

# Function to wait for deployment to complete
wait_for_deployment() {
    local cluster=$1
    local service=$2
    local max_attempts=30
    local attempt=1
    
    echo "⏳ Waiting for deployment to complete..."
    
    while [ $attempt -le $max_attempts ]; do
        local deployment_status=$(aws ecs describe-services \
            --cluster "$cluster" \
            --services "$service" \
            --region "$AWS_REGION" \
            --query 'services[0].deployments[?status==`RUNNING`].status' \
            --output text)
            
        if [ "$deployment_status" = "RUNNING" ]; then
            echo "✅ Deployment completed successfully!"
            return 0
        fi
        
        echo "⏳ Deployment in progress... (attempt $attempt/$max_attempts)"
        sleep 30
        ((attempt++))
    done
    
    echo "❌ Deployment timed out!"
    return 1
}

# Function to check service health
check_service_health() {
    local cluster=$1
    local service=$2
    
    echo "🔍 Checking service health..."
    
    local running_count=$(aws ecs describe-services \
        --cluster "$cluster" \
        --services "$service" \
        --region "$AWS_REGION" \
        --query 'services[0].runningCount' \
        --output text)
        
    local desired_count=$(aws ecs describe-services \
        --cluster "$cluster" \
        --services "$service" \
        --region "$AWS_REGION" \
        --query 'services[0].desiredCount' \
        --output text)
        
    echo "Running tasks: $running_count"
    echo "Desired tasks: $desired_count"
    
    if [ "$running_count" -eq "$desired_count" ] && [ "$running_count" -gt 0 ]; then
        echo "✅ Service is healthy!"
        return 0
    else
        echo "❌ Service is not healthy!"
        return 1
    fi
}

# Main deployment process
main() {
    # Register new task definition
    echo "📝 Registering new task definition..."
    NEW_TASK_DEFINITION=$(aws ecs register-task-definition \
        --cli-input-json file://$TASK_DEFINITION \
        --region "$AWS_REGION" \
        --query 'taskDefinition.taskDefinitionArn' \
        --output text)
        
    if [ -z "$NEW_TASK_DEFINITION" ]; then
        echo "❌ Failed to register task definition!"
        exit 1
    fi
    
    echo "✅ Task definition registered: $NEW_TASK_DEFINITION"
    
    # Update service
    echo "🔄 Updating ECS service..."
    aws ecs update-service \
        --cluster "$CLUSTER_NAME" \
        --service "$SERVICE_NAME" \
        --task-definition "$NEW_TASK_DEFINITION" \
        --region "$AWS_REGION" \
        --force-new-deployment > /dev/null
        
    echo "✅ Service update initiated"
    
    # Wait for deployment to complete
    if wait_for_deployment "$CLUSTER_NAME" "$SERVICE_NAME"; then
        # Check final service health
        if check_service_health "$CLUSTER_NAME" "$SERVICE_NAME"; then
            echo "🎉 Deployment completed successfully!"
            exit 0
        else
            echo "❌ Deployment completed but service is not healthy!"
            exit 1
        fi
    else
        echo "❌ Deployment failed!"
        exit 1
    fi
}

# Run main function
main