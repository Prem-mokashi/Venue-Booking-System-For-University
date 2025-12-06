# Enable VTU Logo in PDF - Step by Step Instructions

## Current Status
- ✅ **Kannada text added**: VTU full form now appears in Kannada
- ✅ **Logo code ready**: PDF will automatically use actual VTU logo once GD extension is enabled
- ⚠️ **GD Extension needed**: Currently using text logo because GD extension is not enabled

## To Enable Actual VTU Logo Image:

### Method 1: Use the Batch File (Easiest)
1. **Run the batch file**: Double-click `enable_gd_extension.bat` in the project folder
2. **Restart Apache**: Go to XAMPP Control Panel and restart Apache
3. **Test PDF**: Generate a new PDF - it will now use the actual VTU logo!

### Method 2: Manual Configuration
1. **Open php.ini file**: 
   - Navigate to `C:\xampp\php\php.ini`
   - Open in Notepad or any text editor

2. **Find GD extension line**:
   - Search for `;extension=gd`
   - Remove the semicolon (`;`) to make it: `extension=gd`

3. **Save and restart**:
   - Save the php.ini file
   - Restart Apache in XAMPP Control Panel

4. **Verify**:
   - Create a test file with `<?php phpinfo(); ?>` 
   - Look for "GD" section to confirm it's loaded

## What Happens After Enabling GD:

### ✅ **With GD Extension Enabled:**
- **Real VTU Logo**: Uses actual `vtu_logo.png` from images folder
- **Professional Appearance**: High-quality logo image
- **Perfect Scaling**: Logo maintains quality at any size

### 📄 **Current PDF Features:**
- ✅ **VTU Full Form in Kannada**: ವಿಶ್ವೇಶ್ವರಯ್ಯ ತಾಂತ್ರಿಕ ವಿಶ್ವವಿದ್ಯಾಲಯ
- ✅ **Bilingual Subtitle**: ಸ್ಥಳ ಬುಕಿಂಗ್ ದೃಢೀಕರಣ
- ✅ **Smart Logo Detection**: Automatically uses image if available
- ✅ **Fallback Design**: Professional text logo if GD not available

## Testing:
After enabling GD extension, generate a new PDF to see:
- Actual VTU logo image instead of text
- All Kannada text properly rendered
- Professional university branding

## Troubleshooting:
If logo still doesn't appear after enabling GD:
1. Check if `public/images/vtu_logo.png` exists
2. Verify Apache was restarted
3. Clear browser cache and try again

## File Locations:
- **VTU Logo**: `public/images/vtu_logo.png` ✅ (exists, 161KB)
- **PHP Config**: `C:\xampp\php\php.ini`
- **PDF Generator**: `public/generate_booking_pdf.php`