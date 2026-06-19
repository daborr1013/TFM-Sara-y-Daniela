# Compress images in media folder
Add-Type -AssemblyName System.Drawing

$mediaPath = "c:\xampp\htdocs\TFM-Sara-y-Daniela\media\images"
$extensions = @("*.png", "*.jpg", "*.jpeg")
$totalSavings = 0

foreach ($extension in $extensions) {
    $files = Get-ChildItem -Path $mediaPath -Filter $extension -ErrorAction SilentlyContinue
    
    foreach ($file in $files) {
        if ($file.Length -gt 300000) {  # Compress files larger than 300KB
            $originalSize = $file.Length
            Write-Host "Compressing $($file.Name) ($('{0:N0}' -f $originalSize) bytes)..."
            
            try {
                $bmp = [System.Drawing.Bitmap]::FromFile($file.FullName)
                
                # Resize to max 800px width
                $maxWidth = 800
                $newWidth = [math]::Min($maxWidth, $bmp.Width)
                $newHeight = [math]::Round($bmp.Height * ($newWidth / $bmp.Width))
                
                $newBmp = New-Object System.Drawing.Bitmap($newWidth, $newHeight)
                $g = [System.Drawing.Graphics]::FromImage($newBmp)
                $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
                $g.DrawImage($bmp, 0, 0, $newWidth, $newHeight)
                $g.Dispose()
                $bmp.Dispose()
                
                $encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object {$_.MimeType -eq 'image/jpeg'}
                $encoderParams = New-Object System.Drawing.Imaging.EncoderParameters(1)
                $encoderParams.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, 85L)
                
                $tempPath = $file.FullName + ".tmp"
                $newBmp.Save($tempPath, $encoder, $encoderParams)
                $newBmp.Dispose()
                
                $newSize = (Get-Item $tempPath).Length
                Remove-Item $file.FullName -Force
                Rename-Item $tempPath -NewName $file.Name
                
                $savings = $originalSize - $newSize
                $totalSavings += $savings
                Write-Host "  ✓ Saved $('{0:N0}' -f $savings) bytes"
            }
            catch {
                Write-Host "  ✗ Error compressing $($file.Name): $_"
            }
        }
    }
}

Write-Host "`nTotal saved: $('{0:N2}' -f ($totalSavings / 1MB)) MB"
Write-Host "Done!"
