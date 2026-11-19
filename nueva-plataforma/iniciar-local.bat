@echo off
REM ==========================================================================
REM  INEES Mantenimientos - arranque local (Spring Boot + Angular + MariaDB)
REM
REM  Uso:
REM    iniciar-local.bat            Arranca BD + backend (sirve el frontend ya compilado)
REM    iniciar-local.bat dev        Igual, y ademas "ng serve" en http://localhost:4200
REM                                 con recarga automatica al editar el frontend
REM    iniciar-local.bat build      Recompila el frontend antes de arrancar
REM    iniciar-local.bat ayuda      Muestra esta ayuda
REM
REM  Configuracion opcional: copie config-local.ejemplo.bat como config-local.bat
REM  y ponga ahi contrasenas y rutas (ese archivo no se sube al repositorio).
REM  Para detener todo: detener-local.bat
REM ==========================================================================
setlocal EnableExtensions EnableDelayedExpansion
title INEES - Arranque local
set "RAIZ=%~dp0"
if "%RAIZ:~-1%"=="\" set "RAIZ=%RAIZ:~0,-1%"
set "MODO=%~1"
if "%MODO%"=="" set "MODO=normal"
if /i "%MODO%"=="ayuda" goto :ayuda
if /i "%MODO%"=="help" goto :ayuda
if /i "%MODO%"=="/?" goto :ayuda
if /i not "%MODO%"=="normal" if /i not "%MODO%"=="dev" if /i not "%MODO%"=="build" (
  echo Modo desconocido: %MODO%
  goto :ayuda
)

echo.
echo ===============================================================
echo   INEES Mantenimientos - arranque local  [modo: %MODO%]
echo ===============================================================

REM --------------------------------------------------------------- 0. Configuracion
if exist "%RAIZ%\config-local.bat" (
  echo [config] Cargando config-local.bat
  call "%RAIZ%\config-local.bat"
) else (
  echo [config] Sin config-local.bat: se usan los valores por defecto
  echo          ^(BD root sin clave en localhost:3306, puerto 8080^).
)
if not defined PORT set "PORT=8080"
if not defined DB_HOST set "DB_HOST=localhost"
if not defined DB_PORT set "DB_PORT=3306"
if not defined DB_NAME set "DB_NAME=inees_mantenimientos"
if not defined DB_USER set "DB_USER=root"
if not defined INEES_LOCAL set "INEES_LOCAL=%USERPROFILE%\inees-local"
if not defined LEGACY_ROOT set "LEGACY_ROOT=%RAIZ%\.."

