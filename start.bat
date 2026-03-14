@echo off
cd /d "%~dp0"

echo ========================================
echo   Shooting Board - Demarrage
echo ========================================
echo.

REM Demarrer le serveur dans une nouvelle fenetre
start "Shooting Board - Serveur" cmd /k "echo Serveur sur http://localhost:8765 & echo Fermez cette fenetre pour arreter. & echo. & (python -m http.server 8765 2>nul || python3 -m http.server 8765)"

REM Attendre que le serveur soit pret
timeout /t 2 /nobreak >nul

REM Ouvrir le navigateur
start http://localhost:8765

echo L'application a ete ouverte dans votre navigateur.
echo.
echo Pour arreter : fermez la fenetre "Shooting Board - Serveur"
echo.
pause
