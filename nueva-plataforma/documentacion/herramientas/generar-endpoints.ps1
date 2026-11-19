<#
  Regenera documentacion\07-endpoints.md leyendo las anotaciones @GetMapping/@PostMapping de los controladores.
  Uso:  powershell -ExecutionPolicy Bypass -File .\generar-endpoints.ps1
#>
param(
  [string]$Fuentes = (Join-Path $PSScriptRoot '..\..\backend\src\main\java\co\inees\mantenimientos\modulos'),
  [string]$Salida = (Join-Path $PSScriptRoot '..\07-endpoints.md')
)

$filas = @()
$controladores = @()
foreach ($f in Get-ChildItem $Fuentes -Recurse -Filter *Controller.java | Sort-Object { $_.Directory.Name }, Name) {
  $txt = [IO.File]::ReadAllText($f.FullName, [Text.Encoding]::UTF8)
  $mod = $f.Directory.Name
  $idxClase = $txt.IndexOf(' class ')
  $antes = $txt.Substring(0, [Math]::Max(0, $idxClase))
  $prefijo = ''
  $mp = [regex]::Match($antes, '@RequestMapping\(\s*"([^"]+)"\s*\)')
  if ($mp.Success) { $prefijo = $mp.Groups[1].Value }
  $doc = ''
  $md = [regex]::Matches($antes, '/\*\*(.*?)\*/', 'Singleline')
  if ($md.Count) {
    $doc = ($md[$md.Count - 1].Groups[1].Value -split "`n" | ForEach-Object { ($_ -replace '^\s*\*\s?', '').Trim() } | Where-Object { $_ }) -join ' '
  }
  $controladores += [pscustomobject]@{ mod = $mod; ctrl = $f.BaseName; doc = $doc }
  $cuerpo = $txt.Substring([Math]::Max(0, $idxClase))
  foreach ($a in [regex]::Matches($cuerpo, '@(Get|Post|Put|Delete|Request)Mapping(\((?<args>[^)]*)\))?\s*(?<resto>[^{;]*?)\b(?<fn>\w+)\s*\(', 'Singleline')) {
    $met = $a.Groups[1].Value.ToUpper()
    if ($met -eq 'REQUEST') { $met = 'GET/POST' }
    $args2 = $a.Groups['args'].Value
    $vs = ''
    $mv = [regex]::Match($args2, '(?:value|path)\s*=\s*(\{[^}]*\}|"[^"]*")', 'Singleline')
    if ($mv.Success) { $vs = $mv.Groups[1].Value } else { $mv2 = [regex]::Match($args2, '^\s*(\{[^}]*\}|"[^"]*")', 'Singleline'); if ($mv2.Success) { $vs = $mv2.Groups[1].Value } }
    $urls = @([regex]::Matches($vs, '"([^"]*)"') | ForEach-Object { $prefijo + $_.Groups[1].Value })
    if ($urls.Count -eq 0) { $urls = @($prefijo) }
    $acc = ''
    $pa = [regex]::Match($args2, 'params\s*=\s*"([^"]+)"')
    if ($pa.Success) { $acc = $pa.Groups[1].Value } else { $pb = [regex]::Match($args2, 'params\s*=\s*\{([^}]*)\}'); if ($pb.Success) { $acc = ($pb.Groups[1].Value -replace '"', '' -replace '\s+', ' ').Trim() } }
    $acc = $acc -replace '^accion=', ''
    foreach ($u in $urls) {
      if (-not $u -or $u -eq '/api') { continue }
      $filas += [pscustomobject]@{ mod = $mod; ctrl = $f.BaseName; met = $met; url = $u; accion = $acc; fn = $a.Groups['fn'].Value }
    }
  }
}

$sb = New-Object System.Text.StringBuilder
function L($s = '') { [void]$sb.AppendLine($s) }
$paginas = @($filas | Select-Object -ExpandProperty url -Unique)
L '# 7. Referencia de endpoints'
L ''
L "Lista generada desde el código de los controladores: **$($filas.Count) endpoints** en **$($paginas.Count) páginas**, agrupados por módulo y controlador. Las convenciones (URL, sesión, permisos, formato de respuesta) se explican en [04-backend.md](04-backend.md#ciclo-de-una-petición)."
L ''
L '> Para regenerar este archivo después de agregar o cambiar endpoints: `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-endpoints.ps1`.'
L ''
L 'Cómo leer las tablas:'
L ''
L '- **URL**: ruta del endpoint. La primera parte después de `/api/` es la *página* y debe existir en la tabla `rutas` (ver [08-permisos-y-roles.md](08-permisos-y-roles.md)).'
L '- **accion**: valor del parámetro `?accion=`. "—" significa que el endpoint responde sin `accion` (o con cualquier otra).'
L '- **Método**: `GET`, `POST` o `GET/POST` (acepta los dos).'
L '- **Método Java**: `Controlador.metodo`, para ubicar el código.'
L ''
L '## Endpoints que no son páginas'
L ''
L '| URL | Método | Qué hace |'
L '| --- | --- | --- |'
L '| `/api/sesion` | GET | Estado de la sesión: usuario, rol y lista de páginas permitidas. Lo llama Angular al cargar. |'
L '| `/api/logout` | GET/POST | Cierra la sesión (equivale a `logout` del PHP). |'
L '| `/api/{pagina}` con `accion` | — | Todo lo demás, listado abajo. En `pdfServicio`, "`accion`" en la columna accion significa que acepta cualquier valor. |'
L '| `/`, `/{pagina}`, `/{pagina}/{param}` | GET | Devuelve `index.html` para que Angular resuelva la ruta (`core/SpaController`). |'
L '| `/uploads/**`, `/app/uploads/**`, `/imagenes/**`, `/app/imagenes/**`, `/app/logos/**`, `/app/temp/**` | GET | Archivos subidos, servidos desde las mismas carpetas del PHP (`core/WebConfig`). |'
L ''
L '## Índice de módulos'
L ''
foreach ($g in ($filas | Group-Object mod | Sort-Object Name)) {
  L ("- [{0}](#{0}) — {1} endpoints" -f $g.Name, $g.Count)
}
L ''
foreach ($g in ($filas | Group-Object mod | Sort-Object Name)) {
  L "## $($g.Name)"
  L ''
  foreach ($c in ($g.Group | Group-Object ctrl | Sort-Object Name)) {
    L "### $($c.Name)"
    L ''
    $d = ($controladores | Where-Object { $_.ctrl -eq $c.Name }).doc
    if ($d) { L $d; L '' }
    L "Archivo: ``backend/src/main/java/co/inees/mantenimientos/modulos/$($g.Name)/$($c.Name).java``"
    L ''
    L '| URL | Método | accion | Método Java |'
    L '| --- | --- | --- | --- |'
    foreach ($e in ($c.Group | Sort-Object url, @{ e = { if ($_.accion -eq 'index') { 0 } else { 1 } } }, accion)) {
      $acc = if ($e.accion) { "``$($e.accion)``" } else { '—' }
      L ("| ``{0}`` | {1} | {2} | ``{3}.{4}`` |" -f $e.url, $e.met, $acc, $e.ctrl, $e.fn)
    }
    L ''
  }
}
[IO.File]::WriteAllText($Salida, $sb.ToString(), (New-Object Text.UTF8Encoding $false))
"ok: $($filas.Count) endpoints, $($paginas.Count) páginas"
