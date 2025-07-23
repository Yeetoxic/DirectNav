@echo off
setlocal enabledelayedexpansion

echo === Updating DirectNav from GitHub ZIP ===

set TMP_DIR=update_tmp
set ZIP_URL=https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip
set ZIP_FILE=main.zip

:: Clean previous temp
rmdir /s /q %TMP_DIR%
del /q %ZIP_FILE%

:: Create temp dir
mkdir %TMP_DIR%

:: Download latest
powershell -Command "Invoke-WebRequest -Uri '%ZIP_URL%' -OutFile '%ZIP_FILE%'"

:: Extract zip
powershell -Command "Expand-Archive -Path '%ZIP_FILE%' -DestinationPath '%TMP_DIR%'"

:: Copy updated root files
xcopy /Y /E /Q "%TMP_DIR%\DirectNav-main\docker\*" "docker\"
copy /Y "%TMP_DIR%\DirectNav-main\docker-compose.yml" .
copy /Y "%TMP_DIR%\DirectNav-main\README.md" .
copy /Y "%TMP_DIR%\DirectNav-main\update_linux.sh" .
copy /Y "%TMP_DIR%\DirectNav-main\update_windows.bat" .

:: Ensure target directory exists and copy zDirectNav contents
if not exist "app\zDirectNav" mkdir "app\zDirectNav"
xcopy /Y /E /Q "%TMP_DIR%\DirectNav-main\app\zDirectNav\*" "app\zDirectNav\"

:: Clean up
rmdir /s /q %TMP_DIR%
del /q %ZIP_FILE%

:: Rebuild docker
echo === Rebuilding Docker containers ===
docker compose down
docker compose up --build -d

echo ✓ Update complete
pause
