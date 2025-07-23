@echo off
echo 🔄 Updating DirectNav...

cd /d "%~dp0"

:: Check for Git
where git >nul 2>&1
if errorlevel 1 (
    echo ❌ Git is not installed or not in PATH. Aborting.
    pause
    exit /b 1
)

:: Stash changes that aren't in /app
echo 📦 Backing up uncommitted changes (excluding /app)...

:: Use git ls-files to safely stash only non-/app changes
for /f "delims=" %%f in ('git status --porcelain ^| findstr /V /C:" M app/"') do (
    git stash push -m "Auto-stash before update" >nul
    goto :skip_pull
)

:skip_pull
echo ⬇️ Pulling latest updates...
git pull origin main || git pull origin master

echo 🔁 Reapplying stashed changes (if any)...
git stash pop || echo ✅ No stashed changes to reapply.

:: Docker support
if exist docker-compose.yml (
    echo 🐳 Rebuilding Docker containers...
    docker compose down
    docker compose up -d --build
)

echo ✅ DirectNav update complete. Files in /app were preserved.
pause
