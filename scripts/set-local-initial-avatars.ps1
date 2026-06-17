param(
  [Parameter(Mandatory = $false)]
  [string[]] $Folders,

  [Parameter(Mandatory = $false)]
  [string] $FoldersFile,

  [switch] $WhatIf
)

$ErrorActionPreference = 'Stop'

$keys = @(
  'EURE_DEFAULT_AVATAR_PROVIDER_RESPONSABLES',
  'EURE_DEFAULT_AVATAR_PROVIDER_ALUMNOS'
)

function Get-TargetFolders {
  $targets = @()

  if ($Folders) {
    $targets += $Folders
  }

  if ($FoldersFile) {
    if (-not (Test-Path -LiteralPath $FoldersFile)) {
      throw "No existe el archivo de carpetas: $FoldersFile"
    }

    $targets += Get-Content -LiteralPath $FoldersFile |
      ForEach-Object { $_.Trim() } |
      Where-Object { $_ -ne '' -and -not $_.StartsWith('#') }
  }

  if ($targets.Count -eq 0) {
    throw "Indicá carpetas con -Folders o un archivo con -FoldersFile."
  }

  return $targets
}

function Set-EnvValue {
  param(
    [string] $Content,
    [string] $Key,
    [string] $Value
  )

  $pattern = "(?m)^$([regex]::Escape($Key))=.*$"
  $replacement = "$Key=$Value"

  if ($Content -match $pattern) {
    return [regex]::Replace($Content, $pattern, $replacement)
  }

  if ($Content.EndsWith("`n")) {
    return $Content + $replacement + "`n"
  }

  return $Content + "`n" + $replacement + "`n"
}

$targetFolders = Get-TargetFolders

foreach ($folder in $targetFolders) {
  $resolvedFolder = Resolve-Path -LiteralPath $folder -ErrorAction SilentlyContinue

  if (-not $resolvedFolder) {
    Write-Warning "No existe la carpeta: $folder"
    continue
  }

  $envPath = Join-Path -Path $resolvedFolder.Path -ChildPath '.env'

  if (-not (Test-Path -LiteralPath $envPath)) {
    Write-Warning "No existe .env en: $($resolvedFolder.Path)"
    continue
  }

  $original = Get-Content -Raw -LiteralPath $envPath
  $updated = $original

  foreach ($key in $keys) {
    $updated = Set-EnvValue -Content $updated -Key $key -Value 'local_Iniciales'
  }

  if ($updated -eq $original) {
    Write-Host "Sin cambios: $envPath"
    continue
  }

  if ($WhatIf) {
    Write-Host "Simulacion: actualizaria $envPath"
    continue
  }

  Set-Content -LiteralPath $envPath -Value $updated -NoNewline
  Write-Host "Actualizado: $envPath"
}
