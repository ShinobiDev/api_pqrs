#!/bin/bash

# Health check script for PQRS API
# Verifies that the application is running correctly after deployment

set -e

# Configuration
API_BASE_URL=${API_BASE_URL:-"http://localhost"}
HEALTH_ENDPOINT=${HEALTH_ENDPOINT:-"/api/health"}
MAX_ATTEMPTS=${MAX_ATTEMPTS:-10}
RETRY_DELAY=${RETRY_DELAY:-30}

echo "🏥 Starting health check for PQRS API..."
echo "URL: $API_BASE_URL$HEALTH_ENDPOINT"

# Function to check API health
check_api_health() {
    local url="$API_BASE_URL$HEALTH_ENDPOINT"
    
    # Try to reach the health endpoint
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    
    if [ "$response" = "200" ]; then
        echo "✅ Health endpoint responded with 200 OK"
        return 0
    else
        echo "❌ Health endpoint responded with: $response"
        return 1
    fi
}

# Function to check database connectivity
check_database() {
    local url="$API_BASE_URL/api/status"
    
    # Try to reach a simple database-dependent endpoint
    response=$(curl -s "$url" 2>/dev/null || echo "error")
    
    if [[ "$response" == *"success"* ]] || [[ "$response" == *"ok"* ]]; then
        echo "✅ Database connectivity verified"
        return 0
    else
        echo "⚠️  Database connectivity could not be verified"
        return 1
    fi
}

# Function to check key API endpoints
check_api_endpoints() {
    local endpoints=(
        "/api/pqrs"
        "/api/users"
        "/api/statuses"
    )
    
    echo "🔍 Checking key API endpoints..."
    
    for endpoint in "${endpoints[@]}"; do
        local url="$API_BASE_URL$endpoint"
        local response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
        
        # Accept 200 (OK) or 401 (Unauthorized - means endpoint is working but needs auth)
        if [ "$response" = "200" ] || [ "$response" = "401" ]; then
            echo "✅ $endpoint - OK ($response)"
        else
            echo "❌ $endpoint - Failed ($response)"
            return 1
        fi
    done
    
    return 0
}

# Function to check application logs for errors
check_application_logs() {
    echo "📋 Checking for recent application errors..."
    
    # Check if we're in a Docker environment
    if [ -f "/var/log/apache2/error.log" ]; then
        recent_errors=$(tail -n 50 /var/log/apache2/error.log | grep -i "error\|fatal\|exception" | tail -n 5 || true)
        if [ -n "$recent_errors" ]; then
            echo "⚠️  Recent errors found in logs:"
            echo "$recent_errors"
        else
            echo "✅ No recent errors in application logs"
        fi
    else
        echo "📋 Log file not accessible (may be in container)"
    fi
}

# Function to perform comprehensive health check
perform_health_check() {
    local attempt=1
    
    while [ $attempt -le $MAX_ATTEMPTS ]; do
        echo "🔍 Health check attempt $attempt/$MAX_ATTEMPTS"
        
        # Basic health endpoint check
        if check_api_health; then
            echo "✅ Basic health check passed"
            
            # Additional checks if basic health passes
            sleep 5  # Give the service a moment to stabilize
            
            # Check database connectivity
            check_database
            
            # Check key endpoints
            if check_api_endpoints; then
                echo "✅ All API endpoints accessible"
                
                # Check application logs
                check_application_logs
                
                echo "🎉 Comprehensive health check PASSED!"
                return 0
            else
                echo "❌ Some API endpoints failed"
            fi
        else
            echo "❌ Basic health check failed"
        fi
        
        if [ $attempt -lt $MAX_ATTEMPTS ]; then
            echo "⏳ Waiting $RETRY_DELAY seconds before retry..."
            sleep $RETRY_DELAY
        fi
        
        ((attempt++))
    done
    
    echo "❌ Health check FAILED after $MAX_ATTEMPTS attempts"
    return 1
}

# Function to generate health report
generate_health_report() {
    local status=$1
    local report_file="/tmp/health_report.json"
    
    cat > "$report_file" << EOF
{
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "status": "$status",
    "api_base_url": "$API_BASE_URL",
    "health_endpoint": "$HEALTH_ENDPOINT",
    "max_attempts": $MAX_ATTEMPTS,
    "retry_delay": $RETRY_DELAY,
    "environment": "${APP_ENV:-unknown}",
    "version": "${APP_VERSION:-unknown}"
}
EOF
    
    echo "📊 Health report generated: $report_file"
    cat "$report_file"
}

# Main execution
main() {
    echo "🚀 Starting comprehensive health check..."
    
    # Check if curl is available
    if ! command -v curl &> /dev/null; then
        echo "❌ curl is not available! Cannot perform health checks."
        generate_health_report "error"
        exit 1
    fi
    
    # Perform health check
    if perform_health_check; then
        generate_health_report "healthy"
        echo "✅ Application is healthy and ready!"
        exit 0
    else
        generate_health_report "unhealthy"
        echo "❌ Application health check failed!"
        exit 1
    fi
}

# Handle script arguments
case "${1:-check}" in
    "check")
        main
        ;;
    "quick")
        echo "🏃 Quick health check..."
        check_api_health
        ;;
    "endpoints")
        echo "🔗 Checking endpoints only..."
        check_api_endpoints
        ;;
    *)
        echo "Usage: $0 [check|quick|endpoints]"
        echo "  check     - Full comprehensive health check (default)"
        echo "  quick     - Quick health endpoint check only"  
        echo "  endpoints - Check key API endpoints only"
        exit 1
        ;;
esac