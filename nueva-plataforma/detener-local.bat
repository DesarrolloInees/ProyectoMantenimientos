@echo off
REM ==========================================================================
REM  INEES Mantenimientos - detiene lo que arranco iniciar-local.bat
REM
REM    detener-local.bat         Detiene backend (puerto 8080) y ng serve (4200)
REM    detener-local.bat todo    Ademas apaga la base de datos local (puerto 3306)
REM
REM  Busca los procesos por el puerto en que escuchan, asi no cierra otros
REM  programas Java o Node que tenga abiertos.
REM ==========================================================================
setlocal EnableExtensions EnableDelayedExpansion
set "RAIZ=%~dp0"
if "%RAIZ:~-1%"=="\" set "RAIZ=%RAIZ:~0,-1%"
if exist "%RAIZ%\config-local.bat" call "%RAIZ%\config-local.bat"
if not defined PORT set "PORT=8080"
if not defined DB_PORT set "DB_PORT=3306"
if not defined DB_HOST set "DB_HOST=localhost"
if not defined DB_USER set "DB_USER=root"
if not defined INEES_LOCAL set "INEES_LOCAL=%USERPROFILE%\inees-local"

call :matar_puerto %PORT% "Backend Spring Boot"
call :matar_puerto 4200 "Frontend ng serve"
REM Cierra las ventanas que quedaron en "pause"
taskkill /fi "WINDOWTITLE eq INEES Backend*" /t /f >nul 2>&1
taskkill /fi "WINDOWTITLE eq INEES Frontend*" /t /f >nul 2>&1

if /i "%~1"=="todo" call :apagar_bd

echo.
echo Listo.
endlocal
exit /b 0

:matar_puerto
set "ENCONTRADO="
for /f "tokens=5" %%P in ('netstat -ano -p tcp ^| findstr /r /c:":%~1 .*LISTENING"') do (
  if not "%%P"=="0" (
    taskkill /pid %%P /t /f >nul 2>&1
    set "ENCONTRADO=1"
  )
)
if defined ENCONTRADO (echo [ok]  %~2 detenido) else (echo [--]  %~2 no estaba corriendo)
exit /b 0

:apagar_bd
set "ADMIN="
for /d %%D in ("%INEES_LOCAL%\mariadb-*") do if exist "%%~fD\bin\mysqladmin.exe" set "ADMIN=%%~fD\bin\mysqladmin.exe"
if not defined ADMIN if exist "C:\xampp\mysql\bin\mysqladmin.exe" set "ADMIN=C:\xampp\mysql\bin\mysqladmin.exe"
if not defined ADMIN (
  echo [--]  No se encontro mysqladmin.exe; apague la base de datos manualmente.
  exit /b 0
)
set "CLAVE_ARG="
if defined DB_PASS set "CLAVE_ARG=-p%DB_PASS%"
"!ADMIN!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% !CLAVE_ARG! shutdown >nul 2>&1
if errorlevel 1 (
  echo [--]  La base de datos no respondio a shutdown ^(quiza ya estaba apagada^).
) else (
  echo [ok]  Base de datos apagada
)
taskkill /fi "WINDOWTITLE eq INEES Base de datos*" /f >nul 2>&1
exit /b 0
