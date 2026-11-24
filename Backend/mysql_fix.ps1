Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$dataDir = "C:\xampp\mysql\data"
$backupDir = "C:\xampp\mysql\backup"
$oldDataDir = "C:\xampp\mysql\data_old_$timestamp"

if (Test-Path $dataDir) {
    Write-Host "Renaming current data folder to data_old_$timestamp..."
    Rename-Item -Path $dataDir -NewName "data_old_$timestamp"
} else {
    Write-Host "Error: Cannot find $dataDir"
    exit
}

Write-Host "Creating new data folder from backup..."
Copy-Item -Path $backupDir -Destination $dataDir -Recurse

$exclude = @("mysql", "performance_schema", "phpmyadmin")
$folders = Get-ChildItem -Path $oldDataDir -Directory | Where-Object { $exclude -notcontains $_.Name }

Write-Host "Copying custom database folders..."
foreach ($folder in $folders) {
    Write-Host " -> Copying $($folder.Name)"
    Copy-Item -Path $folder.FullName -Destination "$dataDir\$($folder.Name)" -Recurse -Force
}

Write-Host "Copying ibdata1..."
Copy-Item -Path "$oldDataDir\ibdata1" -Destination "$dataDir\ibdata1" -Force

Write-Host "========================================="
Write-Host "Recovery completed successfully!"
Write-Host "You can now go back to the XAMPP Control Panel and click 'Start' on MySQL."
Write-Host "========================================="
