# Execute este script como Administrador.

param(
    [switch]$SetPrivateProfile
)

$ruleName = 'Cantina - Apache HTTP 80'

if ($SetPrivateProfile) {
    Get-NetConnectionProfile | Where-Object { $_.InterfaceAlias -eq 'Ethernet' -and $_.NetworkCategory -eq 'Public' } |
        Set-NetConnectionProfile -NetworkCategory Private
}

if (Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue) {
    Set-NetFirewallRule -DisplayName $ruleName -Enabled True -Direction Inbound -Action Allow -Profile Private,Public
} else {
    New-NetFirewallRule -DisplayName $ruleName -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80 -Profile Private,Public -RemoteAddress LocalSubnet | Out-Null
}

Write-Host 'Regra de firewall aplicada.'
Get-NetFirewallRule -DisplayName $ruleName | Select-Object DisplayName,Enabled,Profile,Direction,Action
