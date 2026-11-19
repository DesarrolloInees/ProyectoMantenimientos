# Ejecuta Maven con el JDK 21 local (inees-local). Uso: .\mvn-local.ps1 compile | package | spring-boot:run
$jdk = Join-Path $env:USERPROFILE 'inees-local\jdk-21.0.13+9'
if (Test-Path $jdk) {
    $env:JAVA_HOME = $jdk
    $env:Path = "$jdk\bin;$env:Path"
}
$mvn = Get-Command mvn -ErrorAction SilentlyContinue
if ($mvn) { $mvnExe = $mvn.Source } else {
    $mvnExe = (Get-ChildItem (Join-Path $env:USERPROFILE '.m2\wrapper\dists') -Recurse -Filter mvn.cmd -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
}
if (-not $mvnExe) { Write-Error 'No se encontró Maven (mvn).'; exit 1 }
& $mvnExe @args
exit $LASTEXITCODE
