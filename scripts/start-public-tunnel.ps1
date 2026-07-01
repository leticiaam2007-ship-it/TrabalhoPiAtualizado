param(
    [string]$LocalUrl = 'http://localhost'
)

$wingetPath = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'
$exe = Get-ChildItem -Path $wingetPath -Recurse -Filter 'cloudflared.exe' -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending |
    Select-Object -First 1 -ExpandProperty FullName

if (-not $exe) {
    Write-Error 'cloudflared nao encontrado. Instale com: winget install --id Cloudflare.cloudflared -e --accept-source-agreements --accept-package-agreements'
    exit 1
}

$out = Join-Path $PSScriptRoot '..\cloudflared.out.log'
$err = Join-Path $PSScriptRoot '..\cloudflared.err.log'
$out = [System.IO.Path]::GetFullPath($out)
$err = [System.IO.Path]::GetFullPath($err)

Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Milliseconds 500

foreach ($f in @($out, $err)) {
    if (Test-Path $f) {
        Remove-Item $f -Force
    }
}

Start-Process -FilePath $exe -ArgumentList 'tunnel','--url',$LocalUrl,'--no-autoupdate' -WindowStyle Hidden -RedirectStandardOutput $out -RedirectStandardError $err | Out-Null

$url = $null
for ($i = 0; $i -lt 40; $i++) {
    Start-Sleep -Milliseconds 500
    if (Test-Path $err) {
        $lines = Get-Content $err -Tail 200
        $match = $lines | Select-String -Pattern 'https://[a-z0-9\-]+\.trycloudflare\.com' | Select-Object -Last 1
        if ($match) {
            $url = $match.Matches[0].Value
            break
        }
    }
}

if (-not $url) {
    Write-Output 'Tunnel iniciado, mas URL ainda nao encontrada. Confira o log:'
    Write-Output $err
    exit 0
}

Write-Output ('TUNNEL_URL=' + $url)
Write-Output ('APP_LOGIN_URL=' + $url + '/cantina/login.php')
Write-Output ('APP_HOME_URL=' + $url + '/cantina/index.php')
Write-Output ('LOG_FILE=' + $err)
