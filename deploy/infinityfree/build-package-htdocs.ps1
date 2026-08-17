Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$deployRoot = Join-Path $projectRoot 'deploy\infinityfree'
$packageRoot = Join-Path $deployRoot 'upload_package_htdocs'
$htdocsRoot = Join-Path $packageRoot 'htdocs'
$htdocsZip = Join-Path $deployRoot 'htdocs_full_app_upload.zip'

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
    Copy-IfExists -Source (Join-Path $projectRoot $item) -Destination $htdocsRoot
}

$publicItems = @(
    '.htaccess',
    'build',
    'css',
    'images',
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

# Copy the actual public storage contents instead of the local junction/symlink.
$publicStorageSource = Join-Path $projectRoot 'storage\app\public'
$publicStorageDestination = Join-Path $htdocsRoot 'storage'
if (Test-Path -LiteralPath $publicStorageSource) {
    if (-not (Test-Path -LiteralPath $publicStorageDestination)) {
        New-Item -ItemType Directory -Path $publicStorageDestination | Out-Null
    }

    Get-ChildItem -LiteralPath $publicStorageSource -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $publicStorageDestination -Recurse -Force
    }
}

Copy-Item -LiteralPath (Join-Path $deployRoot 'htdocs-root-index.php') -Destination (Join-Path $htdocsRoot 'index.php') -Force
Copy-Item -LiteralPath (Join-Path $deployRoot 'env.production.example') -Destination (Join-Path $htdocsRoot '.env.example') -Force

if (Test-Path -LiteralPath $htdocsZip) {
    Remove-Item -LiteralPath $htdocsZip -Force
}

Compress-Archive -Path (Join-Path $htdocsRoot '*') -DestinationPath $htdocsZip -Force

Write-Host "InfinityFree all-in-htdocs package updated:"
Write-Host " - $htdocsRoot"
Write-Host " - $htdocsZip"
