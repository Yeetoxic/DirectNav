@echo off
setlocal EnableDelayedExpansion

:: =========================
:: === Config Variables ===
:: =========================
set "TMP_DIR=update_tmp"
set "ZIP_URL=https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip"
set "ZIP_FILE=main.zip"
set "LOG_FILE=update_log.txt"
set "SELF=%~f0"
set "NEW_UPDATER=update_new.bat"
set "VBS_SCRIPT=run_after_update.vbs"

echo === DirectNav Auto-Updater ===

:: Clean last run
rmdir /s /q "%TMP_DIR%" >nul 2>&1
del /q "%ZIP_FILE%" "%LOG_FILE%" "%NEW_UPDATER%" "%VBS_SCRIPT%" >nul 2>&1
mkdir "%TMP_DIR%"

echo Stopping Docker containers...
docker compose down

echo Downloading latest DirectNav ZIP...
powershell -Command "try { Invoke-WebRequest -Uri '%ZIP_URL%' -OutFile '%ZIP_FILE%' -UseBasicParsing } catch { $_.Exception.Message; exit 1 }"
if not exist "%ZIP_FILE%" (
    echo [ERROR] Download failed.
    pause
    exit /b 1
)

echo Extracting ZIP...
powershell -Command "Expand-Archive -Path '%ZIP_FILE%' -DestinationPath '%TMP_DIR%' -Force"

set "ROOT=%TMP_DIR%\DirectNav-main"
if not exist "%ROOT%\app" (
    echo [ERROR] Bad ROOT path: %ROOT%
    dir /b "%TMP_DIR%"
    pause
    exit /b 1
)

echo Update Summary: > "%LOG_FILE%"
echo ------------------------------ >> "%LOG_FILE%"

:: ---------------- Top-level files (exclude self) ----------------
for %%F in (
    README.md
    docker-compose.yml
    setup_windows.bat
    setup_linux.sh
    update_linux.sh
    update_windows.bat
) do (
    if /i not "%%F"=="update_windows.bat" (
        call :copyFile "%ROOT%\%%F" "%%F"
    ) else (
        copy /Y "%ROOT%\%%F" "%NEW_UPDATER%" >nul
        echo [REPLACED] update_windows.bat >> "%LOG_FILE%"
    )
)

:: ---------------- docker folder ----------------
echo Updating docker/...
set "BASE=%ROOT%\docker\"
if exist "%BASE%" (
    for /R "%BASE%" %%F in (*) do (
        set "abs=%%~fF"
        set "rel=!abs:%BASE%=!"
        if "!rel:~0,1!"=="\" set "rel=!rel:~1!"
        call :copyFile "%%~fF" "docker\!rel!"
    )
) else (
    echo [WARN] Missing folder: %ROOT%\docker >> "%LOG_FILE%"
)

:: ---------------- index.php ----------------
echo Updating app/index.php...
if exist "%ROOT%\app\index.php" (
    call :copyFile "%ROOT%\app\index.php" "app\index.php"
) else (
    echo [WARN] Missing: %ROOT%\app\index.php >> "%LOG_FILE%"
)

:: ---------------- zDirectNav folder ----------------
echo Updating app/zDirectNav/...
set "BASE=%ROOT%\app\zDirectNav\"
if exist "%BASE%" (
    for /R "%BASE%" %%F in (*) do (
        set "abs=%%~fF"
        set "rel=!abs:%BASE%=!"
        if "!rel:~0,1!"=="\" set "rel=!rel:~1!"
        call :copyFile "%%~fF" "app\zDirectNav\!rel!"
    )
) else (
    echo [WARN] Missing folder: %ROOT%\app\zDirectNav >> "%LOG_FILE%"
)

echo ------------------------------
type "%LOG_FILE%"
echo ------------------------------

echo.
echo Press ENTER to run setup and finalize update...
pause >nul

:: Post-update VBS script
> "%VBS_SCRIPT%" echo Set WshShell = CreateObject("WScript.Shell")
>> "%VBS_SCRIPT%" echo WScript.Sleep 1000
>> "%VBS_SCRIPT%" echo WshShell.Run "setup_windows.bat", 1, False
>> "%VBS_SCRIPT%" echo WScript.Sleep 2000
>> "%VBS_SCRIPT%" echo Set fso = CreateObject("Scripting.FileSystemObject")
>> "%VBS_SCRIPT%" echo If fso.FileExists("update_new.bat") Then
>> "%VBS_SCRIPT%" echo     fso.CopyFile "update_new.bat", "update_windows.bat", True
>> "%VBS_SCRIPT%" echo     fso.DeleteFile "update_new.bat"
>> "%VBS_SCRIPT%" echo End If
>> "%VBS_SCRIPT%" echo If fso.FolderExists("update_tmp") Then fso.DeleteFolder "update_tmp", True
>> "%VBS_SCRIPT%" echo If fso.FileExists("main.zip") Then fso.DeleteFile "main.zip"
>> "%VBS_SCRIPT%" echo fso.DeleteFile "run_after_update.vbs"

start "" wscript "%VBS_SCRIPT%"
exit /b 0

:: ======================
:: === Helper Methods ===
:: ======================

:copyFile
setlocal EnableDelayedExpansion
set "srcFile=%~1"
set "dstFile=%~2"

if not exist "%srcFile%" (
    >> "%LOG_FILE%" echo [MISS] %srcFile%
    endlocal & exit /b 0
)

ver >nul 2>&1  :: reset ERRORLEVEL

if exist "%dstFile%" (
    fc /b "%srcFile%" "%dstFile%" >nul
    if errorlevel 1 (
        copy /Y "%srcFile%" "%dstFile%" >nul
        >> "%LOG_FILE%" echo [REPLACED] %dstFile%
    )
) else (
    if not exist "%~dp2" mkdir "%~dp2" >nul 2>&1
    copy /Y "%srcFile%" "%dstFile%" >nul
    >> "%LOG_FILE%" echo [ADDED] %dstFile%
)
endlocal & exit /b 0