REM --------------------------------------------------------------- 1. Java 21
set "JAVA_OK="
if defined JAVA_HOME if exist "%JAVA_HOME%\bin\java.exe" set "JAVA_OK=1"
if not defined JAVA_OK (
  for /d %%D in ("%INEES_LOCAL%\jdk-21*") do (
    if exist "%%~fD\bin\java.exe" (
      set "JAVA_HOME=%%~fD"
      set "JAVA_OK=1"
    )
  )
)
if defined JAVA_OK (
  set "PATH=!JAVA_HOME!\bin;!PATH!"
) else (
  where java >nul 2>&1 && set "JAVA_OK=1"
)
if not defined JAVA_OK (
  echo.
  echo [ERROR] No se encontro Java 21.
  echo         Instale el JDK 21 ^(por ejemplo Eclipse Temurin 21: https://adoptium.net^)
  echo         o descomprimalo en "%INEES_LOCAL%\jdk-21..." y vuelva a ejecutar.
  goto :fallo
)
set "JVER="
for /f "tokens=3" %%V in ('java -version 2^>^&1 ^| findstr /i "version"') do set "JVER=%%~V"
for /f "tokens=1 delims=." %%M in ("!JVER!") do set "JMAYOR=%%M"
if defined JMAYOR if !JMAYOR! LSS 21 (
  echo [ERROR] Java !JVER! es muy antiguo: se necesita Java 21 o superior.
  goto :fallo
)
echo [java]   Java !JVER! listo

REM --------------------------------------------------------------- 2. Maven
set "MVN="
for /f "delims=" %%M in ('where mvn.cmd 2^>nul') do if not defined MVN set "MVN=%%M"
if not defined MVN (
  for /r "%USERPROFILE%\.m2\wrapper\dists" %%M in (mvn.cmd) do if exist "%%M" if not defined MVN set "MVN=%%M"
)
if not defined MVN (
  for /d %%D in ("%INEES_LOCAL%\apache-maven-*") do if exist "%%~fD\bin\mvn.cmd" set "MVN=%%~fD\bin\mvn.cmd"
)
if not defined MVN (
  echo.
  echo [maven]  No se encontro Maven.
  choice /c SN /m "         Descargar Maven 3.9.9 en %INEES_LOCAL% ahora"
  if errorlevel 2 (
    echo          Instale Maven ^(https://maven.apache.org^) o agreguelo al PATH y vuelva a ejecutar.
    goto :fallo
  )
  if not exist "%INEES_LOCAL%" mkdir "%INEES_LOCAL%"
  powershell -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $z=Join-Path $env:INEES_LOCAL 'maven.zip'; Invoke-WebRequest -UseBasicParsing 'https://archive.apache.org/dist/maven/maven-3/3.9.9/binaries/apache-maven-3.9.9-bin.zip' -OutFile $z; Expand-Archive $z -DestinationPath $env:INEES_LOCAL -Force; Remove-Item $z"
  if errorlevel 1 (
    echo [ERROR] No se pudo descargar Maven. Revise la conexion a internet.
    goto :fallo
  )
  set "MVN=%INEES_LOCAL%\apache-maven-3.9.9\bin\mvn.cmd"
)
echo [maven]  !MVN!

REM --------------------------------------------------------------- 3. Base de datos
call :puerto_escuchando %DB_PORT%
if defined ESCUCHA (
  echo [bd]     Ya hay un servidor de base de datos en el puerto %DB_PORT%
) else (
  call :iniciar_bd
  if errorlevel 1 goto :fallo
)
call :verificar_bd

REM --------------------------------------------------------------- 4. Frontend
set "STATIC=%RAIZ%\backend\src\main\resources\static\index.html"
set "NECESITA_NODE="
if /i "%MODO%"=="dev" set "NECESITA_NODE=1"
if /i "%MODO%"=="build" set "NECESITA_NODE=1"
if not exist "%STATIC%" set "NECESITA_NODE=1"
if defined NECESITA_NODE (
  where npm.cmd >nul 2>&1
  if errorlevel 1 (
    echo.
    echo [ERROR] Se necesita Node.js 20 o superior para el frontend: https://nodejs.org
    goto :fallo
  )
  if not exist "%RAIZ%\frontend\node_modules" (
    echo [front]  Instalando dependencias de Angular ^(npm ci, solo la primera vez^)...
    pushd "%RAIZ%\frontend"
    call npm.cmd ci
    set "ERR=!errorlevel!"
    popd
    if not "!ERR!"=="0" (
      echo [ERROR] Fallo npm ci.
      goto :fallo
    )
  )
)
set "COMPILAR="
if /i "%MODO%"=="build" set "COMPILAR=1"
if not exist "%STATIC%" set "COMPILAR=1"
if defined COMPILAR (
  echo [front]  Compilando Angular ^(ng build^)...
  pushd "%RAIZ%\frontend"
  call npx.cmd ng build
  set "ERR=!errorlevel!"
  popd
  if not "!ERR!"=="0" (
    echo [ERROR] Fallo la compilacion del frontend.
    goto :fallo
  )
) else (
  echo [front]  Usando el frontend ya compilado ^(use "build" para recompilarlo^)
)

REM --------------------------------------------------------------- 5. Backend
call :puerto_escuchando %PORT%
if defined ESCUCHA (
  echo [back]   Ya hay algo escuchando en el puerto %PORT%: se asume que el backend esta corriendo.
  echo          Si no es asi, ejecute detener-local.bat y vuelva a intentarlo.
) else (
  echo [back]   Arrancando Spring Boot en una ventana nueva ^(la primera vez descarga dependencias^)...
  start "INEES Backend" "%RAIZ%\scripts\_backend.bat"
)
echo [back]   Esperando a que responda http://localhost:%PORT% ...
set /a INTENTOS=0
:esperar_backend
set "CODIGO="
for /f %%C in ('curl.exe -s -o nul -w "%%{http_code}" http://localhost:%PORT%/api/sesion 2^>nul') do set "CODIGO=%%C"
if "!CODIGO!"=="200" goto :backend_listo
set /a INTENTOS+=1
if !INTENTOS! GEQ 150 (
  echo.
  echo [ERROR] El backend no respondio en 5 minutos. Revise la ventana "INEES Backend".
  goto :fallo
)
timeout /t 2 /nobreak >nul
goto :esperar_backend
:backend_listo
echo [back]   Backend listo

REM --------------------------------------------------------------- 6. Modo dev
if /i "%MODO%"=="dev" (
  call :puerto_escuchando 4200
  if defined ESCUCHA (
    echo [front]  ng serve ya esta corriendo en el puerto 4200
    start "" "http://localhost:4200"
  ) else (
    echo [front]  Arrancando ng serve en una ventana nueva ^(se abre solo al terminar de compilar^)...
    start "INEES Frontend" "%RAIZ%\scripts\_frontend.bat"
  )
  set "URL=http://localhost:4200"
) else (
  set "URL=http://localhost:%PORT%"
  start "" "!URL!"
)

echo.
echo ===============================================================
echo   Listo:  !URL!
echo   Backend: ventana "INEES Backend"   ^|   Detener: detener-local.bat
echo ===============================================================
echo.
endlocal
exit /b 0

REM ======================================================================
REM  Subrutinas
REM ======================================================================

:puerto_escuchando
set "ESCUCHA="
for /f "tokens=1" %%L in ('netstat -ano -p tcp ^| findstr /r /c:":%~1 .*LISTENING"') do set "ESCUCHA=1"
exit /b 0

:iniciar_bd
set "BD_EXE="
set "BD_ARGS="
if defined DB_SERVIDOR_EXE (
  set "BD_EXE=%DB_SERVIDOR_EXE%"
  set "BD_ARGS=%DB_SERVIDOR_ARGS%"
)
if not defined BD_EXE (
  for /d %%D in ("%INEES_LOCAL%\mariadb-*") do (
    if exist "%%~fD\bin\mariadbd.exe" if exist "%INEES_LOCAL%\mariadb-data" (
      set "BD_EXE=%%~fD\bin\mariadbd.exe"
      set "BD_ARGS=--datadir="%INEES_LOCAL%\mariadb-data" --port=%DB_PORT% --console"
    )
  )
)
if not defined BD_EXE if exist "C:\xampp\mysql\bin\mysqld.exe" (
  set "BD_EXE=C:\xampp\mysql\bin\mysqld.exe"
  set "BD_ARGS=--defaults-file="C:\xampp\mysql\bin\my.ini" --standalone"
)
if not defined BD_EXE (
  echo.
  echo [ERROR] No hay una base de datos escuchando en el puerto %DB_PORT% y no se encontro
  echo         MariaDB en "%INEES_LOCAL%" ni XAMPP en C:\xampp.
  echo         Inicie MySQL/MariaDB manualmente ^(por ejemplo desde el panel de XAMPP^) o
  echo         defina DB_SERVIDOR_EXE en config-local.bat. Ver documentacion\02-instalacion-y-ejecucion-local.md
  exit /b 1
)
echo [bd]     Arrancando base de datos: !BD_EXE!
start "INEES Base de datos" /min "!BD_EXE!" !BD_ARGS!
set /a ESPERA=0
:esperar_bd
call :puerto_escuchando %DB_PORT%
if defined ESCUCHA (
  echo [bd]     Base de datos lista en el puerto %DB_PORT%
  exit /b 0
)
set /a ESPERA+=1
if !ESPERA! GEQ 30 (
  echo [ERROR] La base de datos no arranco en 30 segundos. Revise la ventana "INEES Base de datos".
  exit /b 1
)
timeout /t 1 /nobreak >nul
goto :esperar_bd

:verificar_bd
set "CLIENTE="
for /d %%D in ("%INEES_LOCAL%\mariadb-*") do if exist "%%~fD\bin\mysql.exe" set "CLIENTE=%%~fD\bin\mysql.exe"
if not defined CLIENTE if exist "C:\xampp\mysql\bin\mysql.exe" set "CLIENTE=C:\xampp\mysql\bin\mysql.exe"
if not defined CLIENTE for /f "delims=" %%M in ('where mysql.exe 2^>nul') do if not defined CLIENTE set "CLIENTE=%%M"
if not defined CLIENTE (
  echo [bd]     ^(No se encontro el cliente mysql.exe; se omite la verificacion de la base^)
  exit /b 0
)
set "CLAVE_ARG="
if defined DB_PASS set "CLAVE_ARG=-p%DB_PASS%"
"!CLIENTE!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% !CLAVE_ARG! -N -e "SELECT COUNT(*) FROM %DB_NAME%.rutas" >nul 2>&1
if errorlevel 1 (
  echo.
  echo [AVISO] No se pudo leer %DB_NAME%.rutas con el usuario %DB_USER%.
  echo         Verifique que la base exista y que DB_USER/DB_PASS sean correctos ^(config-local.bat^).
  echo         Para crearla desde un respaldo: ver documentacion\02-instalacion-y-ejecucion-local.md
  echo.
) else (
  echo [bd]     Base %DB_NAME% accesible
)
exit /b 0

:ayuda
echo.
echo  iniciar-local.bat [normal ^| dev ^| build ^| ayuda]
echo.
echo    normal  ^(por defecto^) Base de datos + backend en http://localhost:8080
echo            sirviendo el frontend ya compilado. Si nunca se compilo, lo compila.
echo    dev     Ademas abre "ng serve" en http://localhost:4200: los cambios del
echo            frontend se ven al guardar. Use esto para desarrollar.
echo    build   Recompila el frontend ^(ng build^) y luego arranca como "normal".
echo.
echo  Configuracion: copie config-local.ejemplo.bat a config-local.bat.
echo  Detener todo:  detener-local.bat   ^(o "detener-local.bat todo" para incluir la BD^)
echo.
endlocal
exit /b 0

:fallo
echo.
echo No se pudo completar el arranque. Corrija el error indicado y vuelva a ejecutar.
echo.
pause
endlocal
exit /b 1
