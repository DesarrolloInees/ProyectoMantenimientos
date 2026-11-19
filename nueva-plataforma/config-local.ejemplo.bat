@echo off
REM ==========================================================================
REM  Configuracion local de INEES Mantenimientos.
REM  1. Copie este archivo como config-local.bat (en esta misma carpeta).
REM  2. Descomente (quite "REM ") y ajuste solo lo que necesite.
REM  config-local.bat NO se sube al repositorio: aqui pueden ir contrasenas.
REM  Todas las variables estan explicadas en documentacion\03-configuracion.md
REM ==========================================================================

REM ---- Base de datos (por defecto: root sin clave en localhost:3306) ----
REM set "DB_HOST=localhost"
REM set "DB_PORT=3306"
REM set "DB_NAME=inees_mantenimientos"
REM set "DB_USER=root"
REM set "DB_PASS="

REM ---- Servidor de base de datos que arranca iniciar-local.bat si no esta corriendo ----
REM Por defecto busca MariaDB portable en %USERPROFILE%\inees-local y luego XAMPP en C:\xampp.
REM set "INEES_LOCAL=%USERPROFILE%\inees-local"
REM set "DB_SERVIDOR_EXE=C:\xampp\mysql\bin\mysqld.exe"
REM set "DB_SERVIDOR_ARGS=--defaults-file=C:\xampp\mysql\bin\my.ini --standalone"

REM ---- Puerto del backend ----
REM set "PORT=8080"

REM ---- Carpeta raiz del proyecto PHP (uploads/, app/uploads/, imagenes/) ----
REM Por defecto es la carpeta que contiene a nueva-plataforma.
REM set "LEGACY_ROOT=C:\xampp\htdocs\ProyectoMantenimientos"

REM ---- Correo (recuperar contrasena, solicitudes de inventario, alertas) ----
REM set "SMTP_HOST=smtp.gmail.com"
REM set "SMTP_PORT=465"
REM set "SMTP_USER=ineesmensajesautomaticos@gmail.com"
REM set "SMTP_PASS=clave-de-aplicacion"

REM ---- Servicios externos ----
REM set "TELEGRAM_BOT_TOKEN="
REM set "TELEGRAM_CHAT_ID="
REM set "GOOGLE_MAPS_KEY="
REM set "GROQ_API_KEY="
REM set "GROQ_API_KEY_2="
REM set "CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe"

REM ---- Tareas programadas (dejar apagadas en local para no enviar alertas reales) ----
REM set "CRON_ALERTAS=false"
REM set "CRON_TELEGRAM=false"

REM ---- Java (si no esta en JAVA_HOME ni en %USERPROFILE%\inees-local\jdk-21...) ----
REM set "JAVA_HOME=C:\Program Files\Eclipse Adoptium\jdk-21"
