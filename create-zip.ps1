# Compress entire project to ZIP
$projectPath = "c:\xampp\htdocs\TFM-Sara-y-Daniela"
$outputPath = "c:\xampp\htdocs\TFM-Sara-y-Daniela.zip"

Write-Host "Creating compressed ZIP file..."
Write-Host "Source: $projectPath"
Write-Host "Output: $outputPath"
Write-Host ""

# Remove old zip if exists
if (Test-Path $outputPath) {
    Remove-Item $outputPath -Force
    Write-Host "Removed old ZIP file"
}

# Create new ZIP with maximum compression
Compress-Archive -Path "$projectPath\*" -DestinationPath $outputPath -CompressionLevel Optimal -Force

if (Test-Path $outputPath) {
    $zipSize = (Get-Item $outputPath).Length
    $zipSizeMB = [math]::Round($zipSize / 1MB, 2)
    Write-Host "✓ ZIP created successfully!"
    Write-Host "File size: $zipSizeMB MB"
    Write-Host "File location: $outputPath"
} else {
    Write-Host "✗ Error creating ZIP file"
}
