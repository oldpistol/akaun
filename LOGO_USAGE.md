# IA Logo Component Usage

This logo component provides multiple variants of the 'IA' logo for different use cases in your application.

## Available Variants

### 1. Icon Variant (Default in Admin Panel)
Compact square logo with gradient background - perfect for favicons and admin panel.

```blade
<x-logo variant="icon" size="md" />
```

### 2. Default Professional Variant
Full logo with 'IA' badge and company name - ideal for headers and main branding.

```blade
<x-logo variant="default" size="lg" />
```

### 3. Minimal Variant
Clean outline design - great for light backgrounds and minimal designs.

```blade
<x-logo variant="minimal" size="md" />
```

### 4. Gradient Variant
Vibrant gradient background with styled text - perfect for landing pages.

```blade
<x-logo variant="gradient" size="xl" />
```

## Available Sizes

- `sm` - Small (32px / w-8 h-8)
- `md` - Medium (48px / w-12 h-12) - Default
- `lg` - Large (64px / w-16 h-16)
- `xl` - Extra Large (96px / w-24 h-24)

## Usage Examples

### In Blade Templates

```blade
{{-- Admin panel sidebar --}}
<x-logo variant="icon" size="md" />

{{-- Page header --}}
<x-logo variant="default" size="lg" />

{{-- Email templates --}}
<x-logo variant="gradient" size="xl" />

{{-- Footer --}}
<x-logo variant="minimal" size="sm" />
```

### In PDF Templates

```blade
<div style="text-align: center; margin-bottom: 20px;">
    <x-logo variant="icon" size="lg" />
</div>
```

### With Custom Classes

```blade
<x-logo variant="icon" size="md" class="mx-auto mb-4" />
```

## Color Customization

The logo uses:
- **Blue gradient** for the icon variant (#3b82f6 to #1d4ed8)
- **Purple gradient** for the gradient variant (#6366f1 to #d946ef)
- **currentColor** for the minimal variant (adapts to text color)

## Where It's Used

- ✅ Filament Admin Panel (sidebar)
- 📄 PDF invoices/quotations (add manually)
- 📧 Email templates (add manually)
- 🌐 Public pages (add manually)

## Customizing the Logo

Edit `/resources/views/components/logo.blade.php` to:
- Change colors in the gradient definitions
- Adjust font sizes
- Modify border radius
- Add new variants

## Creating a Favicon

To create a favicon from the icon variant:
1. Visit https://tutor.test in browser
2. Right-click the logo and "Save as SVG"
3. Use an online converter to create .ico file
4. Place in `/public/favicon.ico`
