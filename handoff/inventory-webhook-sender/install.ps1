param([string]$ProjectPath = 'C:\xampp\htdocs\inventory-laravel')
$ErrorActionPreference = 'Stop'
$targetRoot = [IO.Path]::GetFullPath($ProjectPath).TrimEnd('\')
if (-not (Test-Path -LiteralPath (Join-Path $targetRoot 'artisan'))) { throw 'ProjectPath must be the Inventory Laravel folder.' }
$files = @(
    'config\pos_webhook.php',
    'app\Services\PosWebhookSender.php',
    'app\Console\Commands\SendPosWebhooks.php',
    'database\migrations\2026_10_10_190000_create_pos_webhook_outbox.php'
)
foreach ($relativeFile in $files) {
    $destination = [IO.Path]::GetFullPath((Join-Path $targetRoot $relativeFile))
    if (-not $destination.StartsWith($targetRoot + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Invalid destination path.' }
    New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
    if (Test-Path -LiteralPath $destination) { Copy-Item -LiteralPath $destination -Destination ($destination + '.backup-' + (Get-Date -Format 'yyyyMMddHHmmss')) }
    Copy-Item -LiteralPath (Join-Path (Join-Path $PSScriptRoot 'files') $relativeFile) -Destination $destination -Force
}
Push-Location $targetRoot
try {
    & php artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Configuration refresh failed.' }
    & php artisan migrate --path=database/migrations/2026_10_10_190000_create_pos_webhook_outbox.php --force
    if ($LASTEXITCODE -ne 0) { throw 'Migration failed. Check MySQL/MariaDB connection and CREATE TRIGGER permission.' }
    & php artisan pos:webhooks --once
    if ($LASTEXITCODE -ne 0) { throw 'Sender test failed. Check .env, receiver URL/token, and pending outbox errors.' }
} finally { Pop-Location }
Write-Output 'Installed. From the Inventory project folder, run: php artisan pos:webhooks'
