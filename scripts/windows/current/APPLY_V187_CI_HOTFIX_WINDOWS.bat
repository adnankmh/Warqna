@echo off
setlocal EnableExtensions
cd /d "%~dp0\..\..\.."

echo ============================================================
echo Warqnaa V0.3.6+187 - GitHub CI Hotfix
echo Secret-bearing .env + Composer lock consistency
echo ============================================================

set "PYTHON_CMD=python"
where py >nul 2>nul
if not errorlevel 1 set "PYTHON_CMD=py -3"

%PYTHON_CMD% tools\test_v187_ci_hotfix.py
if errorlevel 1 goto :failed

%PYTHON_CMD% tools\apply_v187_ci_hotfix.py
if errorlevel 1 goto :failed

echo.
echo [SUCCESS] Hotfix applied and clean FULL/PATCH ZIP files created.
echo Check the parent folder of Warqnaa for the generated archives.
echo Then review changes in GitHub Desktop, Commit, and Push.
pause
exit /b 0

:failed
echo.
echo [FAILED] The hotfix stopped safely. Read the error above.
pause
exit /b 1
