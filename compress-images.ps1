# Simple image compression using built-in tools
$imagePath = "c:\xampp\htdocs\TFM-Sara-y-Daniela\media\images"
$totalSavings = 0

Write-Host "Starting image compression in: $imagePath"
Write-Host ""

# Get all image files
$imageFiles = @(Get-ChildItem -Path $imagePath -Filter "*.png" -ErrorAction SilentlyContinue) + 
              @(Get-ChildItem -Path $imagePath -Filter "*.jpg" -ErrorAction SilentlyContinue) +
              @(Get-ChildItem -Path $imagePath -Filter "*.jpeg" -ErrorAction SilentlyContinue)

foreach ($file in $imageFiles) {
    $sizeBefore = $file.Length
    
    if ($sizeBefore -gt 300000) {  # Only compress files > 300KB
        Write-Host "Processing: $($file.Name) ($('{0:N0}' -f $sizeBefore) bytes)"
        
        # Use ImageMagick if available
        $magickPath = "C:\Program Files\ImageMagick-7*\magick.exe"
        $magick = Get-Item $magickPath -ErrorAction SilentlyContinue | Select-Object -First 1
        
        if ($magick) {
            $tempFile = "$($file.FullName).tmp"
            & $magick.FullName $file.FullName -resize 800x600 -quality 85 $tempFile 2>$null
            
            if (Test-Path $tempFile) {
                $sizeAfter = (Get-Item $tempFile).Length
                Remove-Item $file.FullName -Force
                Move-Item $tempFile -Destination $file.FullName -Force
                $savings = $sizeBefore - $sizeAfter
                $totalSavings += $savings
                Write-Host "  ✓ Compressed to $('{0:N0}' -f $sizeAfter) bytes (saved: $('{0:N0}' -f $savings))"
            }
        } else {
            Write-Host "  ⚠ ImageMagick not found - skipping"
        }
    }
}

Write-Host ""
Write-Host "Total saved: $('{0:N2}' -f ($totalSavings / 1MB)) MB"
Write-Host "Done!"
