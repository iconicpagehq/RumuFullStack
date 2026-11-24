<# :
@echo off
title Rumu Test
powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-Expression ((Get-Content '%~f0') -join [Environment]::NewLine)"
pause
exit /b
#>
Write-Host "Hello from clean PowerShell!" -ForegroundColor Green
