# Update Tippy.js and Popper.js vendor libraries
# Run this script from the vendor folder to download and patch the latest versions

$ErrorActionPreference = "Stop"

Write-Host "Downloading Popper.js..." -ForegroundColor Cyan
Invoke-WebRequest -Uri "https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js" -OutFile "popper.js"

Write-Host "Downloading Tippy.js..." -ForegroundColor Cyan
Invoke-WebRequest -Uri "https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.min.js" -OutFile "tippy.js"

Write-Host "Patching Popper.js (removing AMD detection)..." -ForegroundColor Yellow
$popperContent = Get-Content "popper.js" -Raw
# Remove the AMD define branch so it falls through to global
$popperContent = $popperContent -replace '"function"==typeof define&&define\.amd\?define\(\["exports"\],t\):', ''
Set-Content "popper.js" -Value $popperContent -NoNewline

Write-Host "Patching Tippy.js (removing AMD detection)..." -ForegroundColor Yellow
$tippyContent = Get-Content "tippy.js" -Raw
# Remove the AMD define branch so it falls through to global
$tippyContent = $tippyContent -replace '"function"==typeof define&&define\.amd\?define\(\["@popperjs/core"\],e\):', ''
Set-Content "tippy.js" -Value $tippyContent -NoNewline

Write-Host ""
Write-Host "Done! Libraries updated and patched." -ForegroundColor Green
Write-Host ""
Write-Host "Note: The AMD detection patterns may change in future versions." -ForegroundColor DarkYellow
Write-Host "If patching fails, manually check the UMD wrapper and remove the 'define.amd' branch." -ForegroundColor DarkYellow

