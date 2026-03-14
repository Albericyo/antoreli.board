@echo off
REM pushd convertit les chemins UNC (\\wsl...) en lecteur temporaire (Z:, etc.)
pushd "%~dp0"
set "ROOT=%CD%"

echo ========================================
echo   Shooting Board - Demarrage
echo ========================================
echo.

REM Fenetre 1 : Serveur (ROOT = chemin compatible CMD, ex: Z:\)
start "Shooting Board - Serveur" cmd /k "cd /d ""%ROOT%"" && echo Serveur sur http://localhost:8765 && echo Fermez cette fenetre pour arreter. && echo. && (py -m http.server 8765 || python -m http.server 8765 || python3 -m http.server 8765)"

REM Courte pause pour laisser le serveur demarrer
timeout /t 1 /nobreak >nul

REM Ouvrir le navigateur
start http://localhost:8765

REM Fenetre 2 : Cette invite reste ouverte
echo L'application est ouverte dans votre navigateur.
echo Pour arreter : fermez la fenetre "Shooting Board - Serveur"
echo.
pause

popd 2>nul
