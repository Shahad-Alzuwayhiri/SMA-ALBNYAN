param(
  [switch]$Apply  # إذا مررتي -Apply ينفّذ النقل للأرشيف
)

$Root    = Get-Location
$Public  = Join-Path $Root "public"
$Archive = Join-Path $Root "_archive_unused"
if ($Apply -and !(Test-Path $Archive)) { New-Item -ItemType Directory -Force -Path $Archive | Out-Null }

# 1) اجمعي كل الملفات المرشحة للمراجعة
$phpFiles  = Get-ChildItem -Recurse -File -Include *.php
$assetFiles = Get-ChildItem -Recurse -File -Path $Public -Include *.css,*.js,*.png,*.jpg,*.jpeg,*.gif,*.svg

# 2) ابنِي قائمة "مُشار إليها" (Referenced) بالبحث في السورس
$sourceFiles = Get-ChildItem -Recurse -File -Include *.php,*.html,*.js
$referenced = [System.Collections.Generic.HashSet[string]]::new()

foreach ($sf in $sourceFiles) {
  $content = Get-Content $sf.FullName -Raw

  # أصول عبر asset('path')
  $matches = [regex]::Matches($content, "asset\(['\"]([^'\"]+)['\"]\)")
  foreach ($m in $matches) {
    $rel = $m.Groups[1].Value.TrimStart('/').Replace('\','/')
    $full = Join-Path $Public $rel
    $resolved = Resolve-Path $full -ErrorAction SilentlyContinue
    if ($resolved) { $referenced[$resolved] = $true }
  }

  # href/src مباشر
  [regex]::Matches($content, "(?:href|src)=['\"]([^'\"]+)['\"]", "IgnoreCase") | ForEach-Object {
    $u = $_.Groups[1].Value
    if ($u -match "^(https?:)?//") { return }
    $u2 = $u.TrimStart('/').Replace('\','/')
    if ($u2 -like "public/*") { $u2 = $u2.Substring(7) }
    $full = Join-Path $Public $u2
    $referenced.Add((Resolve-Path $full -ErrorAction SilentlyContinue)) | Out-Null
  }

  # include/require/require_once لمسارات PHP
  [regex]::Matches($content, "(require|include)(_once)?\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", "IgnoreCase") | ForEach-Object {
    $inc = $_.Groups[3].Value
    $base = Split-Path $sf.FullName
    $incFull = Resolve-Path (Join-Path $base $inc) -ErrorAction SilentlyContinue
    if ($incFull) { $referenced.Add($incFull) | Out-Null }
  }
}

# 3) اكتشفي الأصول غير المُشار إليها داخل public/
$unusedAssets = @()
foreach ($asset in $assetFiles) {
  $resolved = Resolve-Path $asset.FullName -ErrorAction SilentlyContinue
  if (-not $referenced.ContainsKey($resolved)) {
    $unusedAssets += $asset
  }
}

# 4) اكتشفي صفحات index المكررة خارج public/ وتوجيهات قديمة
$redirectPatterns = @(
  'header\s*\(\s*["\']Location:\s*[^"\']*public/[^"\']*["\']\s*\)\s*;?',
  '<meta\s+http-equiv=["\']refresh["\']\s+content=["\']\d+;\s*url=.*public/.*["\']',
  'window\.location(\.href)?\s*=\s*["\'].*public/.*["\']'
)
$stubIndexes = @()
$indexFiles = Get-ChildItem -Recurse -File -Filter "index*.php" | Where-Object { $_.DirectoryName -notlike "*\public*" -and $_.Length -lt 4096 }
foreach ($f in $indexFiles) {
  $c = Get-Content $f.FullName -Raw
  foreach ($pat in $redirectPatterns) {
    if ($c -match $pat) {
      $stubIndexes += $f
      break
    }
  }
}

# 5) اكتشفي ملفات مكرّرة بالمحتوى (SHA256) — نحتفظ بنسخة داخل public إن وُجدت
$dupGroups = Get-ChildItem -Recurse -File -Include *.php,*.css,*.js,*.png,*.jpg,*.jpeg,*.gif,*.svg |
  Get-FileHash -Algorithm SHA256 |
  Group-Object Hash | Where-Object { $_.Count -gt 1 }

$duplicateFiles = @()
foreach ($g in $dupGroups) {
  $keep = $g.Group | Sort-Object { ($_ -match "\\public\\") ? 0 : 1 }, Path | Select-Object -First 1
  $toArchive = $g.Group | Where-Object { $_.Path -ne $keep.Path }
  $duplicateFiles += $toArchive
}

# 6) تقرير
Write-Host "=== Unused Assets (not referenced) ==="
$unusedAssets | Select-Object FullName,Length | Format-Table -Auto

Write-Host "`n=== Redirect-only index files (outside public) ==="
$stubIndexes | Select-Object FullName,Length | Format-Table -Auto

Write-Host "`n=== Duplicates by content (to archive) ==="
$duplicateFiles | Select-Object Path | Format-Table -Auto

if (-not $Apply) {
  Write-Host "`nDry-run only. Re-run with -Apply to MOVE candidates into $Archive"
  exit 0
}

# 7) نفّذي الأرشفة (Move-Item) بأمان
foreach ($f in $unusedAssets + $stubIndexes + $duplicateFiles) {
  try {
    $dest = Join-Path $Archive (Split-Path $f.FullName -Leaf)
    Move-Item -Force -Path $f.FullName -Destination $dest
  } catch {
    Write-Warning "Failed to move: $($f.FullName) -> $dest  ($($_.Exception.Message))"
  }
}

Write-Host "`nMoved candidates to: $Archive"
Write-Host "راجع الأرشيف. إذا كل شيء تمام، بإمكانك حذف المجلد لاحقًا."
