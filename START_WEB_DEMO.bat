@echo off
chcp 65001 >nul
cd /d "%~dp0web_demo"
echo.
echo ================================================
echo   Warqna v142 Premium Interactive Web Demo
echo ================================================
echo.
where python >nul 2>nul
if %errorlevel%==0 (
  start "" http://127.0.0.1:8088
  python -m http.server 8088
) else (
  echo Python is not installed. Opening the demo directly...
  start "" index.html
)
