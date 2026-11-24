Write-Host "1. Building frontend..." -ForegroundColor Cyan
Set-Location "c:\Campany Projects\rumu-v2.2\frontend"
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "Build failed! Aborting." -ForegroundColor Red
    exit 1
}

Write-Host "2. Copying build artifacts to rumu-frontend-repo..." -ForegroundColor Cyan
$repoDir = "c:\Campany Projects\rumu-v2.2\rumu-frontend-repo"
Copy-Item -Path "dist\*" -Destination $repoDir -Recurse -Force
Copy-Item -Path "dist\.htaccess" -Destination $repoDir -Force

Write-Host "3. Committing and pushing to GitHub (RumuFrontEnd)..." -ForegroundColor Cyan
Set-Location $repoDir
git add -A
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm"
git commit -m "Production build - $timestamp"
git push origin main

Write-Host "Done! Frontend pushed to GitHub." -ForegroundColor Green
