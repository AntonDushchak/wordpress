#!/bin/bash

echo "========================================"
echo "WordPress Docker Launcher"
echo "========================================"
echo

# Цвета
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Проверяем Docker
echo "[1/4] Проверка Docker..."
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker найден"
else
    echo -e "${RED}✗${NC} Docker не установлен!"
    echo "Установите Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

if command -v docker-compose &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker Compose найден"
else
    echo -e "${RED}✗${NC} Docker Compose не найден!"
    echo "Установите Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

echo
echo "[2/4] Остановка существующих контейнеров..."
docker-compose down

echo
echo "[3/4] Запуск WordPress..."
docker-compose up -d

echo
echo "[4/4] Ожидание запуска сервисов..."
sleep 10

echo
echo "========================================"
echo -e "${GREEN}WordPress запущен!${NC}"
echo "========================================"
echo
echo -e "${YELLOW}WordPress:${NC} http://localhost:8080"
echo -e "${YELLOW}phpMyAdmin:${NC} http://localhost:8081"
echo
echo "Данные для входа в phpMyAdmin:"
echo "Пользователь: root"
echo "Пароль: root_password"
echo
echo "Полезные команды:"
echo "  Остановка: docker-compose down"
echo "  Логи: docker-compose logs -f"
echo "  Статус: docker-compose ps"
echo
echo -e "${GREEN}Готово! 🚀${NC}"


