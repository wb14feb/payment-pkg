# Jinah Package Assets

This directory contains the static assets (CSS and JavaScript) for the Jinah payment package.

## Directory Structure

```
assets/
├── css/
│   └── jinah.css          # Main stylesheet for payment UI
├── js/
│   └── jinah.js           # JavaScript for payment interactions
└── README.md              # This file
```

## Publishing Assets

To publish the assets to your Laravel application's public directory, run:

```bash
php artisan vendor:publish --tag=jinah-assets
```

This will copy the assets to `public/vendor/jinah/`.

## Using the Assets

The assets are automatically included in the package's Blade views. However, if you customize the views, you can reference them using:

### CSS

```html
<link href="{{ asset('vendor/jinah/css/jinah.css') }}" rel="stylesheet">
```

### JavaScript

```html
<script src="{{ asset('vendor/jinah/js/jinah.js') }}"></script>
```

## CSS Features

The `jinah.css` file provides:

- Modern payment interface styling
- Responsive design for mobile and desktop
- Payment method card styles with hover effects
- Form validation styles
- Loading states and animations
- Order summary styling
- Custom CSS variables for easy theming

### CSS Variables

You can customize the colors by overriding these CSS variables:

```css
:root {
    --jinah-primary: #007bff;
    --jinah-success: #28a745;
    --jinah-danger: #dc3545;
    --jinah-warning: #ffc107;
    --jinah-info: #17a2b8;
    --jinah-light: #f8f9fa;
    --jinah-dark: #343a40;
    --jinah-border: #dee2e6;
}
```

## JavaScript Features

The `jinah.js` file provides:

- Payment method selection handling
- Form validation
- Dynamic quantity controls
- Total calculation
- Loading states
- Custom events for extensibility

### JavaScript API

The JavaScript file exposes a `JinahPayment` class that can be initialized:

```javascript
// Auto-initialized on DOM ready if #paymentForm exists
// Or manually initialize:
const payment = new JinahPayment({
    formSelector: '#paymentForm',
    submitButtonSelector: '#submitBtn',
    // ... other options
});
```

### Custom Events

Listen to custom events for extended functionality:

```javascript
document.querySelector('#paymentForm').addEventListener('jinah:paymentMethodSelected', (e) => {
    console.log('Selected method:', e.detail.method);
});

document.querySelector('#paymentForm').addEventListener('jinah:beforeSubmit', (e) => {
    console.log('Form submitting with:', e.detail);
    // Return false to prevent submission
});

document.querySelector('#paymentForm').addEventListener('jinah:totalCalculated', (e) => {
    console.log('Total:', e.detail.total);
});
```

## Customization

### Overriding Styles

You can override the default styles by:

1. Publishing the assets: `php artisan vendor:publish --tag=jinah-assets`
2. Editing the published CSS file at `public/vendor/jinah/css/jinah.css`

### Extending JavaScript

To extend the JavaScript functionality:

1. Load the base `jinah.js` file
2. Add your custom scripts after it
3. Use the custom events to hook into the payment flow

Example:

```html
<script src="{{ asset('vendor/jinah/js/jinah.js') }}"></script>
<script>
    // Your custom code
    document.querySelector('#paymentForm').addEventListener('jinah:beforeSubmit', (e) => {
        // Add custom validation or analytics
        console.log('Processing payment...', e.detail);
    });
</script>
```

## Dependencies

The package assets work with:

- Bootstrap 5.3.0+ (for UI components)
- Font Awesome 6.0.0+ (for icons)

These dependencies are loaded via CDN in the default views, but you can replace them with local copies if preferred.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

The assets are included as part of the Jinah package and follow the same license.
