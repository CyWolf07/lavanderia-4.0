param(
    [int]$Port = 8080,
    [int]$DbPort = 5433,
    [switch]$NoBuild
)

function Get-LanIpAddress {
    $addresses = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.254\.' -and
            $_.InterfaceAlias -notmatch 'Loopback' -and
            $_.InterfaceAlias -notmatch 'Default Switch' -and
            $_.InterfaceAlias -notmatch 'WSL'
        } |
        Sort-Object InterfaceMetric, SkipAsSource

    return $addresses | Select-Object -ExpandProperty IPAddress -First 1
}

$lanIp = Get-LanIpAddress

if (-not $lanIp) {
    $lanIp = 'localhost'
}

$env:DOCKER_WEB_PORT = "$Port"
$env:DOCKER_DB_PORT = "$DbPort"
$env:DOCKER_APP_URL = if ($lanIp -eq 'localhost') {
    "http://localhost:$Port"
} else {
    "http://${lanIp}:$Port"
}

$composeArgs = @('compose', 'up', '-d')

if (-not $NoBuild) {
    $composeArgs = @('compose', 'up', '--build', '-d')
}

docker @composeArgs

if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

$healthUrl = "http://localhost:$Port/up"
$ready = $false

for ($attempt = 0; $attempt -lt 20; $attempt++) {
    try {
        $response = Invoke-WebRequest -UseBasicParsing $healthUrl -TimeoutSec 5

        if ($response.StatusCode -eq 200) {
            $ready = $true
            break
        }
    } catch {
        Start-Sleep -Seconds 2
    }
}

Write-Host ""
Write-Host "Lavanderia lista."
Write-Host "URL local: http://localhost:$Port"

if ($lanIp -ne 'localhost') {
    Write-Host "URL en red: http://${lanIp}:$Port"
}

Write-Host "Base de datos: localhost:$DbPort"

if ($ready) {
    Write-Host "Estado web: OK"
} else {
    Write-Warning "La aplicacion todavia esta arrancando. Revisa: docker compose logs -f web"
}

Write-Host ""
Write-Host "Si otro dispositivo no abre la pagina, revisa el firewall de Windows para permitir el puerto $Port."
