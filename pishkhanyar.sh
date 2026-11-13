#!/usr/bin/env bash
# ============================================================
# PishkhanYar - Project Management Script
# Usage: ./pishkhanyar.sh [command]
# ============================================================
set -euo pipefail

COMPOSE_FILE="docker-compose.yml"
PROJECT="pishkhanyar"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "${BLUE}"
    echo "╔══════════════════════════════════════╗"
    echo "║        سامانه پیشخوان‌یار             ║"
    echo "║   PishkhanYar Management Script      ║"
    echo "╚══════════════════════════════════════╝"
    echo -e "${NC}"
}

check_env() {
    if [ ! -f ".env" ]; then
        echo -e "${YELLOW}⚠️  File .env not found. Copying from .env.example ...${NC}"
        cp .env.example .env
        echo -e "${GREEN}✅ .env created. Please review and edit if needed.${NC}"
    fi
}

cmd_start() {
    print_header
    check_env
    echo -e "${GREEN}🚀 Starting PishkhanYar...${NC}"
    docker compose -f "$COMPOSE_FILE" up -d --build
    echo ""
    echo -e "${GREEN}✅ PishkhanYar is running!${NC}"
    echo -e "${BLUE}📌 App:        http://localhost:8670${NC}"
    echo -e "${BLUE}📌 phpMyAdmin: http://localhost:8672${NC}"
    echo -e "${BLUE}📌 MySQL:      localhost:8671${NC}"
}

cmd_stop() {
    echo -e "${YELLOW}🛑 Stopping PishkhanYar...${NC}"
    docker compose -f "$COMPOSE_FILE" down
    echo -e "${GREEN}✅ Stopped.${NC}"
}

cmd_restart() {
    cmd_stop
    cmd_start
}

cmd_logs() {
    local service="${1:-}"
    if [ -n "$service" ]; then
        docker compose -f "$COMPOSE_FILE" logs -f "$service"
    else
        docker compose -f "$COMPOSE_FILE" logs -f
    fi
}

cmd_status() {
    echo -e "${BLUE}📊 PishkhanYar Container Status:${NC}"
    docker compose -f "$COMPOSE_FILE" ps
}

cmd_shell() {
    local service="${1:-app}"
    echo -e "${BLUE}🔧 Opening shell in $service container...${NC}"
    docker compose -f "$COMPOSE_FILE" exec "$service" bash
}

cmd_db_import() {
    local sql_file="${1:-pishkhan-yar/database/schema.sql}"
    echo -e "${YELLOW}📥 Importing database from $sql_file ...${NC}"
    docker compose -f "$COMPOSE_FILE" exec -T db \
        mysql -u root -p"$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2)" \
        "$(grep DB_NAME .env | cut -d= -f2)" < "$sql_file"
    echo -e "${GREEN}✅ Database imported successfully.${NC}"
}

cmd_db_backup() {
    local backup_file="backup_$(date +%Y%m%d_%H%M%S).sql"
    echo -e "${YELLOW}💾 Backing up database to $backup_file ...${NC}"
    docker compose -f "$COMPOSE_FILE" exec -T db \
        mysqldump -u root -p"$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2)" \
        "$(grep DB_NAME .env | cut -d= -f2)" > "$backup_file"
    echo -e "${GREEN}✅ Backup saved to $backup_file${NC}"
}

cmd_reset_db() {
    echo -e "${RED}⚠️  WARNING: This will destroy ALL database data!${NC}"
    read -r -p "Are you sure? Type 'yes' to confirm: " confirm
    if [ "$confirm" = "yes" ]; then
        docker compose -f "$COMPOSE_FILE" down -v
        docker compose -f "$COMPOSE_FILE" up -d
        echo -e "${GREEN}✅ Database reset complete.${NC}"
    else
        echo -e "${YELLOW}❌ Cancelled.${NC}"
    fi
}

cmd_update() {
    echo -e "${BLUE}🔄 Updating PishkhanYar...${NC}"
    git pull origin main
    docker compose -f "$COMPOSE_FILE" build --no-cache app
    docker compose -f "$COMPOSE_FILE" up -d
    echo -e "${GREEN}✅ Update complete.${NC}"
}

cmd_help() {
    print_header
    echo "Commands:"
    echo ""
    echo -e "  ${GREEN}start${NC}           Start all services"
    echo -e "  ${GREEN}stop${NC}            Stop all services"
    echo -e "  ${GREEN}restart${NC}         Restart all services"
    echo -e "  ${GREEN}status${NC}          Show container status"
    echo -e "  ${GREEN}logs [service]${NC}  Show logs (optional: app|db|phpmyadmin)"
    echo -e "  ${GREEN}shell [service]${NC} Open shell in container (default: app)"
    echo -e "  ${GREEN}db-import [file]${NC} Import SQL file to database"
    echo -e "  ${GREEN}db-backup${NC}       Backup database to SQL file"
    echo -e "  ${GREEN}reset-db${NC}        Reset database (DESTRUCTIVE!)"
    echo -e "  ${GREEN}update${NC}          Pull latest code and rebuild"
    echo ""
    echo "Ports:"
    echo -e "  ${BLUE}App:        http://localhost:8670${NC}"
    echo -e "  ${BLUE}MySQL:      localhost:8671${NC}"
    echo -e "  ${BLUE}phpMyAdmin: http://localhost:8672${NC}"
}

# Main dispatcher
case "${1:-help}" in
    start)      cmd_start ;;
    stop)       cmd_stop ;;
    restart)    cmd_restart ;;
    logs)       cmd_logs "${2:-}" ;;
    status)     cmd_status ;;
    shell)      cmd_shell "${2:-app}" ;;
    db-import)  cmd_db_import "${2:-pishkhan-yar/database/schema.sql}" ;;
    db-backup)  cmd_db_backup ;;
    reset-db)   cmd_reset_db ;;
    update)     cmd_update ;;
    help|*)     cmd_help ;;
esac
