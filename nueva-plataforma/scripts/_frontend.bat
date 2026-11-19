@echo off
REM Ventana de "ng serve" (modo dev). La abre "iniciar-local.bat dev".
REM Sirve Angular en http://localhost:4200 y redirige /api y los archivos al backend (proxy.conf.json).
title INEES Frontend
cd /d "%~dp0..\frontend"
echo Arrancando ng serve (Ctrl+C para detenerlo)...
echo.
call npx.cmd ng serve --open
echo.
echo ng serve se detuvo (codigo %errorlevel%).
pause
