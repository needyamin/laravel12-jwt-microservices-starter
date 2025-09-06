@echo off
echo Starting All Microservices...
echo.
echo This will start all three services in separate windows.
echo Make sure you have PHP and Composer installed.
echo.
pause

start "Gateway Service" cmd /k "cd gateway-service && php artisan serve --port=8000"
timeout /t 2 /nobreak >nul

start "Users Service" cmd /k "cd users-service && php artisan serve --port=8001"
timeout /t 2 /nobreak >nul

start "Orders Service" cmd /k "cd orders-service && php artisan serve --port=8002"

echo.
echo All services are starting...
echo Gateway Service: http://localhost:8000
echo Users Service: http://localhost:8001
echo Orders Service: http://localhost:8002
echo.
echo Press any key to exit this window...
pause >nul
