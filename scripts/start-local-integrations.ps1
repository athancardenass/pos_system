param([string]$ProjectRoot = 'D:\USB-READY\projects')
$ErrorActionPreference = 'Stop'
$posRoot = Split-Path $PSScriptRoot -Parent
$phpExe = 'C:\xampp\php\php.exe'
$systems = @(
    @{ Folder='inventory-laravel'; Port=8001 },
    @{ Folder='harvest-hr-next'; Port=8003 },
    @{ Folder='CustomerManagementSystem-Laravel12'; Port=8004 }
)
foreach ($system in $systems) {
    if (Get-NetTCPConnection -State Listen -LocalPort $system.Port -ErrorAction SilentlyContinue) {
        Write-Output "Port $($system.Port) already has a listener; keeping it running."
        continue
    }
    $sourcePath = Join-Path $ProjectRoot $system.Folder
    $runtimePath = Join-Path (Join-Path $posRoot 'storage\app\source-runtimes') $system.Folder
    $routerPath = Join-Path $runtimePath 'router.php'
    if (Test-Path -LiteralPath $routerPath) {
        $arguments = @('-S',"127.0.0.1:$($system.Port)",'-t',('"'+(Join-Path $sourcePath 'public')+'"'),('"'+$routerPath+'"'))
        $workingPath = $posRoot
    } else {
        if (-not (Test-Path -LiteralPath (Join-Path $sourcePath 'vendor\autoload.php'))) { throw "Install dependencies in $sourcePath first." }
        $arguments = @('artisan','serve','--host=127.0.0.1',"--port=$($system.Port)")
        $workingPath = $sourcePath
    }
    Start-Process -FilePath $phpExe -ArgumentList $arguments -WorkingDirectory $workingPath -WindowStyle Hidden `
        -RedirectStandardOutput (Join-Path $posRoot "storage\logs\$($system.Folder).stdout.log") `
        -RedirectStandardError (Join-Path $posRoot "storage\logs\$($system.Folder).stderr.log") | Out-Null
    Write-Output "$($system.Folder) started on loopback port $($system.Port)."
}
$existingScheduler = Get-CimInstance Win32_Process -Filter "Name='php.exe'" | Where-Object { $_.CommandLine -like '*artisan schedule:work*' }
if (-not $existingScheduler) {
    Start-Process -FilePath $phpExe -ArgumentList 'artisan','schedule:work' -WorkingDirectory $posRoot -WindowStyle Hidden `
        -RedirectStandardOutput (Join-Path $posRoot 'storage\logs\integration-scheduler.stdout.log') `
        -RedirectStandardError (Join-Path $posRoot 'storage\logs\integration-scheduler.stderr.log') | Out-Null
    Write-Output 'POS integration scheduler started.'
}

$existingWebhookWorker = Get-CimInstance Win32_Process -Filter "Name='php.exe'" | Where-Object { $_.CommandLine -like '*artisan integrations:webhooks*' }
if (-not $existingWebhookWorker) {
    Start-Process -FilePath $phpExe -ArgumentList 'artisan','integrations:webhooks' -WorkingDirectory $posRoot -WindowStyle Hidden `
        -RedirectStandardOutput (Join-Path $posRoot 'storage\logs\inventory-webhooks.stdout.log') `
        -RedirectStandardError (Join-Path $posRoot 'storage\logs\inventory-webhooks.stderr.log') | Out-Null
    Write-Output 'Inventory webhook/stock-event worker started.'
}
