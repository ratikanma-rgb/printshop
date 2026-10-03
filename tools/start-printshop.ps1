$ErrorActionPreference = 'Stop'
$Project = Split-Path -Parent $PSScriptRoot
Set-Location $Project

function Resolve-Php {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $xamppPhp = 'C:\xampp\php\php.exe'
    if (Test-Path $xamppPhp) { return $xamppPhp }
    throw 'ไม่พบ PHP กรุณาติดตั้ง PHP หรือ XAMPP และเพิ่ม PHP ลง PATH'
}

function Test-TcpPort([string]$HostName, [int]$Port) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $result = $client.BeginConnect($HostName, $Port, $null, $null)
        $ok = $result.AsyncWaitHandle.WaitOne(700, $false)
        if ($ok -and $client.Connected) { $client.EndConnect($result); $client.Close(); return $true }
        $client.Close()
        return $false
    } catch { return $false }
}

$Php = Resolve-Php
Write-Host "[PrintShop] Project: $Project" -ForegroundColor Cyan
Write-Host "[PrintShop] PHP: $Php" -ForegroundColor Cyan

# Start MySQL/MariaDB automatically when port 3306 is not available.
if (-not (Test-TcpPort '127.0.0.1' 3306)) {
    $MySqlD = 'C:\xampp\mysql\bin\mysqld.exe'
    $MyIni = 'C:\xampp\mysql\bin\my.ini'
    if (-not (Test-Path $MySqlD)) {
        throw 'MySQL ไม่ได้ทำงาน และไม่พบ C:\xampp\mysql\bin\mysqld.exe'
    }

    Write-Host '[PrintShop] Starting MySQL...' -ForegroundColor Yellow
    $mysqlCommand = "& '$MySqlD' --defaults-file='$MyIni' --console"
    Start-Process powershell.exe -ArgumentList @('-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $mysqlCommand)

    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Seconds 1
        if (Test-TcpPort '127.0.0.1' 3306) { break }
    }
}

if (-not (Test-TcpPort '127.0.0.1' 3306)) {
    throw 'เปิด MySQL ไม่สำเร็จ กรุณาตรวจสอบว่า Port 3306 ถูกใช้งานหรือ MySQL มีปัญหา'
}
Write-Host '[PrintShop] MySQL ready.' -ForegroundColor Green

# Create the local database when the XAMPP mysql client is available.
$MySqlClient = 'C:\xampp\mysql\bin\mysql.exe'
if (Test-Path $MySqlClient) {
    & $MySqlClient -u root -e "CREATE DATABASE IF NOT EXISTS printshop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS printshop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
}

# Ensure local session/cache directories exist before Laravel starts.
New-Item -ItemType Directory -Force (Join-Path $Project 'storage\framework\sessions') | Out-Null
New-Item -ItemType Directory -Force (Join-Path $Project 'storage\framework\cache\data') | Out-Null
New-Item -ItemType Directory -Force (Join-Path $Project 'storage\framework\views') | Out-Null

& $Php artisan optimize:clear | Out-Host
& $Php artisan migrate --force | Out-Host

$PublicStorage = Join-Path $Project 'public\storage'
$TargetStorage = Join-Path $Project 'storage\app\public'
if (Test-Path $PublicStorage) {
    $storageItem = Get-Item $PublicStorage -Force
    if (-not ($storageItem.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
        Write-Host '[PrintShop] Repairing public/storage link...' -ForegroundColor Yellow
        New-Item -ItemType Directory -Force $TargetStorage | Out-Null
        Get-ChildItem $PublicStorage -Force | ForEach-Object {
            Copy-Item $_.FullName -Destination $TargetStorage -Recurse -Force
        }
        Remove-Item $PublicStorage -Recurse -Force
    }
}
if (-not (Test-Path $PublicStorage)) {
    try { & $Php artisan storage:link | Out-Host } catch { }
}

# Make sure port 8000 is serving THIS project, not an older PrintShop copy.
try {
    $listeners = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
    foreach ($listener in $listeners) {
        $proc = Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue
        if ($proc -and $proc.ProcessName -match '^php') {
            Write-Host '[PrintShop] Closing old PHP server on port 8000...' -ForegroundColor Yellow
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
            Start-Sleep -Milliseconds 800
        }
    }
} catch { }

if (Test-TcpPort '127.0.0.1' 8000) {
    throw 'Port 8000 is being used by another program. Please close that program and run START_PRINTSHOP.bat again.'
}

$serverCommand = "Set-Location '$Project'; & '$Php' artisan serve --host=127.0.0.1 --port=8000"
Start-Process powershell.exe -ArgumentList @('-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $serverCommand)
Start-Sleep -Seconds 2

# Start one queue worker window for queued database notifications.
$queueCommand = "Set-Location '$Project'; & '$Php' artisan queue:work --tries=3 --timeout=90"
Start-Process powershell.exe -ArgumentList @('-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $queueCommand)

Start-Sleep -Seconds 1
Start-Process 'http://127.0.0.1:8000/login'
Write-Host '[PrintShop] Ready. Browser opened at http://127.0.0.1:8000/login' -ForegroundColor Green
