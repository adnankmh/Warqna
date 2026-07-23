@echo off
setlocal
cd /d "%~dp0\..\..\.."
where py >nul 2>nul
if %errorlevel%==0 (
  py -3 tools\clean_repository_runtime_files.py --apply
) else (
  python tools\clean_repository_runtime_files.py --apply
)
if errorlevel 1 goto :fail
call CHECK_WARQNA_WINDOWS.bat
if errorlevel 1 goto :fail
echo.
echo Warqnaa V187 repository cleanup and validation completed successfully.
pause
exit /b 0
:fail
echo.
echo Warqnaa V187 cleanup or validation failed. Review the message above.
pause
exit /b 1
