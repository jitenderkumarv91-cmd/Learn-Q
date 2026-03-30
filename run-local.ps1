$phpRoot = 'C:\Program Files\php-8.5.4-nts-Win32-vs17-x64'
$phpExe = Join-Path $phpRoot 'php.exe'
$phpIni = Join-Path $PSScriptRoot 'php.ini'

if (-not (Test-Path $phpExe)) {
    Write-Error "php.exe not found at $phpExe"
    exit 1
}

if (-not (Test-Path $phpIni)) {
    Write-Error "Project php.ini not found at $phpIni"
    exit 1
}

Write-Host "Starting ScholarGrid on http://localhost:8000 using $phpExe"
& $phpExe -c $phpIni -S localhost:8000 -t $PSScriptRoot
