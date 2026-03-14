@echo off
REM Fonctionne sur Windows : C:\, D:\, etc. (pushd gere aussi les chemins UNC)
pushd "%~dp0"
set "ROOT=%CD%"

echo ========================================
echo   Shooting Board - Demarrage
echo ========================================
echo.

REM Fenetre 1 : Serveur (Python ou Node.js)
start "Shooting Board - Serveur" cmd /k "cd /d ""%ROOT%"" && echo Serveur sur http://localhost:8765 && echo Fermez cette fenetre pour arreter. && echo. && (py -m http.server 8765 2>nul || python -m http.server 8765 2>nul || python3 -m http.server 8765 2>nul || (echo Lancement avec Node.js... && npx -y serve -l 8765 2>nul) || (echo. && echo ERREUR : Python ou Node.js requis. && echo Installez Python : https://python.org && echo ou Node.js : https://nodejs.org && echo. && pause))"

REM Courte pause pour laisser le serveur demarrer
timeout /t 2 /nobreak >nul

REM Ouvrir le navigateur
start http://localhost:8765

echo L'application est ouverte dans votre navigateur.
echo Pour arreter : fermez la fenetre "Shooting Board - Serveur"
echo.
pause

popd 2>nul
