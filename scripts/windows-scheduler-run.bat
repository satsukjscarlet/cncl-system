@echo off
setlocal

set "PROJECT_DIR=%~dp0.."
set "PHP_BIN=php"
set "LOG_DIR=%PROJECT_DIR%\storage\logs"
set "LOG_FILE=%LOG_DIR%\windows-scheduler.log"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

cd /d "%PROJECT_DIR%"

echo [%date% %time%] Running Laravel scheduler >> "%LOG_FILE%"
"%PHP_BIN%" artisan schedule:run >> "%LOG_FILE%" 2>&1
set "EXIT_CODE=%ERRORLEVEL%"
echo [%date% %time%] Finished with code %EXIT_CODE% >> "%LOG_FILE%"

exit /b %EXIT_CODE%
