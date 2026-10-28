<#
=============================================================================
 SAE - PHP 8.3 Runner
-----------------------------------------------------------------------------
PATH di mesin dev punya entri Laragon yang basi, sehingga `php` menunjuk ke
PHP 8.1 (LoaderSAE) dan PHPUnit menolak jalan (butuh >= 8.3). Skrip ini
mencari PHP >= 8.3 secara eksplisit, lalu meneruskan argumen ke php/artisan/
composer/phpunit apa adanya.

 Pemakaian:
     powershell -File run-php.ps1 -v                 # versi PHP
     powershell -File run-php.ps1 artisan migrate     # php artisan migrate
     powershell -File run-php.ps1 test                # seluruh test
     powershell -File run-php.ps1 test tests/Feature/Laboran
     powershell -File run-php.ps1 composer install
     powershell -File run-php.ps1 php -r "echo 1;"    # php mentah

 Skrip ini HANYA memilih interpreter; tidak mengubah PATH sistem.
=============================================================================
#>
[CmdletBinding()]
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Argv
)

$ErrorActionPreference = 'Stop'
$MinVersion  = [version]'8.3'
$Root        = $PSScriptRoot

# ---------------------------------------------------------------------------
# Kandidat PHP: entri PATH (yang valid) + lokasi Laragon yang dikenal.
# ---------------------------------------------------------------------------
$candidates = [System.Collections.Generic.List[string]]::new()

# Tambahkan kandidat dari direktori PATH yang namanya mengandung "php".
foreach ($dir in ($env:PATH -split ';')) {
    if ([string]::IsNullOrWhiteSpace($dir)) { continue }
    if ($dir -notmatch 'php') { continue }
    $exe = Join-Path $dir 'php.exe'
    if (Test-Path -LiteralPath $exe) { $candidates.Add($exe) }
}

# Lokasi Laragon standar (pola php-8.<minor>.*-Win32-*).
foreach ($base in @('C:\laragon\bin\php', "$env:USERPROFILE\laragon\bin\php")) {
    if (-not (Test-Path -LiteralPath $base)) { continue }
    Get-ChildItem -LiteralPath $base -Directory -Filter 'php-*' -ErrorAction SilentlyContinue |
        ForEach-Object {
            $exe = Join-Path $_.FullName 'php.exe'
            if (Test-Path -LiteralPath $exe) { $candidates.Add($exe) }
        }
}

# ---------------------------------------------------------------------------
# Pilih versi tertinggi yang >= 8.3.
# ---------------------------------------------------------------------------
$chosen    = $null
$seen      = @{}
$evaluated = @()

foreach ($exe in $candidates) {
    if ($seen.ContainsKey($exe)) { continue }
    $seen[$exe] = $true

    $raw = & $exe -r 'echo PHP_VERSION;' 2>$null
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($raw)) { continue }

    $ver = $null
    if (-not [version]::TryParse($raw.Trim(), [ref]$ver)) { continue }

    $evaluated += [pscustomobject]@{ Path = $exe; Version = $ver }
    if ($ver -ge $MinVersion -and ($null -eq $chosen -or $ver -gt $chosen.Version)) {
        $chosen = [pscustomobject]@{ Path = $exe; Version = $ver }
    }
}

if ($null -eq $chosen) {
    Write-Host 'Tidak menemukan PHP >= 8.3.' -ForegroundColor Red
    if ($evaluated.Count -gt 0) {
        Write-Host 'Kandidat yang diperiksa:' -ForegroundColor Yellow
        $evaluated | ForEach-Object { Write-Host ("  {0}  -> PHP {1}" -f $_.Path, $_.Version) }
    } else {
        Write-Host 'Tidak ada php.exe yang bisa dibaca di PATH maupun folder Laragon.' -ForegroundColor Yellow
    }
    Write-Host 'Pasang PHP 8.3+ (mis. via Laragon: Menu > PHP > Version) lalu coba lagi.' -ForegroundColor Yellow
    exit 1
}

$php  = $chosen.Path
$args = @($Argv)

# Slice aman: `$a[1..($a.Count-1)]` terbalik saat Count=1 (range 1..0), jadi
# jangan pakai range; buang elemen pertama secara eksplisit.
function Rest([string[]] $a) {
    if ($a.Count -le 1) { return ,@() }
    return ,@($a[1..($a.Count - 1)])
}

# ---------------------------------------------------------------------------
# Perintah ringkas -> perintah sebenarnya.
# ---------------------------------------------------------------------------
if ($args.Count -eq 0) {
    $args = @('--version')
} elseif ($args[0] -eq 'test') {
    $args = @('vendor\bin\phpunit') + (Rest $args)
} elseif ($args[0] -eq 'artisan') {
    $args = @('artisan') + (Rest $args)
} elseif ($args[0] -eq 'php') {
    $args = @(Rest $args)
    if ($args.Count -eq 0) { $args = @('--version') }
} elseif ($args[0] -eq 'composer') {
    $composer = Join-Path $Root 'composer.phar'
    $rest     = Rest $args
    if (Test-Path -LiteralPath $composer) {
        $args = @($composer) + $rest
    } else {
        Write-Host 'composer.phar tidak ada; menjalankan composer dari PATH.' -ForegroundColor Yellow
        & composer @rest
        exit $LASTEXITCODE
    }
}

Write-Host ("PHP {0} ({1})" -f $chosen.Version, $php) -ForegroundColor DarkGray
Push-Location -LiteralPath $Root
try {
    & $php @args
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
