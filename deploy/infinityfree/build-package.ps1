Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$deployRoot = Join-Path $projectRoot 'deploy\infinityfree'
$packageRoot = Join-Path $deployRoot 'upload_package'
$htdocsRoot = Join-Path $packageRoot 'htdocs'
$appRoot = Join-Path $packageRoot 'vilocare_app'
$htdocsZip = Join-Path $deployRoot 'htdocs_upload.zip'
$appZip = Join-Path $deployRoot 'vilocare_app_upload.zip'

function Reset-Directory {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path
    )

    if (Test-Path -LiteralPath $Path) {
        Remove-Item -LiteralPath $Path -Recurse -Force
    }

    New-Item -ItemType Directory -Path $Path | Out-Null
}

function Copy-IfExists {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Source,
        [Parameter(Mandatory = $true)]
        [string] $Destination
    )

    if (Test-Path -LiteralPath $Source) {
        Copy-Item -LiteralPath $Source -Destination $Destination -Recurse -Force
    }
}

Reset-Directory -Path $packageRoot
New-Item -ItemType Directory -Path $htdocsRoot | Out-Null
New-Item -ItemType Directory -Path $appRoot | Out-Null

$appItems = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'resources',
    'routes',
    'storage',
    'vendor',
    'artisan',
    'composer.json',
    'composer.lock'
)

foreach ($item in $appItems) {
    Copy-IfExists -Source (Join-Path $projectRoot $item) -Destination $appRoot
}

$publicItems = @(
    '.htaccess',
    'build',
    'css',
    'images',
    'storage',
    'uploads',
    'android-chrome-192x192.png',
    'android-chrome-512x512.png',
    'apple-touch-icon.png',
    'favicon-16x16.png',
    'favicon.png',
    'robots.txt',
    'site.webmanifest'
)

foreach ($item in $publicItems) {
    Copy-IfExists -Source (Join-Path $projectRoot "public\$item") -Destination $htdocsRoot
}

Copy-Item -LiteralPath (Join-Path $deployRoot 'htdocs-index.php') -Destination (Join-Path $htdocsRoot 'index.php') -Force

if (Test-Path -LiteralPath $htdocsZip) {
    Remove-Item -LiteralPath $htdocsZip -Force
}

if (Test-Path -LiteralPath $appZip) {
    Remove-Item -LiteralPath $appZip -Force
}

Compress-Archive -Path (Join-Path $htdocsRoot '*') -DestinationPath $htdocsZip -Force
Compress-Archive -Path (Join-Path $appRoot '*') -DestinationPath $appZip -Force

Write-Host "InfinityFree package updated:"
Write-Host " - $htdocsRoot"
Write-Host " - $appRoot"
Write-Host " - $htdocsZip"
Write-Host " - $appZip"
