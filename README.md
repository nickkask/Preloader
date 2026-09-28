# Kask Preloader

A lightweight WordPress preloader plugin. Upload an icon, pick a background color, and a full-screen overlay covers the page until it finishes loading.

No dependencies, no external requests, and a single PHP file.

## Features

- Icon upload from the Media Library, with adjustable width
- Background color picker
- Inline critical CSS, so the overlay paints before anything else on the page
- CSS-only failsafe that clears the overlay after 5 seconds, even if JavaScript is delayed or blocked
- Respects `prefers-reduced-motion`
- Hidden from screen readers (`aria-hidden`)
- Skips page builder editors (Etch, Bricks, Elementor)
- Steps aside automatically when Elementor Pro Page Transitions is active
- Script is marked to be skipped by Cloudflare Rocket Loader, WP Rocket, and LiteSpeed Cache

## Installation

1. Download the latest release zip, or zip the `kask-preloader` folder yourself.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and activate.

Or copy the `kask-preloader` folder into `/wp-content/plugins/` and activate it from the Plugins screen.

## Setup

Go to **Settings → Preloader**:

| Setting | Description |
|---|---|
| Status | Turns the preloader on or off on the front end |
| Icon | Choose an image from the Media Library |
| Icon width | Display width in pixels (16–400). Scales down on small screens. |
| Background color | Color of the full-screen overlay |

Click **Save changes**.

### SVG icons

WordPress blocks SVG uploads by default. To use an SVG icon, enable SVG uploads with a plugin or snippet first. PNG and WebP work without any extra setup.

## Disabling on specific pages

Use the `kask_preloader_active` filter:

```php
add_filter( 'kask_preloader_active', function ( $active ) {
    return is_front_page() ? $active : false; // Only show on the homepage
} );
```

## Compatibility

Tested with Etch, Bricks, and Elementor. It works with any theme that calls `wp_body_open`, and falls back to `wp_footer` for themes that don't.

### Caching and optimization plugins

If the overlay hangs until the 5-second failsafe, a "Delay JavaScript" feature is likely holding back the inline script. Add `kask-preloader` to that plugin's exclusion list.

### Elementor Pro

If Page Transitions is enabled, the Elementor preloader takes over and this plugin hides itself. Use one or the other.

## Changelog

### 1.1.0
- Official builder detection for Bricks and Elementor
- CSS-only failsafe replaces the JavaScript timeout
- Script exempted from common delay and defer tools
- Auto-hides when Elementor Pro Page Transitions is active

### 1.0.0
- Initial release

## License

GPL-2.0-or-later
