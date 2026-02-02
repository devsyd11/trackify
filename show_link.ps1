# Extract and display link from sendlink file
$sendlinkFile = "sendlink"

if (Test-Path $sendlinkFile) {
    $content = Get-Content $sendlinkFile -Raw -Encoding UTF8
    
    # Remove ANSI escape codes (regex pattern for ANSI codes)
    $cleanContent = $content -replace '\x1b\[[0-9;]*m', ''
    
    # Extract URL using regex
    $urlPattern = 'https://[^\s]+'
    $match = [regex]::Match($cleanContent, $urlPattern)
    
    if ($match.Success) {
        $link = $match.Value
        Write-Host ""
        Write-Host "[+] Direct link: " -ForegroundColor Yellow -NoNewline
        Write-Host $link -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "[!] Could not extract link" -ForegroundColor Red
        Write-Host ""
        Write-Host "Raw sendlink contents:"
        Write-Host $content
        Write-Host ""
        Write-Host "Cleaned contents (without ANSI codes):"
        Write-Host $cleanContent
    }
} else {
    Write-Host "sendlink file not found!"
}
