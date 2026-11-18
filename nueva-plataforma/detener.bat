@echo off
REM Detiene el backend y el frontend de la plataforma nueva (MariaDB se deja corriendo:
REM la usa tambien la aplicacion PHP; para detenerla use C:\Users\PERSONAL\inees-local\detener.bat).
echo Deteniendo backend y frontend...
powershell -NoProfile -Command "Get-CimInstance Win32_Process | Where-Object { ($_.Name -eq 'java.exe' -and $_.CommandLine -like '*plataforma-0.0.1-SNAPSHOT.jar*') -or ($_.Name -eq 'node.exe' -and $_.CommandLine -like '*nueva-plataforma*' -and $_.CommandLine -like '*serve*') } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }"
echo Listo.
