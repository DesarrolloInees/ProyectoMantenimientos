<#
  Prueba rápida de la plataforma en marcha (no modifica datos).
  1. Inicia sesión con el usuario indicado.
  2. Pide /api/sesion y recorre TODAS las páginas que ese usuario tiene permitidas con ?accion=index.
  3. Revisa que el frontend y los recursos estáticos se sirvan.
  4. Revisa que sin sesión la API responda 401.

  Uso (desde nueva-plataforma):
    powershell -ExecutionPolicy Bypass -File documentacion\herramientas\probar-plataforma.ps1 -Usuario admin -Clave "su-clave"
    ... -Url http://servidor:8080     (por defecto http://localhost:8080)

  Resultado por página:
    OK        respondió datos
    SIN INDEX la página no tiene acción index o solo acepta POST (normal en Crear/Eliminar/descargas)
    ERROR     respondió un error: revisar el mensaje y la ventana del backend
#>
param(
  [Parameter(Mandatory = $true)][string]$Usuario,
  [Parameter(Mandatory = $true)][string]$Clave,
  [string]$Url = 'http://localhost:8080'
)
$ErrorActionPreference = 'Stop'
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Pedir($ruta, $metodo = 'GET', $cuerpo = $null, $sesion = $s) {
  try {
    $r = Invoke-WebRequest -Uri ($Url + $ruta) -Method $metodo -Body $cuerpo -WebSession $sesion -UseBasicParsing -TimeoutSec 120
    $txt = if ($r.Headers['Content-Type'] -match 'json|text') { [Text.Encoding]::UTF8.GetString($r.RawContentStream.ToArray()) } else { '' }
    return [pscustomobject]@{ codigo = [int]$r.StatusCode; tipo = $r.Headers['Content-Type']; texto = $txt }
  } catch [System.Net.WebException] {
    $resp = $_.Exception.Response
    if ($resp) {
      $sr = New-Object IO.StreamReader($resp.GetResponseStream(), [Text.Encoding]::UTF8)
      return [pscustomobject]@{ codigo = [int]$resp.StatusCode; tipo = $resp.ContentType; texto = $sr.ReadToEnd() }
    }
    return [pscustomobject]@{ codigo = 0; tipo = ''; texto = $_.Exception.Message }
  }
}

Write-Host "Probando $Url con el usuario $Usuario" -ForegroundColor Cyan
$login = Pedir '/api/login' 'POST' @{ usuario = $Usuario; password = $Clave }
if ($login.texto -notmatch '"status"\s*:\s*"success"') { Write-Host "No se pudo iniciar sesión: $($login.texto)" -ForegroundColor Red; exit 1 }
if ($login.texto -match 'cambiarPassword') { Write-Host 'El usuario debe cambiar su contraseña primero (forzar_cambio_pwd = 1).' -ForegroundColor Yellow; exit 1 }

$ses = (Pedir '/api/sesion').texto | ConvertFrom-Json
Write-Host ("Sesión: {0} (rol {1}), {2} páginas permitidas" -f $ses.usuario_name, $ses.nivel_acceso, $ses.permisos.Count)

$ok = 0; $sinIndex = 0; $errores = @()
foreach ($p in ($ses.permisos | Where-Object { $_ -notin @('logout', 'login') } | Sort-Object)) {
  $r = Pedir "/api/$p`?accion=index"
  $esError = $r.codigo -ne 200 -or $r.texto -match '"(success|exito|ok|status)"\s*:\s*(false|"error")'
  if (-not $esError) { $ok++; continue }
  if ($r.texto -match "Request method 'GET' is not supported|Parameter conditions") { $sinIndex++; continue }
  $errores += [pscustomobject]@{ pagina = $p; codigo = $r.codigo; mensaje = $r.texto.Substring(0, [Math]::Min(200, $r.texto.Length)) }
}

Write-Host ''
Write-Host "Páginas OK: $ok   |   Sin acción index (normal): $sinIndex   |   Con error: $($errores.Count)"
foreach ($e in $errores) { Write-Host ("  ERROR {0} [{1}] {2}" -f $e.pagina, $e.codigo, $e.mensaje) -ForegroundColor Red }

Write-Host ''
foreach ($u in '/', '/inicio', '/app/logos/logoInees.jpg') {
  $r = Pedir $u
  $color = if ($r.codigo -eq 200) { 'Green' } else { 'Red' }
  Write-Host ("Estático {0,-28} {1}" -f $u, $r.codigo) -ForegroundColor $color
}
$anon = Pedir '/api/inicio?accion=index' 'GET' $null (New-Object Microsoft.PowerShell.Commands.WebRequestSession)
$color = if ($anon.codigo -eq 401) { 'Green' } else { 'Red' }
Write-Host ("Sin sesión /api/inicio -> {0} (se espera 401)" -f $anon.codigo) -ForegroundColor $color
Pedir '/api/logout' 'POST' | Out-Null
if ($errores.Count) { exit 2 } else { exit 0 }
