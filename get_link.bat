@echo off
REM Extract and display link from sendlink file using Windows batch

if exist sendlink (
    echo.
    echo [*] Extracting link from sendlink file...
    echo.
    
    REM Use findstr to extract the URL line
    findstr /R "https://.*serveousercontent.com" sendlink >nul 2>&1
    if %errorlevel% equ 0 (
        for /f "tokens=*" %%a in ('findstr /R "https://.*serveousercontent.com" sendlink') do (
            set "line=%%a"
            echo !line!
        )
    ) else (
        findstr /R "https://.*serveo.net" sendlink
    )
    echo.
) else (
    echo sendlink file not found!
)
