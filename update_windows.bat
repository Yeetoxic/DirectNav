@echo off
setlocal enabledelayedexpansion
set TMP_DIR=update_tmp
set ZIP_URL=https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip
set ZIP_FILE=main.zip
set LOG_FILE=update_log.txt
set SELF=%~f0
set NEW_UPDATER=update_new.bat
set VBS_SCRIPT=run_after_update.vbs

echo === DirectNav Auto-Updater ===

:: Clean last run
rmdir /s /q %TMP_DIR% >nul 2>&1
del /q %ZIP_FILE% %LOG_FILE% %NEW_UPDATER% %VBS_SCRIPT% >nul 2>&1
mkdir %TMP_DIR%

echo Stopping Docker containers...
docker compose down

echo Downloading latest DirectNav ZIP...
powershell -Command "Invoke-WebRequest -Uri '%ZIP_URL%' -OutFile '%ZIP_FILE%'"
if not exist "%ZIP_FILE%" (
    echo [ERROR] Download failed.
    pause
    exit /b 1
)

echo Extracting ZIP...
powershell -Command "Expand-Archive -Path '%ZIP_FILE%' -DestinationPath '%TMP_DIR%'"

set SRC=%TMP_DIR%\DirectNav-main
echo Update Summary: > %LOG_FILE%
echo ------------------------------ >> %LOG_FILE%

:: Copy safe top-level files (excluding self)
for %%F in (
    README.md
    docker-compose.yml
    setup_windows.bat
    setup_linux.sh
    update_linux.sh
    update_windows.bat
) do (
    if /i not "%%F"=="update_windows.bat" (
        call :copyFile "%SRC%\%%F" "%%F"
    ) else (
        copy /Y "%SRC%\%%F" "%NEW_UPDATER%" >nul
        echo [REPLACED] update_windows.bat >> %LOG_FILE%
    )
)

:: Copy docker folder
echo Updating docker/...
for /R "%SRC%\docker" %%F in (*) do (
    set "relative=%%F"
    set "relative=!relative:%SRC%\docker\=!"
    call :copyFile "%%F" "docker\!relative!"
)

:: Update app/zDirectNav and index.php
echo Updating app/zDirectNav/...
call :copyFile "%SRC%\app\index.php" "app\index.php"
for /R "%SRC%\app\zDirectNav" %%F in (*) do (
    set "relative=%%F"
    set "relative=!relative:%SRC%\app\zDirectNav\=!"
    call :copyFile "%%F" "app\zDirectNav\!relative!"
)

echo ------------------------------
type %LOG_FILE%
echo ------------------------------

echo.
echo Press ENTER to run setup and finalize update...
pause >nul

:: Determine script path
set SCRIPT_DIR=%~dp0

:: Write the post-update VBS script line by line (in the same folder)
> "%VBS_SCRIPT%" echo Set WshShell = CreateObject("WScript.Shell")
>> "%VBS_SCRIPT%" echo WScript.Sleep 1000
>> "%VBS_SCRIPT%" echo WshShell.Run Chr(34) ^& "%SCRIPT_DIR%setup_windows.bat" ^& Chr(34), 1, False
>> "%VBS_SCRIPT%" echo WScript.Sleep 2000
>> "%VBS_SCRIPT%" echo Set fso = CreateObject("Scripting.FileSystemObject")
>> "%VBS_SCRIPT%" echo If fso.FileExists("%SCRIPT_DIR%update_new.bat") Then
>> "%VBS_SCRIPT%" echo     fso.CopyFile "%SCRIPT_DIR%update_new.bat", "%SCRIPT_DIR%update_windows.bat", True
>> "%VBS_SCRIPT%" echo     fso.DeleteFile "%SCRIPT_DIR%update_new.bat"
>> "%VBS_SCRIPT%" echo End If
>> "%VBS_SCRIPT%" echo If fso.FolderExists("%SCRIPT_DIR%update_tmp") Then fso.DeleteFolder "%SCRIPT_DIR%update_tmp", True
>> "%VBS_SCRIPT%" echo If fso.FileExists("%SCRIPT_DIR%main.zip") Then fso.DeleteFile "%SCRIPT_DIR%main.zip"
>> "%VBS_SCRIPT%" echo fso.DeleteFile WScript.ScriptFullName


:: Run the VBS (it will wait for this script to exit)
start "" wscript "%VBS_SCRIPT%"
exit /b

:: Copy helper
:copyFile
set "src=%~1"
set "dst=%~2"
if not exist "%src%" exit /b
if exist "%dst%" (
    fc /b "%src%" "%dst%" >nul
    if errorlevel 1 (
        copy /Y "%src%" "%dst%" >nul
        echo [REPLACED] %dst% >> %LOG_FILE%
    )
) else (
    mkdir "%~dp2" >nul 2>&1
    copy "%src%" "%dst%" >nul
    echo [ADDED] %dst% >> %LOG_FILE%
)
exit /b
