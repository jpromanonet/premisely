# Deploy Premisely to W:\premisely (192.168.100.50 web root)

$ErrorActionPreference = 'Stop'
$src = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $src 'index.php'))) {
    $src = $PSScriptRoot
    if (-not (Test-Path (Join-Path $src 'index.php'))) {
        $src = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
    }
}
$dest = 'W:\premisely'

Write-Host "Source: $src"
Write-Host "Dest:   $dest"

if (-not (Test-Path 'W:\')) {
    throw 'W: drive is not available'
}

New-Item -ItemType Directory -Force -Path $dest | Out-Null

$exclude = @('.git', 'scripts\deploy-to-w.ps1')
$robolog = Join-Path $env:TEMP 'premisely-deploy.log'

# Mirror project but keep storage uploads/logs if already present on server
robocopy $src $dest /E /XD .git /XF .env /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
# Always copy .env for this local server deployment
Copy-Item (Join-Path $src '.env') (Join-Path $dest '.env') -Force

# Ensure writable storage dirs
@(
    'storage\cache',
    'storage\logs',
    'storage\sessions',
    'storage\uploads',
    'storage\tmp'
) | ForEach-Object {
    New-Item -ItemType Directory -Force -Path (Join-Path $dest $_) | Out-Null
}

Write-Host "Deployed. Open http://192.168.100.50/premisely/index.php?r=/install"
