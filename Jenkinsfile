pipeline {
    agent any
    
    environment {
        // Variables de entorno para el proyecto
        PHP_VERSION = '8.1'
        COMPOSER_HOME = '/tmp/composer'
        DB_CONNECTION = 'sqlite'
        DB_DATABASE = ':memory:'
        APP_KEY = 'base64:YourGeneratedAppKeyHere='
        APP_ENV = 'testing'
        APP_DEBUG = 'true'
    }
    
    options {
        // Mantener solo los últimos 10 builds
        buildDiscarder(logRotator(numToKeepStr: '10'))
        // Timeout del pipeline
        timeout(time: 30, unit: 'MINUTES')
        // Timestamps en los logs
        timestamps()
    }
    
    triggers {
        // Polling SCM cada 5 minutos
        pollSCM('H/5 * * * *')
        // Webhook de GitHub (si está configurado)
        githubPush()
    }
    
    stages {
        stage('🔍 Checkout') {
            steps {
                echo 'Descargando código desde GitHub...'
                checkout scm
                
                script {
                    // Obtener información del commit
                    env.GIT_COMMIT_SHORT = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    env.GIT_BRANCH_NAME = sh(
                        script: 'git rev-parse --abbrev-ref HEAD',
                        returnStdout: true
                    ).trim()
                }
                
                echo "Branch: ${env.GIT_BRANCH_NAME}"
                echo "Commit: ${env.GIT_COMMIT_SHORT}"
            }
        }
        
        stage('🐘 Setup PHP Environment') {
            steps {
                echo 'Configurando entorno PHP...'
                sh '''
                    # Verificar versión de PHP
                    php --version
                    
                    # Verificar extensiones requeridas
                    php -m | grep -E "(pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath)"
                    
                    # Crear directorio de Composer si no existe
                    mkdir -p $COMPOSER_HOME
                '''
            }
        }
        
        stage('📦 Install Dependencies') {
            steps {
                echo 'Instalando dependencias de Composer...'
                sh '''
                    # Instalar dependencias sin scripts de desarrollo
                    composer install --no-dev --no-scripts --no-progress --prefer-dist --optimize-autoloader
                    
                    # Instalar dependencias de desarrollo para testing
                    composer install --dev --no-scripts --no-progress --prefer-dist
                    
                    # Mostrar packages instalados
                    composer show --installed
                '''
            }
        }
        
        stage('⚙️ Configure Application') {
            steps {
                echo 'Configurando aplicación Laravel...'
                sh '''
                    # Copiar archivo de configuración
                    cp .env.example .env
                    
                    # Generar clave de aplicación
                    php artisan key:generate --no-interaction
                    
                    # Configurar base de datos para testing
                    echo "DB_CONNECTION=sqlite" >> .env
                    echo "DB_DATABASE=:memory:" >> .env
                    
                    # Crear directorio de logs si no existe
                    mkdir -p storage/logs
                    mkdir -p storage/framework/cache
                    mkdir -p storage/framework/sessions
                    mkdir -p storage/framework/views
                    
                    # Establecer permisos
                    chmod -R 775 storage bootstrap/cache
                '''
            }
        }
        
        stage('🔍 Code Quality Analysis') {
            parallel {
                stage('PHP Syntax Check') {
                    steps {
                        echo 'Verificando sintaxis PHP...'
                        sh '''
                            # Verificar sintaxis en archivos PHP
                            find app -name "*.php" -exec php -l {} \\;
                            find config -name "*.php" -exec php -l {} \\;
                            find routes -name "*.php" -exec php -l {} \\;
                        '''
                    }
                }
                
                stage('Composer Validation') {
                    steps {
                        echo 'Validando composer.json...'
                        sh 'composer validate --strict'
                    }
                }
                
                stage('Security Check') {
                    steps {
                        echo 'Verificando vulnerabilidades de seguridad...'
                        sh '''
                            # Verificar vulnerabilidades conocidas
                            composer audit || true
                        '''
                    }
                }
            }
        }
        
        stage('🧪 Run Tests') {
            steps {
                echo 'Ejecutando pruebas automatizadas...'
                sh '''
                    # Ejecutar migraciones para testing
                    php artisan migrate --force --no-interaction
                    
                    # Ejecutar seeders si existen
                    php artisan db:seed --force --no-interaction || true
                    
                    # Ejecutar pruebas con PHPUnit
                    php artisan test --parallel --coverage --coverage-clover=coverage.xml
                    
                    # Generar reporte de cobertura
                    php artisan test --coverage-html=coverage-report || true
                '''
            }
            post {
                always {
                    // Publicar resultados de pruebas
                    publishTestResults(testResultsPattern: 'tests/_output/*.xml')
                    
                    // Publicar cobertura de código
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'coverage-report',
                        reportFiles: 'index.html',
                        reportName: 'Coverage Report'
                    ])
                }
            }
        }
        
        stage('📋 Generate API Documentation') {
            steps {
                echo 'Generando documentación de la API...'
                sh '''
                    # Generar documentación Swagger
                    php artisan l5-swagger:generate
                    
                    # Verificar que la documentación se generó correctamente
                    ls -la storage/api-docs/
                '''
            }
        }
        
        stage('🚀 Deploy') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'develop'
                }
            }
            steps {
                echo "Desplegando aplicación desde branch: ${env.GIT_BRANCH_NAME}"
                
                script {
                    if (env.GIT_BRANCH_NAME == 'main' || env.GIT_BRANCH_NAME == 'master') {
                        // Despliegue a producción
                        echo 'Desplegando a PRODUCCIÓN...'
                        sh '''
                            echo "🚀 Despliegue a PRODUCCIÓN"
                            echo "Commit: ${GIT_COMMIT_SHORT}"
                            echo "Branch: ${GIT_BRANCH_NAME}"
                            
                            # Aquí iría el script de despliegue a producción
                            # Ejemplo: rsync, Docker build/push, etc.
                            
                            # Optimizar aplicación para producción
                            composer install --no-dev --optimize-autoloader
                            php artisan config:cache
                            php artisan route:cache
                            php artisan view:cache
                        '''
                    } else if (env.GIT_BRANCH_NAME == 'develop') {
                        // Despliegue a staging
                        echo 'Desplegando a STAGING...'
                        sh '''
                            echo "🧪 Despliegue a STAGING"
                            echo "Commit: ${GIT_COMMIT_SHORT}"
                            echo "Branch: ${GIT_BRANCH_NAME}"
                            
                            # Aquí iría el script de despliegue a staging
                            # Mantener debug activo en staging
                        '''
                    }
                }
            }
        }
        
        stage('🔔 Notify') {
            steps {
                script {
                    def status = currentBuild.currentResult ?: 'SUCCESS'
                    def color = status == 'SUCCESS' ? 'good' : 'danger'
                    def message = """
                        *Pipeline ${status}* 🎯
                        
                        *Proyecto:* API PQRS
                        *Branch:* ${env.GIT_BRANCH_NAME}
                        *Commit:* ${env.GIT_COMMIT_SHORT}
                        *Build:* ${env.BUILD_NUMBER}
                        *Duración:* ${currentBuild.durationString}
                        
                        <${env.BUILD_URL}|Ver Build>
                    """.stripIndent()
                    
                    echo message
                    
                    // Aquí podrías agregar notificaciones a Slack, Teams, etc.
                    // slackSend(color: color, message: message)
                }
            }
        }
    }
    
    post {
        always {
            echo 'Pipeline completado!'
            
            // Limpiar workspace si es necesario
            cleanWs(patterns: [
                [pattern: 'vendor/**', type: 'INCLUDE'],
                [pattern: 'node_modules/**', type: 'INCLUDE'],
                [pattern: '.env', type: 'INCLUDE']
            ])
        }
        
        success {
            echo '✅ Pipeline ejecutado exitosamente!'
        }
        
        failure {
            echo '❌ Pipeline falló!'
            
            // Enviar notificación de error
            script {
                def message = """
                    🚨 *PIPELINE FAILED* 🚨
                    
                    *Proyecto:* API PQRS
                    *Branch:* ${env.GIT_BRANCH_NAME}
                    *Build:* ${env.BUILD_NUMBER}
                    *Error:* ${currentBuild.description ?: 'Ver logs para detalles'}
                    
                    <${env.BUILD_URL}console|Ver Logs>
                """.stripIndent()
                
                echo message
            }
        }
        
        unstable {
            echo '⚠️ Pipeline inestable (algunas pruebas fallaron)'
        }
        
        changed {
            echo '🔄 Estado del pipeline cambió desde el último build'
        }
    }
}