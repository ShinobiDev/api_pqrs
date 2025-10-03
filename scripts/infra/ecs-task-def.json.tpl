{
  "family": "pqrs-task",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "512",
  "memory": "1024",
  "executionRoleArn": "__EXECUTION_ROLE__",
  "taskRoleArn": "__TASK_ROLE__",
  "containerDefinitions": [
    {
      "name": "pqrs-app",
      "image": "__IMAGE__",
      "essential": true,
      "portMappings": [ { "containerPort": 8000, "protocol": "tcp" } ],
      "environment": [],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/pqrs",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      }
    }
  ]
}
