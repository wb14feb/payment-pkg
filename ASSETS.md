# Jinah Package - Asset Installation Guide

## Quick Start

After installing the Jinah package, follow these steps to use the static assets:

### 1. Publish the Assets

Run the following command to publish the assets to your Laravel application:

```bash
php artisan vendor:publish --tag=jinah-assets
```

This will copy the assets to `public/vendor/jinah/`:
- `css/jinah.css` - Full CSS with comments
- `css/jinah.min.css` - Minified CSS for production
- `js/jinah.js` - Full JavaScript with comments
- `js/jinah.min.js` - Minified JavaScript for production

### 2. Use in Your Application

The package views automatically include the assets. However, if you customize the views or want to use the assets elsewhere:

#### In Blade Templates

```blade
<!-- Include CSS in your <head> -->
<link href="{{ asset('vendor/jinah/css/jinah.css') }}" rel="stylesheet">

<!-- Include JS before closing </body> -->
<script src="{{ asset('vendor/jinah/js/jinah.js') }}"></script>
```

#### For Production (Minified)

```blade
<link href="{{ asset('vendor/jinah/css/jinah.min.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/jinah/js/jinah.min.js') }}"></script>
```

### 3. Dependencies

Make sure to include these dependencies before the Jinah assets:

```blade
<!-- Bootstrap 5.3.0+ -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome 6.0.0+ (optional, for icons) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
```

## Publishing All Resources

To publish all package resources at once:

```bash
php artisan vendor:publish --provider="AnyTech\Jinah\JinahServiceProvider"
```

This will publish:
- Configuration files
- View templates
- Database migrations
- Static assets (CSS & JS)

## Customization

### Customizing Styles

1. Publish the assets
2. Edit `public/vendor/jinah/css/jinah.css`
3. Modify the CSS variables in `:root` for easy theming:

```css
:root {
    --jinah-primary: #007bff;      /* Your primary color */
    --jinah-success: #28a745;      /* Your success color */
    --jinah-danger: #dc3545;       /* Your danger color */
    /* ... etc */
}
```

### Extending JavaScript

The package JavaScript is modular and extensible. Listen to custom events:

```javascript
document.querySelector('#paymentForm').addEventListener('jinah:paymentMethodSelected', (e) => {
    console.log('Selected:', e.detail.method);
    // Your custom logic
});

document.querySelector('#paymentForm').addEventListener('jinah:beforeSubmit', (e) => {
    // Add custom validation
    if (!myCustomValidation()) {
        e.preventDefault();
        return false;
    }
});
```

## Available Events

The JavaScript emits these custom events:

| Event | Description | Detail |
|-------|-------------|--------|
| `jinah:paymentMethodSelected` | Fired when payment method is selected | `{ method, element }` |
| `jinah:beforeSubmit` | Fired before form submission | `{ items, paymentMethod }` |
| `jinah:totalCalculated` | Fired when total is recalculated | `{ total, discount }` |

## Testing the Assets

An example HTML file is included at `resources/assets/example.html` that demonstrates all features. To test it locally:

1. Copy the example file to your public directory
2. Open it in a browser
3. Interact with the payment form to see events

## File Structure

```
public/vendor/jinah/
├── css/
│   ├── jinah.css         # Development version with comments
│   └── jinah.min.css     # Production version (minified)
├── js/
│   ├── jinah.js          # Development version with comments
│   └── jinah.min.js      # Production version (minified)
└── README.md             # Asset documentation
```

## Troubleshooting

### Assets Not Loading

1. Ensure assets are published: `php artisan vendor:publish --tag=jinah-assets`
2. Clear cache: `php artisan cache:clear`
3. Check that `public/vendor/jinah` directory exists
4. Verify file permissions

### Styling Issues

1. Ensure Bootstrap is loaded before Jinah CSS
2. Check browser console for CSS errors
3. Verify CSS variables are supported (modern browsers only)

### JavaScript Not Working

1. Ensure Bootstrap JS is loaded before Jinah JS
2. Check browser console for errors
3. Verify `#paymentForm` element exists on the page
4. Check that form structure matches expected HTML

## Support

For issues, questions, or contributions:
- GitHub Issues: [Report an issue]
- Documentation: See package README.md
- Examples: Check `resources/assets/example.html`
