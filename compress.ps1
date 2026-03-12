Add-Type -AssemblyName System.Drawing

$files = Get-ChildItem -Path "c:\Users\SARA\Documents\software\htdocs\tfm\TFM-Sara-y-Daniela\media\images" -Filter *.png
foreach ($file in $files) {
    if ($file.Length -gt 500000) {
        Write-Host "Compressing $($file.Name)..."
        $bmp = [System.Drawing.Bitmap]::FromFile($file.FullName)
        
        $newWidth = 500
        $newHeight = [math]::Round($bmp.Height * (500 / $bmp.Width))
        
        $newBmp = New-Object System.Drawing.Bitmap($newWidth, $newHeight)
        $g = [System.Drawing.Graphics]::FromImage($newBmp)
        $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $g.DrawImage($bmp, 0, 0, $newWidth, $newHeight)
        $g.Dispose()
        $bmp.Dispose()
        
        $tempPath = $file.FullName + ".tmp"
        $newBmp.Save($tempPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
        $newBmp.Dispose()
        
        Remove-Item $file.FullName -Force
        Rename-Item $tempPath -NewName $file.Name
    }
}
WriteLine "Done!"
