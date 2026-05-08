$names = 'anti-hotlink','chatgpt','chrome-extension','custom-model','touch-area','v1-vs-v2'
$langDirs = @{
    'zh-CN' = 'd:\live-2d\trunk\docs\wiki';
    'en'    = 'd:\live-2d\trunk\docs\en\wiki';
    'ja'    = 'd:\live-2d\trunk\docs\ja\wiki';
    'zh-TW' = 'd:\live-2d\trunk\docs\zh-TW\wiki'
}
$dataDir = 'd:\live-2d\trunk\docs\_data\wiki'
if (-not (Test-Path $dataDir)) { New-Item -ItemType Directory -Path $dataDir | Out-Null }

function Split-Front($path) {
    $text = [System.IO.File]::ReadAllText($path)
    if ($text -notmatch "^---\r?\n") { throw "no frontmatter: $path" }
    $rx = [regex]'(?ms)^---\r?\n(.*?)\r?\n---\r?\n(.*)$'
    $m = $rx.Match($text)
    if (-not $m.Success) { throw "broken frontmatter: $path" }
    @{ Front = $m.Groups[1].Value; Body = $m.Groups[2].Value }
}

function Indent2([string]$s) {
    $s = $s -replace "[\r\n]+$", ""
    ($s -split "\r?\n" | ForEach-Object { '  ' + $_ }) -join "`n"
}

foreach ($n in $names) {
    Write-Host "==> $n"
    $front = $null
    $bodies = @{}
    foreach ($lang in 'zh-CN','en','ja','zh-TW') {
        $f = Join-Path $langDirs[$lang] "$n.html"
        if (-not (Test-Path $f)) { Write-Host "  SKIP missing $lang"; continue }
        $r = Split-Front $f
        if ($null -eq $front) { $front = $r.Front }
        $bodies[$lang] = $r.Body
    }
    if ($null -eq $front) { Write-Host "  no langs found, skip"; continue }

    $sb = New-Object System.Text.StringBuilder
    [void]$sb.AppendLine("# Multi-language body for /wiki/$n.html (and /<lang>/wiki/$n.html).")
    [void]$sb.AppendLine("# Edit here, the 4 stub HTML files just include this via _includes/wiki-body.html.")
    foreach ($lang in 'zh-CN','en','ja','zh-TW') {
        if (-not $bodies.ContainsKey($lang)) { continue }
        [void]$sb.AppendLine("'$lang': |")
        [void]$sb.AppendLine((Indent2 $bodies[$lang]))
    }
    $yamlPath = Join-Path $dataDir "$n.yml"
    [System.IO.File]::WriteAllText($yamlPath, $sb.ToString(), (New-Object System.Text.UTF8Encoding $false))
    Write-Host "  wrote $yamlPath ($((Get-Item $yamlPath).Length) bytes)"

    $stub = "---`n$front`nbody_key: $n`n---`n{% include wiki-body.html name=page.body_key %}`n"
    foreach ($lang in 'zh-CN','en','ja','zh-TW') {
        if (-not $bodies.ContainsKey($lang)) { continue }
        $f = Join-Path $langDirs[$lang] "$n.html"
        [System.IO.File]::WriteAllText($f, $stub, (New-Object System.Text.UTF8Encoding $false))
    }
    Write-Host "  rewrote 4 stubs"
}
Write-Host "DONE"
