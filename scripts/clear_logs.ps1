<# Windows PowerShell script to clear Laravel log files stored under storage/logs #>
Remove-Item -Path "storage\logs\*.log" -Force -ErrorAction SilentlyContinue
Write-Host "Laravel logs cleared (storage/logs/*.log)" -ForegroundColor Green
