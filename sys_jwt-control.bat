@echo off
echo JWT Control for Microservices System
echo ====================================
echo.

:menu
echo Choose JWT Mode:
echo 1. Development Mode (JWT Bypass Enabled)
echo 2. Production Mode (Full JWT Required)
echo 3. Custom Mock User Settings
echo 4. Check Current Settings
echo 5. Exit
echo.
set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" goto dev_mode
if "%choice%"=="2" goto prod_mode
if "%choice%"=="3" goto custom_mock
if "%choice%"=="4" goto check_settings
if "%choice%"=="5" goto exit
goto menu

:dev_mode
echo.
echo Setting Development Mode (JWT Bypass Enabled)...
echo.

for %%d in (gateway-service users-service orders-service) do (
    echo Updating %%d...
    cd %%d
    echo APP_ENV=local > .env.temp
    echo APP_DEBUG=true >> .env.temp
    echo JWT_BYPASS=true >> .env.temp
    echo JWT_MOCK_USER_ROLE=admin >> .env.temp
    type .env >> .env.temp
    move .env.temp .env
    cd ..
)

echo.
echo ✅ Development Mode Enabled!
echo - JWT bypass is active
echo - Mock admin user will be used
echo - No authentication required
echo.
pause
goto menu

:prod_mode
echo.
echo Setting Production Mode (Full JWT Required)...
echo.

for %%d in (gateway-service users-service orders-service) do (
    echo Updating %%d...
    cd %%d
    echo APP_ENV=production > .env.temp
    echo APP_DEBUG=false >> .env.temp
    echo JWT_BYPASS=false >> .env.temp
    type .env >> .env.temp
    move .env.temp .env
    cd ..
)

echo.
echo ✅ Production Mode Enabled!
echo - Full JWT authentication required
echo - All security measures active
echo - No bypass available
echo.
pause
goto menu

:custom_mock
echo.
echo Custom Mock User Settings
echo =========================
echo.

set /p mock_id="Mock User ID (default: 1): "
set /p mock_name="Mock User Name (default: Dev User): "
set /p mock_email="Mock User Email (default: dev@localhost.com): "
set /p mock_role="Mock User Role (user/moderator/admin/superadmin, default: admin): "

if "%mock_id%"=="" set mock_id=1
if "%mock_name%"=="" set mock_name=Dev User
if "%mock_email%"=="" set mock_email=dev@localhost.com
if "%mock_role%"=="" set mock_role=admin

echo.
echo Applying custom mock user settings...

for %%d in (gateway-service users-service orders-service) do (
    echo Updating %%d...
    cd %%d
    echo APP_ENV=local > .env.temp
    echo APP_DEBUG=true >> .env.temp
    echo JWT_BYPASS=true >> .env.temp
    echo JWT_MOCK_USER_ID=%mock_id% >> .env.temp
    echo JWT_MOCK_USER_NAME=%mock_name% >> .env.temp
    echo JWT_MOCK_USER_EMAIL=%mock_email% >> .env.temp
    echo JWT_MOCK_USER_ROLE=%mock_role% >> .env.temp
    type .env >> .env.temp
    move .env.temp .env
    cd ..
)

echo.
echo ✅ Custom Mock User Applied!
echo - ID: %mock_id%
echo - Name: %mock_name%
echo - Email: %mock_email%
echo - Role: %mock_role%
echo.
pause
goto menu

:check_settings
echo.
echo Current JWT Settings
echo ===================
echo.

for %%d in (gateway-service users-service orders-service) do (
    echo %%d:
    cd %%d
    if exist .env (
        findstr /C:"APP_ENV" .env
        findstr /C:"APP_DEBUG" .env
        findstr /C:"JWT_BYPASS" .env
        findstr /C:"JWT_MOCK_USER" .env
    ) else (
        echo   .env file not found
    )
    cd ..
    echo.
)
pause
goto menu

:exit
echo.
echo Goodbye!
pause
exit
