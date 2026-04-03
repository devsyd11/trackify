@echo off
echo.
echo [*] Extracting link from sendlink file...
echo.
findstr /R "https://.*serveousercontent.com" sendlink
if errorlevel 1 (
    findstr /R "https://.*serveo.net" sendlink
)
echo.
