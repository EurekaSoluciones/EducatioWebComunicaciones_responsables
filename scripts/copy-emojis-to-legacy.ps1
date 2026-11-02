param([switch]$CheckOnly)

$ErrorActionPreference = 'Stop'
$source = Join-Path $PSScriptRoot '..\public\assets\eure\emoji\v1'
$projects = @(
    'D:\Eureka\Educatio\2023\NivelInicial\WebAcademica\WebAcademica-main'
    'D:\Eureka\Educatio\2023\NivelInicial\WebDocentes\WebDocentes-main'
)

# Validate both projects and existing files before copying anything.
$files = @(Get-ChildItem -LiteralPath $source -Filter '*.png' -File)
if ($files.Count -eq 0) { throw 'No se encontraron los emojis de origen.' }
$hashes = @{}
foreach ($file in $files) {
    $hashes[$file.Name] = (Get-FileHash -LiteralPath $file.FullName).Hash
}
$pending = @()
foreach ($project in $projects) {
    if (-not (Test-Path -LiteralPath (Join-Path $project 'artisan') -PathType Leaf)) {
        throw "No se encontro el proyecto: $project"
    }
    $destination = Join-Path $project 'public\assets\eure\emoji\v1'
    $missing = @()
    foreach ($file in $files) {
        $target = Join-Path $destination $file.Name
        if (Test-Path -LiteralPath $target) {
            if ((Get-FileHash -LiteralPath $target).Hash -ne $hashes[$file.Name]) {
                throw "Existe un archivo diferente; no se sobrescribio: $target"
            }
        } else {
            $missing += $file
        }
    }
    $pending += [pscustomobject]@{ Destination = $destination; Missing = $missing }
}

foreach ($item in $pending) {
    if ($CheckOnly) {
        Write-Host "$($item.Missing.Count) archivos pendientes en $($item.Destination)"
        continue
    }
    New-Item -ItemType Directory -Path $item.Destination -Force | Out-Null
    foreach ($file in $item.Missing) {
        Copy-Item -LiteralPath $file.FullName -Destination $item.Destination
    }
    foreach ($file in $files) {
        $target = Join-Path $item.Destination $file.Name
        if ((Get-FileHash -LiteralPath $target).Hash -ne $hashes[$file.Name]) {
            throw "La verificacion fallo: $target"
        }
    }
    Write-Host "OK: $($files.Count) emojis verificados en $($item.Destination)"
}
