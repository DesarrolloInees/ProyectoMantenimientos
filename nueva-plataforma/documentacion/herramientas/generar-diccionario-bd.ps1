<#
  Regenera documentacion\06-base-de-datos.md leyendo information_schema.
  Uso:  powershell -ExecutionPolicy Bypass -File .\generar-diccionario-bd.ps1 [-Usuario root] [-Clave xxx] [-Base inees_mantenimientos]
  Conserva al final el contenido de bd-convenciones.md (texto escrito a mano).
#>
param(
  [string]$Salida = (Join-Path $PSScriptRoot '..\06-base-de-datos.md'),
  [string]$Extra = (Join-Path $PSScriptRoot 'bd-convenciones.md'),
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
$m = $Mysql
function Q($sql) {
  $a = @('-h', $Servidor, '-u', $Usuario, '--default-character-set=utf8mb4', '-N', '-B')
  if ($Clave) { $a += "-p$Clave" }
  $sql = $sql -replace 'inees_mantenimientos', $Base
  & $m @a $Base -e $sql
}

$grupos = [ordered]@{
  'Operación: clientes, puntos y máquinas' = @('cliente','punto','maquina','tipo_maquina','estado_maquina','delegacion','municipio','modalidad_operativa')
  'Servicios (órdenes)' = @('ordenes_servicio','ordenes_servicio_complemento','evidencia_servicio','orden_servicio_novedad','tipo_novedad','orden_servicio_repuesto','calificacion_servicio','tipo_mantenimiento','tarifa','dias_festivos')
  'Técnicos y control en campo' = @('tecnico','turno_tecnico','monitoreo_motorizados_live','registro_horas_extra','evidencia_horas_extra','estados_aprobacion_he','facturas_parqueadero')
  'Repuestos, inventario y remisiones' = @('repuesto','inventario_tecnico','control_remisiones','estados_remision','instalaciones_desinstalaciones','control_devolucion_repuestos','solicitudes_inventario','solicitud_inventario_detalle')
  'Costos' = @('costos_operativos','gastos_administrativos')
  'Usuarios, seguridad y configuración' = @('usuarios','tipousuario','rutas','rol_permisos','login','password_reset','parametros')
}
$todas = Q "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='inees_mantenimientos' AND TABLE_TYPE='BASE TABLE' ORDER BY 1"
$enGrupos = $grupos.Values | % { $_ }
$faltan = @($todas | ? { $_ -notin $enGrupos })
if ($faltan.Count) { $grupos['Otras'] = $faltan }

$fks = Q "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='inees_mantenimientos' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY 1,2" | % { $p = $_ -split "`t"; [pscustomobject]@{ t=$p[0]; c=$p[1]; rt=$p[2]; rc=$p[3] } }
$cols = Q "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, IFNULL(COLUMN_DEFAULT,''), EXTRA, REPLACE(REPLACE(COLUMN_COMMENT, CHAR(10), ' '), '|', '/') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='inees_mantenimientos' ORDER BY TABLE_NAME, ORDINAL_POSITION" | % { $p = $_ -split "`t"; [pscustomobject]@{ t=$p[0]; c=$p[1]; ty=$p[2]; nul=$p[3]; k=$p[4]; def=$p[5]; ex=$p[6]; com=$p[7] } }
$tinfo = @{}; Q "SELECT TABLE_NAME, TABLE_ROWS, REPLACE(TABLE_COMMENT, '|', '/') FROM information_schema.TABLES WHERE TABLE_SCHEMA='inees_mantenimientos'" | % { $p = $_ -split "`t"; $tinfo[$p[0]] = @{ filas=$p[1]; com=$p[2] } }
$uniq = @{}; Q "SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='inees_mantenimientos' AND NON_UNIQUE=0 AND INDEX_NAME<>'PRIMARY' GROUP BY TABLE_NAME, INDEX_NAME" | % { $p = $_ -split "`t"; if (-not $uniq[$p[0]]) { $uniq[$p[0]] = @() }; $uniq[$p[0]] += $p[2] }

$sb = New-Object System.Text.StringBuilder
function L($s = '') { [void]$sb.AppendLine($s) }
L '# 6. Base de datos'
L ''
L "La plataforma usa la base MariaDB/MySQL **inees_mantenimientos**: $($todas.Count) tablas y $($fks.Count) llaves foráneas. Es la misma base que usa la versión PHP; la migración no cambió el esquema."
L ''
L '> Este documento se generó leyendo `information_schema` de la base local. Las cantidades de filas son aproximadas (estimación de InnoDB) y sirven solo como referencia de tamaño. Para regenerarlo después de un cambio de esquema, ver la sección [Regenerar este documento](#regenerar-este-documento).'
L ''
L '## Contenido'
L ''
L '- [Diagrama entidad-relación general](#diagrama-entidad-relación-general)'
L '- [Diagramas por dominio](#diagramas-por-dominio)'
L '- [Diccionario de datos](#diccionario-de-datos)'
L '- [Valores y convenciones importantes](#valores-y-convenciones-importantes)'
L '- [Regenerar este documento](#regenerar-este-documento)'
L ''
L '## Diagrama entidad-relación general'
L ''
L 'Todas las tablas con sus llaves foráneas. Cada línea va de la tabla que guarda la llave (muchos) a la tabla referenciada (uno). Se ve con cualquier visor de Markdown que soporte Mermaid (GitHub, GitLab, VS Code con la extensión *Markdown Preview Mermaid Support*).'
L ''
L '```mermaid'
L 'erDiagram'
foreach ($f in $fks) { L ("    {0} ||--o{{ {1} : `"{2}`"" -f $f.rt.ToUpper(), $f.t.ToUpper(), $f.c) }
$sinRel = $todas | ? { $t = $_; -not ($fks | ? { $_.t -eq $t -or $_.rt -eq $t }) }
foreach ($t in $sinRel) { L ("    {0} {{ }}" -f $t.ToUpper()) }
L '```'
L ''
L ('Tablas sin relaciones declaradas (se usan por código o son independientes): ' + (($sinRel | % { "``$_``" }) -join ', ') + '.')
L ''
L '## Diagramas por dominio'
L ''
L 'Los mismos datos, separados por área y con las columnas principales, para leerlos con más detalle.'
L ''
foreach ($g in $grupos.Keys) {
  $ts = $grupos[$g]
  L "### $g"
  L ''
  L '```mermaid'
  L 'erDiagram'
  foreach ($t in $ts) {
    L ("    {0} {{" -f $t.ToUpper())
    foreach ($c in ($cols | ? { $_.t -eq $t })) {
      $ty = ($c.ty -replace '\(.*$','' -replace ' unsigned','').ToLower()
      $flag = ''
      if ($c.k -eq 'PRI') { $flag = ' PK' } elseif ($fks | ? { $_.t -eq $t -and $_.c -eq $c.c }) { $flag = ' FK' } elseif ($c.k -eq 'UNI') { $flag = ' UK' }
      L ("        {0} {1}{2}" -f $ty, $c.c, $flag)
    }
    L '    }'
  }
  foreach ($f in ($fks | ? { $_.t -in $ts -or $_.rt -in $ts })) {
    if ($f.t -in $ts -and $f.rt -in $ts) { L ("    {0} ||--o{{ {1} : `"{2}`"" -f $f.rt.ToUpper(), $f.t.ToUpper(), $f.c) }
  }
  L '```'
  L ''
  $externas = $fks | ? { ($_.t -in $ts) -xor ($_.rt -in $ts) }
  if ($externas) {
    L ('Relaciones con tablas de otros dominios: ' + (($externas | % { "``$($_.t).$($_.c)`` → ``$($_.rt)``" }) -join ' · ') + '.')
    L ''
  }
}
L '## Diccionario de datos'
L ''
L 'Por cada tabla: columnas en orden, tipo, si admite nulos, llave (PK primaria, FK foránea, UNI única, MUL índice), valor por defecto y comentario guardado en la base.'
L ''
foreach ($g in $grupos.Keys) {
  L "### $g"
  L ''
  foreach ($t in $grupos[$g]) {
    $ti = $tinfo[$t]
    L "#### ``$t``"
    L ''
    $desc = "Filas aproximadas: $($ti.filas)."
    if ($ti.com) { $desc = "$($ti.com). " + $desc }
    L $desc
    $fk = $fks | ? { $_.t -eq $t }
    if ($fk) { L ('Referencia a: ' + (($fk | % { "``$($_.c)`` → ``$($_.rt).$($_.rc)``" }) -join ', ') + '.') }
    $refs = $fks | ? { $_.rt -eq $t }
    if ($refs) { L ('Referenciada por: ' + (($refs | % { "``$($_.t).$($_.c)``" }) -join ', ') + '.') }
    if ($uniq[$t]) { L ('Restricciones únicas: ' + (($uniq[$t] | % { "($_)" }) -join ', ') + '.') }
    L ''
    L '| Columna | Tipo | Nulo | Llave | Por defecto | Comentario |'
    L '| --- | --- | --- | --- | --- | --- |'
    foreach ($c in ($cols | ? { $_.t -eq $t })) {
      $k = $c.k; if ($fks | ? { $_.t -eq $t -and $_.c -eq $c.c }) { $k = if ($k -eq 'PRI') { 'PK, FK' } else { 'FK' } }
      $def = $c.def; if ($c.ex -match 'auto_increment') { $def = 'autoincremental' } elseif ($c.ex -match 'on update') { $def = "$def (se actualiza solo)" }
      L ("| ``{0}`` | {1} | {2} | {3} | {4} | {5} |" -f $c.c, $c.ty, $(if ($c.nul -eq 'YES') { 'sí' } else { 'no' }), $k, $def, $c.com)
    }
    L ''
  }
}
if ($Extra -and (Test-Path $Extra)) { [void]$sb.Append([IO.File]::ReadAllText($Extra, [Text.Encoding]::UTF8)) }
[IO.File]::WriteAllText($Salida, $sb.ToString(), (New-Object Text.UTF8Encoding $false))
"ok"
