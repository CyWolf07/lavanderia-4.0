param([string]$Gradle = 'gradle')
$ErrorActionPreference = 'Stop'
$signingDir = Join-Path $PSScriptRoot 'signing'
$keystore = Join-Path $signingDir 'puntual.jks'
$passwordFile = Join-Path $signingDir 'password.dpapi'
New-Item -ItemType Directory -Force -Path $signingDir | Out-Null
if (!(Test-Path -LiteralPath $keystore)) {
    $password = [Convert]::ToBase64String([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $password | ConvertTo-SecureString -AsPlainText -Force | ConvertFrom-SecureString | Set-Content -LiteralPath $passwordFile
    $env:PUNTUAL_STORE_PASSWORD = $password
    & "$env:JAVA_HOME/bin/keytool.exe" -genkeypair -keystore $keystore -alias puntual -keyalg RSA -keysize 2048 -validity 10000 -storepass:env PUNTUAL_STORE_PASSWORD -keypass:env PUNTUAL_STORE_PASSWORD -dname 'CN=Lavanderia Exclusiva, O=Lavanderia Exclusiva, C=CO'
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear la firma Android.' }
} else {
    $secret = Get-Content -LiteralPath $passwordFile | ConvertTo-SecureString
    $env:PUNTUAL_STORE_PASSWORD = [Net.NetworkCredential]::new('', $secret).Password
}
try {
    $env:PUNTUAL_KEYSTORE = $keystore
    & $Gradle -p $PSScriptRoot assembleRelease --no-daemon
    if ($LASTEXITCODE -ne 0) { throw 'Fallo la compilacion Android.' }
    $target = Join-Path $PSScriptRoot '../public/downloads'
    New-Item -ItemType Directory -Force -Path $target | Out-Null
    Copy-Item -LiteralPath "$PSScriptRoot/app/build/outputs/apk/release/app-release.apk" -Destination "$target/lavanderia-exclusiva.apk"
    & "$env:JAVA_HOME/bin/keytool.exe" -list -v -keystore $keystore -alias puntual -storepass:env PUNTUAL_STORE_PASSWORD
} finally {
    Remove-Item Env:PUNTUAL_STORE_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:PUNTUAL_KEYSTORE -ErrorAction SilentlyContinue
}
