@echo off
setlocal

:: Set console title and color
title DirectNav Docker Setup
color 0A

:: Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker does not seem to be running.
    pause
    exit /b 1
)

:: Create app folder if not exists
if not exist "app" (
    mkdir app
    echo Created app folder.
)

:: Build and start the container
echo Starting Docker container...
docker-compose up --build -d

echo.
echo [SUCCESS] The container is up and running!
echo Open one of the following in your browser:
echo    - http://localhost:9000 (for local development)
echo    - https://localhost:9443 (may show a certificate warning)
pause