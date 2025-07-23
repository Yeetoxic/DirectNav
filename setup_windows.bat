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
echo Open http://localhost:9000 in your browser.
pause