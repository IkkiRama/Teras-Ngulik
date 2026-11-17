param([int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpCandidates = @('D:\laragon\bin\php\php-8.3.7-Win32-vs16-x64\php.exe')
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($phpCommand) { $phpCandidates += $phpCommand.Source }
$phpExe = $phpCandidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
if (-not $phpExe) { throw 'PHP >=8.3 tidak ditemukan. Pilih PHP 8.3 atau lebih baru di Laragon.' }
if (-not (Test-Path -LiteralPath '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env'; & $phpExe artisan key:generate }
if (-not (Test-Path -LiteralPath 'database\database.sqlite')) { New-Item -ItemType File -Path 'database\database.sqlite' | Out-Null }
& $phpExe artisan migrate --force
Write-Host "Teras Ngulik: http://127.0.0.1:$Port (Ctrl+C untuk berhenti)" -ForegroundColor Cyan
$router = Join-Path $PSScriptRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
Push-Location -LiteralPath (Join-Path $PSScriptRoot 'public')
try { & $phpExe -d upload_max_filesize=12M -d post_max_size=14M -S "127.0.0.1:$Port" $router }
finally { Pop-Location }
