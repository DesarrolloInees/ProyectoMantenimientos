@echo off
REM ============================================================
REM  Inicia la plataforma nueva en local:
REM    MariaDB (portable)  -> 127.0.0.1:3306
REM    Backend Spring Boot -> http://localhost:8081
REM    Frontend Angular    -> http://localhost:4310
REM ============================================================
setlocal
set RAIZ=%~dp0
set LOCAL=C:\Users\PERSONAL\inees-local
set MARIADB=%LOCAL%\mariadb-10.11.19-winx64\bin
set NODE=%LOCAL%\node-v24.22.2-win-x64
set JAVA_HOME=C:\Program Files\Java\jdk-21

REM --- MariaDB
"%MARIADB%\mariadb-admin.exe" -u root ping >nul 2>&1
if errorlevel 1 (
    echo Iniciando MariaDB...
    start "MariaDB INEES" /min "%MARIADB%\mariadbd.exe" --defaults-file="%LOCAL%\mariadb-data\my.ini" --console
    ping -n 6 127.0.0.1 >nul
) else (
    echo MariaDB ya esta corriendo.
)

REM --- Backend (compila la primera vez)
if not exist "%RAIZ%backend\target\plataforma-0.0.1-SNAPSHOT.jar" (
    echo Compilando el backend por primera vez...
    pushd "%RAIZ%backend"
    call mvnw.cmd -q -B -DskipTests package
    popd
)
echo Iniciando backend en http://localhost:8081 ...
start "Backend INEES" /min cmd /c "cd /d "%RAIZ%backend" && "%JAVA_HOME%\bin\java.exe" -jar target\plataforma-0.0.1-SNAPSHOT.jar"

REM --- Frontend (instala dependencias la primera vez)
set PATH=%NODE%;%PATH%
if not exist "%RAIZ%frontend\node_modules" (
    echo Instalando dependencias del frontend...
    pushd "%RAIZ%frontend"
    call npm install
    popd
)
echo Iniciando frontend en http://localhost:4310 ...
start "Frontend INEES" /min cmd /c "cd /d "%RAIZ%frontend" && npx ng serve --host localhost"

echo Esperando a que el backend responda...
set /a N=0
:esperar
ping -n 3 127.0.0.1 >nul
curl -s -o nul http://localhost:8081/api/catalogos
if not errorlevel 1 goto listo
set /a N+=1
if %N% lss 30 goto esperar

:listo
ping -n 8 127.0.0.1 >nul
start "" "http://localhost:4310"
echo.
echo Listo: http://localhost:4310
endlocal
