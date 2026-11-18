$ErrorActionPreference = 'SilentlyContinue'

$addresses = Get-NetIPAddress -AddressFamily IPv4 -AddressState Preferred |
  Where-Object {
    $_.IPAddress -match '^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)' -and
    $_.IPAddress -notmatch '^169\.254\.'
  } |
  Sort-Object @{ Expression = { if ($_.IPAddress -like '192.168.137.*') { 0 } else { 1 } } }, InterfaceAlias, IPAddress

Write-Output ''
Write-Output 'Phone connection links (choose the adapter/network the phone is using):'
if (-not $addresses) {
  Write-Output 'No active private IPv4 address found. Run ipconfig and check that Wi-Fi or Mobile Hotspot is connected.'
  exit 0
}

foreach ($entry in $addresses) {
  $ip = $entry.IPAddress
  $networkLabel = if ($ip -like '192.168.137.*') { 'Laptop Mobile Hotspot' } else { $entry.InterfaceAlias }
  Write-Output "[$networkLabel] Laptop IP: $ip"
  Write-Output "  Phone browser: http://${ip}:4200"
  Write-Output "  APK API address: http://${ip}:8000/api"
  Write-Output "  API health check: http://${ip}:8000/api/health"
}
Write-Output 'The phone and laptop must be connected to the same listed network.'
