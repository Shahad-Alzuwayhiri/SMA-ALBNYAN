@echo off
cd /d "C:\Users\Admin\Desktop\ContractSama\php_app\public"
echo Starting PHP server from: %CD%
echo Server will be available at: http://localhost:8080
echo.
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8080
pause