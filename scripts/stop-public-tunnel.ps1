Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force
Write-Output 'Tunnel encerrado.'
