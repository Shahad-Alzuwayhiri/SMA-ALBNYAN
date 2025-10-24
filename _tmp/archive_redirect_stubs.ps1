# Archive redirect stub PHP files from repo root into _archive_unused\redirect_stubs
# Created by assistant on 2025-10-19
Set-StrictMode -Version Latest

$Root = 'C:\xampp\htdocs\ContractSama'
Set-Location $Root

$arch = Join-Path $Root '_archive_unused\redirect_stubs'
New-Item -ItemType Directory -Force -Path $arch | Out-Null

# Find small PHP files in repo root that look like public redirect stubs
$stubs = Get-ChildItem -File -Path $Root -Filter *.php |
  Where-Object { $_.DirectoryName -eq $Root -and $_.Length -lt 6KB } |
  Where-Object {
    try {
      $c = Get-Content $_.FullName -Raw -ErrorAction Stop
      # simple heuristics: redirect headers/meta/JS that point to a public/ path
      ($c -match 'Location:\s*.*public/' -or
       $c -match 'refresh.*public' -or
       $c -match 'window\.location.*public')
    } catch {
      $false
    }
  }

if (-not $stubs -or $stubs.Count -eq 0) {
  Write-Host "No redirect stubs found in repo root."
  exit 0
}

$stamp = Get-Date -Format "yyyyMMddHHmmss"
Write-Host "Found $($stubs.Count) stub(s). Archiving to: $arch" -ForegroundColor Cyan
foreach ($f in $stubs) {
  $dest = Join-Path $arch ("{0}.{1}.php" -f $f.BaseName, $stamp)
  Move-Item -Force -Path $f.FullName -Destination $dest
  Write-Host "Archived: $($f.Name) -> $dest"
}

Write-Host "Done." -ForegroundColor Green
