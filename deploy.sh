#!/bin/bash

#################################################
# Kiosco Credencialización - Deployment Script
#################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project name
PROJECT_NAME="kiosco-credencializacion"

# Functions
print_header() {
    echo -e "\n${BLUE}==================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}==================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}→ $1${NC}"
}

check_requirements() {
    print_header "Verificando Requisitos"
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker no está instalado"
        exit 1
    fi
    print_success "Docker instalado"
    
    # Check Docker Compose
    if ! command -v docker compose &> /dev/null && ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose no está instalado"
        exit 1
    fi
    print_success "Docker Compose instalado"
    
    # Check if .env.production exists
    if [ ! -f ".env.production" ]; then
        print_error "Archivo .env.production no encontrado"
        print_info "Copia .env.example a .env.production y configúralo"
        exit 1
    fi
    print_success "Archivo .env.production encontrado"
    
    # Check if CLOUDFLARE_TUNNEL_TOKEN is set
    if ! grep -q "CLOUDFLARE_TUNNEL_TOKEN.*=.*[A-Za-z0-9]" .env.production; then
        print_warning "CLOUDFLARE_TUNNEL_TOKEN no configurado en .env.production"
        print_info "El túnel de Cloudflare no funcionará sin un token válido"
    else
        print_success "Token de Cloudflare configurado"
    fi
}

build_images() {
    print_header "Construyendo Imágenes Docker"
    docker compose -f docker-compose.production.yml build --no-cache
    print_success "Imágenes construidas exitosamente"
}

start_services() {
    print_header "Iniciando Servicios"
    docker compose -f docker-compose.production.yml up -d
    print_success "Servicios iniciados"
    
    print_info "Esperando que los servicios estén listos..."
    sleep 10
}

stop_services() {
    print_header "Deteniendo Servicios"
    docker compose -f docker-compose.production.yml down
    print_success "Servicios detenidos"
}

restart_services() {
    print_header "Reiniciando Servicios"
    docker compose -f docker-compose.production.yml restart
    print_success "Servicios reiniciados"
}

install_dependencies() {
    print_header "Instalando Dependencias de PHP"
    docker compose -f docker-compose.production.yml exec -T app composer install --no-dev --optimize-autoloader --no-interaction
    print_success "Dependencias instaladas"
}

run_migrations() {
    print_header "Ejecutando Migraciones de Base de Datos"
    docker compose -f docker-compose.production.yml exec -T app php spark migrate
    print_success "Migraciones ejecutadas"
}

run_seeders() {
    print_header "Ejecutando Seeders"
    print_warning "Esto creará usuarios por defecto si no existen"
    read -p "¿Continuar? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker compose -f docker-compose.production.yml exec -T app php spark db:seed AuthSeeder
        print_success "Seeders ejecutados"
    else
        print_info "Seeders omitidos"
    fi
}

generate_encryption_key() {
    print_header "Generando Clave de Encriptación"
    docker compose -f docker-compose.production.yml exec -T app php spark key:generate
    print_success "Clave generada (revisa los logs arriba y añádela a .env.production)"
}

show_status() {
    print_header "Estado de los Servicios"
    docker compose -f docker-compose.production.yml ps
}

show_logs() {
    print_header "Mostrando Logs"
    docker compose -f docker-compose.production.yml logs -f --tail=100
}

backup_database() {
    print_header "Realizando Backup de Base de Datos"
    
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    BACKUP_FILE="backups/kiosco_backup_${TIMESTAMP}.sql"
    
    mkdir -p backups
    
    docker compose -f docker-compose.production.yml exec -T db mysqldump \
        -u root -p"${MYSQL_ROOT_PASSWORD}" \
        kiosco_production > "${BACKUP_FILE}"
    
    print_success "Backup guardado en ${BACKUP_FILE}"
}

setup_production() {
    print_header "🚀 Setup Completo para Producción"
    
    check_requirements
    build_images
    start_services
    install_dependencies
    run_migrations
    
    print_info "\n¿Deseas ejecutar los seeders para crear usuarios iniciales?"
    run_seeders
    
    show_status
    
    print_header "✅ Despliegue Completado"
    print_info "La aplicación debería estar corriendo"
    print_info "Verifica el túnel de Cloudflare con: docker compose -f docker-compose.production.yml logs tunnel"
}

show_help() {
    cat << EOF

${GREEN}Kiosco Credencialización - Script de Despliegue${NC}

${YELLOW}USO:${NC}
    ./deploy.sh [comando]

${YELLOW}COMANDOS:${NC}
    ${BLUE}setup${NC}          Setup completo (build + start + migrate + seed)
    ${BLUE}start${NC}          Iniciar servicios
    ${BLUE}stop${NC}           Detener servicios
    ${BLUE}restart${NC}        Reiniciar servicios
    ${BLUE}build${NC}          Construir imágenes Docker
    ${BLUE}install${NC}        Instalar dependencias de PHP
    ${BLUE}migrate${NC}        Ejecutar migraciones de BD
    ${BLUE}seed${NC}           Ejecutar seeders
    ${BLUE}status${NC}         Ver estado de servicios
    ${BLUE}logs${NC}           Ver logs en tiempo real
    ${BLUE}backup${NC}         Hacer backup de base de datos
    ${BLUE}keygen${NC}         Generar clave de encriptación
    ${BLUE}help${NC}           Mostrar esta ayuda

${YELLOW}EJEMPLOS:${NC}
    ./deploy.sh setup       # Primera vez - setup completo
    ./deploy.sh start       # Iniciar servicios
    ./deploy.sh logs        # Ver logs
    ./deploy.sh backup      # Hacer backup

EOF
}

# Main script
case "${1:-help}" in
    setup)
        setup_production
        ;;
    start)
        check_requirements
        start_services
        show_status
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        show_status
        ;;
    build)
        build_images
        ;;
    install)
        install_dependencies
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        run_seeders
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    backup)
        backup_database
        ;;
    keygen)
        generate_encryption_key
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Comando desconocido: $1"
        show_help
        exit 1
        ;;
esac
