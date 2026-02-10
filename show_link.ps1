# Extract and display link from sendlink file — hack-style terminal (green/cyan/amber)
$sendlinkFile = "sendlink"

if (Test-Path $sendlinkFile) {
    $content = Get-Content $sendlinkFile -Raw -Encoding UTF8
    $cleanContent = $content -replace '\x1b\[[0-9;]*m', ''
    $urlPattern = 'https://[^\s]+'
    $match = [regex]::Match($cleanContent, $urlPattern)

    if ($match.Success) {
        $link = $match.Value
        Write-Host ""
        Write-Host "[+] Direct link: " -ForegroundColor Cyan -NoNewline
        Write-Host $link -ForegroundColor Green
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "[!] Could not extract link" -ForegroundColor Red
        Write-Host ""
        Write-Host "Raw sendlink contents:" -ForegroundColor DarkGray
        Write-Host $content
        Write-Host ""
        Write-Host "Cleaned contents (without ANSI codes):" -ForegroundColor DarkGray
        Write-Host $cleanContent
    }
} else {
    Write-Host "[!] sendlink file not found!" -ForegroundColor Red
}
