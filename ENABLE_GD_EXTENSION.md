# How to Enable PHP GD Extension for Image Support in PDFs

## Issue
The PDF generation fails when trying to use images because the PHP GD extension is not enabled.

**Error:** "The PHP GD extension is required, but is not installed."

## Solution Options

### Option 1: Enable GD Extension in XAMPP (Recommended)

1. **Open PHP Configuration:**
   - Navigate to your XAMPP installation folder (usually `C:\xampp\`)
   - Open `php\php.ini` file in a text editor

2. **Find and Uncomment GD Extension:**
   - Search for: `;extension=gd`
   - Remove the semicolon to make it: `extension=gd`

3. **Restart Apache:**
   - Stop and start Apache in XAMPP Control Panel
   - Or restart XAMPP completely

4. **Verify Installation:**
   - Create a test file with: `<?php phpinfo(); ?>`
   - Look for "GD" section in the output

### Option 2: Alternative Image Approach (Current Implementation)

We've implemented a text-based logo that doesn't require GD extension:
- Professional circular design with VTU branding
- No external dependencies
- Works on all PHP installations

## Current PDF Features (Without GD)

✅ **Professional VTU Logo:** Text-based circular logo with university colors
✅ **Complete Branding:** University name in English and Kannada
✅ **All Booking Information:** Complete details without image dependencies
✅ **Professional Layout:** Clean, official document design

## Future Image Support

Once GD extension is enabled, you can:
- Use actual VTU logo images
- Add QR codes for verification
- Include venue photos
- Enhanced visual elements

## Testing GD Extension

```php
<?php
if (extension_loaded('gd')) {
    echo "GD Extension is loaded!";
    print_r(gd_info());
} else {
    echo "GD Extension is NOT loaded.";
}
?>
```

## Current Workaround Benefits

- **No Dependencies:** Works on any PHP installation
- **Fast Generation:** No image processing overhead
- **Professional Look:** Clean, branded appearance
- **Reliable:** No external file dependencies