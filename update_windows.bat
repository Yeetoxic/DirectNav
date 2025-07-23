@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"
echo === Updating DirectNav from GitHub ZIP ===

:: Download ZIP
curl -L -o update_tmp.zip https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip

:: Extract
powershell -Command "Expand-Archive -Force 'update_tmp.zip' 'update_tmp'"

:: === Update core files ===
echo • Updating core files...
xcopy /Y /I /Q "update_tmp\DirectNav-main\README.md" "README.md"
xcopy /Y /I /Q "update_tmp\DirectNav-main\docker-compose.yml" "docker-compose.yml"
xcopy /Y /I /Q "update_tmp\DirectNav-main\update_windows.bat" "update_windows.bat"
xcopy /Y /I /Q "update_tmp\DirectNav-main\update_linux.sh" "update_linux.sh"
xcopy /Y /I /Q "update_tmp\DirectNav-main\setup_windows.bat" "setup_windows.bat"
xcopy /Y /I /Q "update_tmp\DirectNav-main\setup_linux.sh" "setup_linux.sh"

:: Update docker folder
xcopy /E /Y /I "update_tmp\DirectNav-main\docker" "docker"

:: === Update app/index.php & zDirectNav only ===
echo • Updating /app core files...
xcopy /Y /I /Q "update_tmp\DirectNav-main\app\index.php" "app\index.php"
xcopy /E /Y /I "update_tmp\DirectNav-main\app\zDirectNav" "app\zDirectNav"

:: Cleanup
rmdir /S /Q update_tmp
del update_tmp.zip

:: Rebuild Docker
docker compose down
docker compose up --build -d

echo ✅ Update complete!
pause
