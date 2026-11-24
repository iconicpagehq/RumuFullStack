Add-Type -AssemblyName System.IO.Compression.FileSystem
$root = $PSScriptRoot
if (-not $root) { $root = Get-Location }
$src = Join-Path $root 'plugin-staging'
$dst = Join-Path $root 'rumu-mcp-plugin.zip'
if (Test-Path $dst) { Remove-Item $dst -Force }
$zip = [System.IO.Compression.ZipFile]::Open($dst, 'Create')
Get-ChildItem -Recurse -Force $src | Where-Object { -not $_.PSIsContainer } | ForEach-Object {
  $rel = $_.FullName.Substring($src.Length + 1) -replace '\\', '/'
  [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel) | Out-Null
}
$zip.Dispose()
Write-Output "zip written to $dst"
