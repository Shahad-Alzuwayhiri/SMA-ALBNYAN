param(
  [switch]$Apply,                         # Apply the move; without it = Dry-run
  [string]$Root = ".",                    # Search root
  [int]$MaxKB = 12,                       # Max size in KB for auto-detect stubs
  [string[]]$Include                      # Filenames to include manually (optional)
)

$here = Resolve-Path $Root
Set-Location $here

$arch = Join-Path (Get-Location) "_archive_unused\redirect_stubs"
New-Item -ItemType Directory -Force -Path $arch | Out-Null

function Is-RedirectStub {
  param([string]$Path)

  try { $content = Get-Content $Path -Raw -ErrorAction Stop } catch { return $false }

  # Simpler, robust checks using substring matching to avoid complex regex quoting
  if ($content -like '*public/*') {
    if ($content -like '*Location:*' -or $content -like '*window.location*' -or $content -like '*http-equiv*refresh*') {
      return $true
    }
  }

  if ($content -like '*Moved to _archive_redirect_stubs*' -or $content -like '*Redirect to Public Folder*') {
    return $true
  }

  # File might be a very small placeholder
  $len = (Get-Item $Path).Length
  if ($len -le ($MaxKB * 1024)) {
    if ($content -match '^\s*<\?php' -or $content -match '^\s*<!DOCTYPE') { return $true }
  }

  return $false
}

# 1) prepare candidate list
$candidates = @()

# (a) manual includes
if ($Include -and $Include.Count -gt 0) {
  foreach ($name in $Include) {
    $f = Join-Path (Get-Location) $name
    if (Test-Path $f -PathType Leaf) {
      $candidates += (Get-Item $f)
    } else {
      Write-Warning "Not found (Include): $name"
    }
  }
}

# (b) automatic detection in repo root
$rootPhp = Get-ChildItem -File -Path . -Filter *.php |
           Where-Object { $_.DirectoryName -eq (Get-Location).Path }

foreach ($f in $rootPhp) {
  if ($Include -and ($Include -contains $f.Name)) { continue }
  if ($f.Name -ieq "index.php") { continue }
  if (Is-RedirectStub -Path $f.FullName) {
    $candidates += $f
  }
}

# 2) filter (no-op placeholder here but kept for future rules)
$candidates = $candidates | Where-Object { $_.Name -notmatch '^\s*$' }

# 3) show candidates
if (-not $candidates -or $candidates.Count -eq 0) {
  Write-Host "No redirect stubs found in repo root."
  if ($Include) { Write-Host "(Includes processed; none found/valid)" }
  exit 0
}

Write-Host "Candidates:`n"
$candidates | Select-Object Name, Length, FullName | Format-Table -Auto

# 4) apply or dry-run
if (-not $Apply) {
  Write-Host "`nDry-run only. Re-run with -Apply to MOVE candidates into $arch"
  exit 0
}

$stamp = Get-Date -Format "yyyyMMddHHmmss"
foreach ($f in $candidates) {
  $dest = Join-Path $arch ("{0}.{1}.php" -f ($f.BaseName), $stamp)
  try {
    Move-Item -Force -Path $f.FullName -Destination $dest
    Write-Host "Archived: $($f.Name) -> $dest"
  } catch {
    Write-Warning "Failed to move: $($f.FullName) -> $dest  ($($_.Exception.Message))"
  }
}

Write-Host "Done." -ForegroundColor Green
