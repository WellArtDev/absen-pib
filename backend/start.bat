@echo off
echo Starting AbsenPIB Backend...
echo.
echo API Server: http://localhost:8000
echo Dashboard:  http://localhost:8000/dashboard/
echo.
echo Default Login:
echo   Email: superadmin@absenpib.com
echo   Password: admin123
echo.
php -S localhost:8000 -t public
