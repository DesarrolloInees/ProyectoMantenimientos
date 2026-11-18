# Descarga el mapa de Colombia y lo prepara para OSRM (una vez, y cuando se quiera actualizar el mapa).
# Requiere Docker. Tarda unos 15-30 minutos y necesita ~6 GB de RAM libres y ~3 GB de disco.
#   powershell -ExecutionPolicy Bypass -File preparar.ps1
$ErrorActionPreference = 'Stop'
$imagen = 'ghcr.io/project-osrm/osrm-backend:v5.27.1'
$datos = Join-Path $PSScriptRoot 'datos'
New-Item -ItemType Directory -Force $datos | Out-Null

$pbf = Join-Path $datos 'colombia-latest.osm.pbf'
if (-not (Test-Path $pbf)) {
    Write-Host 'Descargando el mapa de Colombia (Geofabrik, ~300 MB)...'
    $ProgressPreference = 'SilentlyContinue'
    Invoke-WebRequest 'https://download.geofabrik.de/south-america/colombia-latest.osm.pbf' -OutFile $pbf
}

# Perfil de carro: es lo más cercano a una moto que trae OSRM (respeta sentidos viales y vías no transitables).
Write-Host 'Extrayendo vías...'
docker run --rm -t -v "${datos}:/data" $imagen osrm-extract -p /opt/car.lua /data/colombia-latest.osm.pbf
if ($LASTEXITCODE -ne 0) { throw 'osrm-extract falló' }
Write-Host 'Particionando...'
docker run --rm -t -v "${datos}:/data" $imagen osrm-partition /data/colombia-latest.osrm
if ($LASTEXITCODE -ne 0) { throw 'osrm-partition falló' }
Write-Host 'Personalizando...'
docker run --rm -t -v "${datos}:/data" $imagen osrm-customize /data/colombia-latest.osrm
if ($LASTEXITCODE -ne 0) { throw 'osrm-customize falló' }

Write-Host ''
Write-Host 'Listo. Ahora: docker compose up -d   (en esta carpeta)'
Write-Host 'Prueba: http://localhost:5000/route/v1/driving/-74.0721,4.7110;-74.0570,4.6760'
