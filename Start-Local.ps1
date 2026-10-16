$ErrorActionPreference = 'Stop'
$project = $PSScriptRoot
Set-Location -LiteralPath $project
if (-not (Test-Path -LiteralPath "$project\.env")) { throw 'Configure the application environment first. See README.md.' }
$php = (Get-Command php).Source
$databaseListening = Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
if (-not $databaseListening) {
    Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList "--defaults-file=$project\storage\mysql\my.ini",'--bind-address=127.0.0.1','--port=3307' -WorkingDirectory "$project\storage\mysql" -WindowStyle Hidden
    Start-Sleep -Seconds 2
}
if (-not (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $php -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -WorkingDirectory $project -WindowStyle Hidden -RedirectStandardOutput "$project\storage\logs\server-output.log" -RedirectStandardError "$project\storage\logs\server-error.log"
}
$queuePidFile = "$project\storage\app\queue-worker.pid"
$existingWorker = if(Test-Path -LiteralPath $queuePidFile){Get-Process -Id ([int](Get-Content -LiteralPath $queuePidFile)) -ErrorAction SilentlyContinue}else{$null}
if (-not $existingWorker) {
    $worker = Start-Process -FilePath $php -ArgumentList 'artisan','queue:work','--sleep=2','--tries=5','--timeout=90' -WorkingDirectory $project -WindowStyle Hidden -PassThru -RedirectStandardOutput "$project\storage\logs\queue-output.log" -RedirectStandardError "$project\storage\logs\queue-error.log"
    $worker.Id | Set-Content -LiteralPath $queuePidFile
}
Write-Output 'Website: http://127.0.0.1:8000'
Write-Output 'CMS: http://127.0.0.1:8000/admin'
