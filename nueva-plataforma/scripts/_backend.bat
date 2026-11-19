@echo off
REM Ventana del backend. La abre iniciar-local.bat (hereda JAVA_HOME, MVN y las variables de config-local.bat).
REM Tambien se puede ejecutar sola si Java 21 y Maven estan en el PATH.
title INEES Backend
cd /d "%~dp0..\backend"
if not defined MVN set "MVN=mvn.cmd"
echo Arrancando Spring Boot (Ctrl+C para detenerlo)...
echo.
call "%MVN%" spring-boot:run
echo.
echo El backend se detuvo (codigo %errorlevel%). Revise los mensajes de arriba.
pause
