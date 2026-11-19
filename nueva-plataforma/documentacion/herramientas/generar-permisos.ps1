<#
  Regenera la sección "Matriz de permisos" de documentacion\08-permisos-y-roles.md
  leyendo las tablas rutas, rol_permisos y tipousuario.
  Uso:  powershell -ExecutionPolicy Bypass -File .\generar-permisos.ps1 [-Usuario root] [-Clave xxx]
  El texto escrito a mano está en permisos-intro.md y se conserva.
#>
param(
  [string]$Salida = (Join-Path $PSScriptRoot '..\08-permisos-y-roles.md'),
  [string]$Intro = (Join-Path $PSScriptRoot 'permisos-intro.md'),
  [string]$Mysql = '',
  [string]$Usuario = 'root',
  [string]$Clave = '',
  [string]$Servidor = 'localhost',
  [string]$Base = 'inees_mantenimientos'
)
if (-not $Mysql) {
  $cands = @(
    (Get-ChildItem (Join-Path $env:USERPROFILE 'inees-local') -Directory -Filter 'mariadb-*' -ErrorAction SilentlyContinue | ForEach-Object { Join-Path $_.FullName 'bin\mysql.exe' }),
    'C:\xampp\mysql\bin\mysql.exe'
  ) | Where-Object { $_ -and (Test-Path $_) }
  if ($cands) { $Mysql = @($cands)[0] } else { $Mysql = 'mysql' }
}
[Console]::OutputEncoding = [Text.Encoding]::UTF8
function Q($sql) {
  $a = @('-h', $Servidor, '-u', $Usuario, '--default-character-set=utf8mb4', '-N', '-B')
  if ($Clave) { $a += "-p$Clave" }
  & $Mysql @a $Base -e $sql
}

$roles = Q "SELECT idTipoUsuario, nombreTipoUsuario FROM tipousuario ORDER BY idTipoUsuario" | ForEach-Object { $p = $_ -split "`t"; [pscustomobject]@{ id = [int]$p[0]; nombre = $p[1] } }
$rutas = Q "SELECT r.id_ruta, r.nombre_ruta, r.estado, IFNULL(REPLACE(r.descripcion,'|','/'),''), r.controlador_ruta, IFNULL(GROUP_CONCAT(rp.rol_id ORDER BY rp.rol_id),'') FROM rutas r LEFT JOIN rol_permisos rp ON rp.ruta_id = r.id_ruta GROUP BY r.id_ruta ORDER BY r.nombre_ruta" | ForEach-Object {
  $p = $_ -split "`t"
  [pscustomobject]@{ id = $p[0]; nombre = $p[1]; estado = $p[2]; desc = $p[3]; ctrl = $p[4]; roles = @($p[5] -split ',' | Where-Object { $_ } | ForEach-Object { [int]$_ }) }
}

$sb = New-Object System.Text.StringBuilder
function L($s = '') { [void]$sb.AppendLine($s) }
if (Test-Path $Intro) { [void]$sb.Append([IO.File]::ReadAllText($Intro, [Text.Encoding]::UTF8)) }
L ''
L '## Matriz de permisos'
L ''
L "Generada desde la base de datos: $(@($rutas).Count) páginas registradas en ``rutas``. ✔ = el rol tiene la página en ``rol_permisos``; ★ = el rol 1 la ve por ser administrador aunque no tenga fila. Las páginas inactivas no las ve nadie."
L ''
$enc = '| Página | Estado | ' + (($roles | ForEach-Object { "$($_.id) $($_.nombre)" }) -join ' | ') + ' | Descripción |'
L $enc
L ('| --- | --- | ' + (($roles | ForEach-Object { '---' }) -join ' | ') + ' | --- |')
foreach ($r in $rutas) {
  $celdas = foreach ($ro in $roles) {
    if ($r.roles -contains $ro.id) { '✔' } elseif ($ro.id -eq 1 -and $r.estado -eq 'activo') { '★' } else { '' }
  }
  L ("| ``{0}`` | {1} | {2} | {3} |" -f $r.nombre, $r.estado, ($celdas -join ' | '), $r.desc)
}
L ''
L '> Para regenerar esta tabla: `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-permisos.ps1`.'
[IO.File]::WriteAllText($Salida, $sb.ToString(), (New-Object Text.UTF8Encoding $false))
"ok: $(@($rutas).Count) rutas"
