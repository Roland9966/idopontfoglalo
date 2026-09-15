@echo off
setlocal
set "PROJECT_DIR=%~dp0.."
set "BACKUP_DIR=%PROJECT_DIR%\storage\backups"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f %%I in ('powershell.exe -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
if not defined STAMP (
    echo A mentes idobelyege nem hozhato letre.
    exit /b 1
)
set "OUTPUT_FILE=%BACKUP_DIR%\idopontfoglalo_%STAMP%.sql"

"C:\xampp\mysql\bin\mysqldump.exe" --host=127.0.0.1 --user=root --default-character-set=utf8mb4 --single-transaction --result-file="%OUTPUT_FILE%" idopontfoglalo
if errorlevel 1 (
    if exist "%OUTPUT_FILE%" del /q "%OUTPUT_FILE%"
    echo A mentes sikertelen.
    exit /b 1
)
echo A mentes elkeszult: %OUTPUT_FILE%
