param([string]$ProjectRoot = 'D:\USB-READY\projects')
$ErrorActionPreference = 'Stop'
$mysqlExe = 'C:\xampp\mysql\bin\mysql.exe'
$phpExe = 'C:\xampp\php\php.exe'
$posRoot = Split-Path $PSScriptRoot -Parent
function Set-EnvValue([string]$FilePath, [string]$Key, [string]$Value) {
    $content = Get-Content -LiteralPath $FilePath -Raw
    $pattern = '(?m)^' + [regex]::Escape($Key) + '=.*$'
    if ([regex]::IsMatch($content, $pattern)) { $content = [regex]::Replace($content, $pattern, { param($match) "$Key=$Value" }) }
    else { $content += "`n$Key=$Value`n" }
    Set-Content -LiteralPath $FilePath -Value $content -NoNewline
}
$systems = @(
    @{ Name='inventory'; Folder='inventory-laravel'; Database='supermarket_inventory'; Port=8001; Prefix='INVENTORY' },
    @{ Name='hrms'; Folder='harvest-hr-next'; Database='harvest_hr'; Port=8003; Prefix='HRMS' },
    @{ Name='crm'; Folder='CustomerManagementSystem-Laravel12'; Database='customer_management_system'; Port=8004; Prefix='CRM' }
)
foreach ($system in $systems) {
    $root = Join-Path $ProjectRoot $system.Folder
    $runtime = Join-Path (Join-Path $posRoot 'storage\app\source-runtimes') $system.Folder
    $artisanPath = if (Test-Path -LiteralPath (Join-Path $runtime 'vendor\autoload.php')) { Join-Path $runtime 'artisan.php' } else { Join-Path $root 'artisan' }
    if ($artisanPath -eq (Join-Path $root 'artisan') -and -not (Test-Path -LiteralPath (Join-Path $root 'vendor\autoload.php'))) { throw "Install Composer dependencies in $root before configuring local APIs." }
    $envPath = Join-Path $root '.env'
    if (-not (Test-Path -LiteralPath $envPath)) { Copy-Item -LiteralPath (Join-Path $root '.env.example') -Destination $envPath }
    $database = $system.Database
    $exists = & $mysqlExe --host=127.0.0.1 --port=3306 --user=root --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$database';"
    if ($LASTEXITCODE -ne 0) { throw 'Database connectivity check failed.' }
    if ([int]$exists -eq 0) {
        & $mysqlExe --host=127.0.0.1 --port=3306 --user=root --execute="CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        if ($LASTEXITCODE -ne 0) { throw 'Database creation failed.' }
        $sqlPath = (Join-Path (Split-Path $ProjectRoot -Parent) "fresh-database-exports\${database}_fresh.sql").Replace('\','/')
        & $mysqlExe --host=127.0.0.1 --port=3306 --user=root "--database=$database" "--execute=source $sqlPath"
        if ($LASTEXITCODE -ne 0) { throw 'Fresh database import failed.' }
    }
    foreach ($pair in @{ APP_ENV='local'; APP_URL="http://127.0.0.1:$($system.Port)"; DB_CONNECTION='mysql'; DB_HOST='127.0.0.1'; DB_PORT='3306'; DB_DATABASE=$database; DB_USERNAME='root'; DB_PASSWORD=''; CACHE_STORE='file'; SESSION_DRIVER='file'; QUEUE_CONNECTION='sync' }.GetEnumerator()) { Set-EnvValue $envPath $pair.Key $pair.Value }
    $tokenLine = Get-Content -LiteralPath $envPath | Where-Object { $_ -match '^INTEGRATION_API_TOKEN=.+' } | Select-Object -First 1
    $token = if ($tokenLine) { $tokenLine.Substring('INTEGRATION_API_TOKEN='.Length) } else { [Convert]::ToHexString([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32)).ToLower() }
    Set-EnvValue $envPath 'INTEGRATION_API_TOKEN' $token
    Set-EnvValue (Join-Path $posRoot '.env') "$($system.Prefix)_API_BASE_URL" "http://127.0.0.1:$($system.Port)"
    Set-EnvValue (Join-Path $posRoot '.env') "$($system.Prefix)_API_TOKEN" $token
    Push-Location $root
    try {
        if (-not (Select-String -LiteralPath .env -Pattern '^APP_KEY=.+')) {
            & $phpExe $artisanPath key:generate --no-interaction
            if ($LASTEXITCODE -ne 0) { throw 'Application key generation failed.' }
        }
        & $phpExe $artisanPath config:clear
        if ($LASTEXITCODE -ne 0) { throw 'Configuration refresh failed.' }
    } finally { Pop-Location }
    Write-Output "$($system.Name) local configuration ready."
}
Set-EnvValue (Join-Path $posRoot '.env') 'INTEGRATION_ALLOW_LOOPBACK_HTTP' 'true'
if (-not (Select-String -LiteralPath (Join-Path $posRoot '.env') -Pattern '^POS_INSTANCE_ID=.+')) { Set-EnvValue (Join-Path $posRoot '.env') 'POS_INSTANCE_ID' ([guid]::NewGuid().ToString()) }
Push-Location $posRoot
try { & $phpExe artisan config:clear } finally { Pop-Location }
