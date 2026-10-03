$ErrorActionPreference = 'Stop'
$Project = Split-Path -Parent $PSScriptRoot
Set-Location $Project

function Resolve-Exe([string]$Name, [string]$Fallback) {
    $cmd = Get-Command $Name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    if (Test-Path $Fallback) { return $Fallback }
    throw "ไม่พบ $Name"
}

function Test-TcpPort([string]$HostName, [int]$Port) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $result = $client.BeginConnect($HostName, $Port, $null, $null)
        $ok = $result.AsyncWaitHandle.WaitOne(700, $false)
        if ($ok -and $client.Connected) { $client.EndConnect($result); $client.Close(); return $true }
        $client.Close(); return $false
    } catch { return $false }
}

$Php = Resolve-Exe 'php' 'C:\xampp\php\php.exe'
$Composer = Get-Command composer -ErrorAction SilentlyContinue
if (-not $Composer) { throw 'ไม่พบ Composer กรุณาติดตั้ง Composer ก่อน' }

Write-Host '[PrintShop] Installing PHP dependencies...' -ForegroundColor Cyan
& $Composer.Source install --no-interaction

if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
$envText = Get-Content '.env' -Raw
if ($envText -notmatch '(?m)^APP_KEY=base64:.+') { & $Php artisan key:generate --force }

# Start MySQL without requiring the XAMPP Control Panel.
if (-not (Test-TcpPort '127.0.0.1' 3306)) {
    $MySqlD = 'C:\xampp\mysql\bin\mysqld.exe'
    $MyIni = 'C:\xampp\mysql\bin\my.ini'
    if (-not (Test-Path $MySqlD)) { throw 'ไม่พบ MySQL ที่ C:\xampp\mysql\bin\mysqld.exe' }
    $mysqlCommand = "& '$MySqlD' --defaults-file='$MyIni' --console"
    Start-Process powershell.exe -ArgumentList @('-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $mysqlCommand)
    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Seconds 1
        if (Test-TcpPort '127.0.0.1' 3306) { break }
    }
}
if (-not (Test-TcpPort '127.0.0.1' 3306)) { throw 'เปิด MySQL ไม่สำเร็จ' }

$MySqlClient = 'C:\xampp\mysql\bin\mysql.exe'
if (Test-Path $MySqlClient) {
    & $MySqlClient -u root -e "CREATE DATABASE IF NOT EXISTS printshop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS printshop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
}

& $Php artisan optimize:clear
& $Php artisan migrate --seed --force

$PublicStorage = Join-Path $Project 'public\storage'
$TargetStorage = Join-Path $Project 'storage\app\public'
if (Test-Path $PublicStorage) {
    $storageItem = Get-Item $PublicStorage -Force
    if (-not ($storageItem.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
        New-Item -ItemType Directory -Force $TargetStorage | Out-Null
        Get-ChildItem $PublicStorage -Force | ForEach-Object {
            Copy-Item $_.FullName -Destination $TargetStorage -Recurse -Force
        }
        Remove-Item $PublicStorage -Recurse -Force
    }
}
if (-not (Test-Path $PublicStorage)) {
    try { & $Php artisan storage:link } catch { }
}

Write-Host ''
Write-Host 'Setup complete.' -ForegroundColor Green
Write-Host 'Demo accounts:' -ForegroundColor Cyan
Write-Host 'Admin:    admin@printshop.local / Printshop123!'
Write-Host 'Staff:    staff@printshop.local / Printshop123!'
Write-Host 'Customer: customer@printshop.local / Printshop123!'
Write-Host ''
Write-Host 'Starting PrintShop...' -ForegroundColor Cyan
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'start-printshop.ps1')
