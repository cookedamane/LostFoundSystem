@echo off
setlocal
set "ROOT=%~dp0"

echo Checking XAMPP MySQL on 127.0.0.1:3306...
"C:\xampp\mysql\bin\mysqladmin.exe" --host=127.0.0.1 --port=3306 --user=root ping >nul 2>&1
if errorlevel 1 (
  echo MySQL is stopped. Starting it in a separate window; account actions need MySQL to finish starting.
  start "Lost and Found MySQL - keep open" /D "C:\xampp" "C:\xampp\mysql_start.bat"
  echo Waiting up to 30 seconds for MySQL...
  for /L %%N in (1,1,30) do (
    "C:\xampp\mysql\bin\mysqladmin.exe" --host=127.0.0.1 --port=3306 --user=root ping >nul 2>&1
    if not errorlevel 1 goto mysql_ready
    timeout /t 1 /nobreak >nul
  )
  echo MySQL did not become ready. Check the XAMPP Control Panel and MySQL logs, then run this file again.
  pause
  exit /b 1
)

:mysql_ready
if not exist "%ROOT%ionic-app\www\index.html" (
  echo Phone app build is missing. Run build-phone-app.bat first.
  pause
  exit /b 1
)

start "Lost and Found API - keep open" /D "%ROOT%php-api" cmd /k "C:\xampp\php\php.exe -S 0.0.0.0:8000 -t public public\index.php"
start "Lost and Found Admin - keep open" /D "%ROOT%lostfound-admin" cmd /k "C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8001"
start "Lost and Found App - keep open" /D "%ROOT%ionic-app" cmd /k "C:\xampp\php\php.exe -S 0.0.0.0:4200 -t www router.php"

echo Started the API, admin, and app in separate windows.
echo Admin: http://127.0.0.1:8001
echo App (PC):            http://127.0.0.1:4200
echo App (phone):         use laptop IPv4 from ipconfig on port 4200
echo API:   http://127.0.0.1:8000/api/health
echo Phone API:           http://laptop-ip:8000/api
echo Wait for the API health check to work before signing in. Keep all server windows open.
pause
