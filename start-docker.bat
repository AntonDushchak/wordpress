@echo off
echo ========================================
echo WordPress Docker Launcher
echo ========================================
echo.

REM Check Docker
echo [1/4] Checking Docker...
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ✗ Docker is not installed!
    echo Install Docker Desktop: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)
echo ✓ Docker found

docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ✗ Docker Compose not found!
    echo Install Docker Desktop with Docker Compose
    pause
    exit /b 1
)
echo ✓ Docker Compose found

echo.
echo [2/4] Stopping existing containers...
docker-compose down

echo.
echo [3/4] Starting WordPress...
docker-compose up -d

echo.
echo [4/4] Waiting for services to start...
timeout /t 10 /nobreak >nul

echo.
echo ========================================
echo WordPress is running!
echo ========================================
echo.
echo WordPress: http://localhost:8080
echo phpMyAdmin: http://localhost:8081
echo.
echo phpMyAdmin login credentials:
echo Username: root
echo Password: root_password
echo.
echo To stop: docker-compose down
echo To view logs: docker-compose logs -f
echo.
pause
