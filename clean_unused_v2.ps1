param([switch]$Apply)

$Root    = Get-Location
$Public  = Join-Path $Root "public"
$Archive = Join-Path $Root "_archive_unused"
New-Item -ItemType Directory -Force -Path $Archive | Out-Null

# اجمع ملفات السورس والاصول
$ExcludePaths = @(
  $Archive,
  (Join-Path $Public 'uploads'),
  (Join-Path $Public 'assets')
)

function Test-PathInExclude($path) {
  foreach ($ex in $ExcludePaths) {
    if ($path -like ("$ex*")) { return $true }
  }
  return $false
}

$phpFiles    = Get-ChildItem -Recurse -File -Include *.php | Where-Object { -not (Test-PathInExclude $_.FullName) }
$assetFiles  = Get-ChildItem -Recurse -File -Path $Public -Include *.css,*.js,*.png,*.jpg,*.jpeg,*.gif,*.svg | Where-Object { -not (Test-PathInExclude $_.FullName) }
$sourceFiles = Get-ChildItem -Recurse -File -Include *.php,*.html,*.js | Where-Object { -not (Test-PathInExclude $_.FullName) }

# HashSet<string> متوافق مع PowerShell 5
$referenced = New-Object System.Collections.Generic.HashSet[System.String]

foreach ($sf in $sourceFiles) {
  $content = Get-Content $sf.FullName -Raw

  # asset('path') - simpler: capture inside parentheses then strip quotes
  $m1 = [System.Text.RegularExpressions.Regex]::Matches($content, 'asset\(([^)]+)\)')
  foreach ($m in $m1) {
    $relRaw = $m.Groups[1].Value.Trim()
    $rel = $relRaw.Trim('"' , "'" ).TrimStart('/').Replace('\','/')
    $full = Join-Path $Public $rel
    $rp   = Resolve-Path $full -ErrorAction SilentlyContinue
    if ($rp) { [void]$referenced.Add($rp.Path) }
  }

  # href|src=... - capture value then strip surrounding quotes if any
  $m2 = [System.Text.RegularExpressions.Regex]::Matches($content, '(?:href|src)=\s*([^>\s]+)')
  foreach ($m in $m2) {
    $uRaw = $m.Groups[1].Value.Trim()
    $u = $uRaw.Trim('"' , "'" )
    if ($u -match '^(https?:)?//') { continue }
    $u2 = $u.TrimStart('/').Replace('\','/')
    if ($u2 -like 'public/*') { $u2 = $u2.Substring(7) } # اشطبي public/ في البداية فقط
    $full = Join-Path $Public $u2
    $rp   = Resolve-Path $full -ErrorAction SilentlyContinue
    if ($rp) { [void]$referenced.Add($rp.Path) }
  }

  # include/require - capture inner argument (may include quotes) then strip
  $m3 = [System.Text.RegularExpressions.Regex]::Matches($content, '(?:require|include)(?:_once)?\s*\(\s*([^\)]+)\s*\)')
  foreach ($m in $m3) {
    $incRaw = $m.Groups[1].Value.Trim()
    $inc = $incRaw.Trim('"' , "'" )
    $base = Split-Path $sf.FullName
    $incFull = Join-Path $base $inc
    $rp = Resolve-Path $incFull -ErrorAction SilentlyContinue
    if ($rp) { [void]$referenced.Add($rp.Path) }
  }
}

# اصول غير مُشار اليها
$unusedAssets = $assetFiles | Where-Object {
  $p = (Resolve-Path $_.FullName -ErrorAction SilentlyContinue)
  if (-not $p) { $false } else { -not $referenced.Contains($p.Path) }
}

# index*.php خارج public والتي هي redirect-only
$redirectPatterns = @(
  'header\s*\(\s*["\'']Location:\s*[^"\'']*public/[^"\'']*["\'']\s*\)\s*;?',
  '<meta\s+http-equiv=["\'']refresh["\'']\s+content=["\'']\d+;\s*url=.*public/.*["\'']',
  'window\.location(\.href)?\s*=\s*["\''].*public/.*["\'']'
)

$stubIndexes = Get-ChildItem -Recurse -File -Filter "index*.php" |
  Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.Length -lt 4096 } |
  Where-Object {
    $c = Get-Content $_.FullName -Raw
    foreach ($re in $redirectPatterns) {
      if ($c -match $re) { return $true }
    }
    return $false
  }

# ملفات مكررة بالمحتوى
$dupGroups = Get-ChildItem -Recurse -File -Include *.php,*.css,*.js,*.png,*.jpg,*.jpeg,*.gif,*.svg |
  Get-FileHash -Algorithm SHA256 |
  Group-Object Hash | Where-Object { $_.Count -gt 1 }


# استثناء ملفات index.php الصغيرة (Placeholder guards)
$excludeIndexMaxSize = 2048 # bytes

$duplicateFiles = @()
Write-Host "=== Duplicates (detailed report) ==="
foreach ($g in $dupGroups) {
  Write-Host "--- Hash: $($g.Name)  Count: $($g.Count) ---"
  # حدد الملف الذي سنحتفظ به (فضل نسخة داخل public إذا وجدت)
  $keep = $g.Group | Where-Object { $_.Path -match "\\public\\" } | Select-Object -First 1
  if (-not $keep) { $keep = $g.Group | Select-Object -First 1 }
  Write-Host "Keep: $($keep.Path)"
  Write-Host "Archive candidates:"
  foreach ($item in $g.Group | Where-Object { $_.Path -ne $keep.Path }) {
    $isIndex = ([IO.Path]::GetFileName($item.Path) -ieq 'index.php') -and ((Get-Item $item.Path).Length -lt $excludeIndexMaxSize)
    if ($isIndex) {
      Write-Host "  - $($item.Path)  (excluded: small index.php)"
      # don't archive this one
    } else {
      Write-Host "  - $($item.Path)"
      $duplicateFiles += $item
    }
  }
  Write-Host ""
}

Write-Host "=== Unused Assets ==="
$unusedAssets | Select-Object FullName,Length | Format-Table -Auto

Write-Host "`n=== Redirect-only index (outside public) ==="
$stubIndexes | Select-Object FullName,Length | Format-Table -Auto

if (-not $Apply) {
  Write-Host "`nDry-run only. Re-run with -Apply to MOVE candidates into $Archive"
  exit 0
}

foreach ($f in $unusedAssets + $stubIndexes + $duplicateFiles) {
  try {
    $dest = Join-Path $Archive (Split-Path $f.FullName -Leaf)
    Move-Item -Force -Path $f.FullName -Destination $dest
  } catch {
    Write-Warning "Failed to move: $($f.FullName) -> $dest  ($($_.Exception.Message))"
  }
}

Write-Host "`nMoved candidates to: $Archive"