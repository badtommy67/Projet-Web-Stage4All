# Projet_WEB_Groupe_2 - AI Coding Agent Instructions

## Project Overview
A PHP-based internship/job listing website ("Projet des stages"). Team project with 4 contributors using Scrum methodology.

**Key Tech Stack:**
- PHP backend (server-side routing & logic)
- HTML/CSS frontend
- Simple MVC-like structure (views/ contains layout templates)

## Architecture & Directory Structure

### Core Layout Pattern
- **`public/index.php`** - Entry point; includes header/footer templates
- **`views/layout/header.php`** - Shared header with dynamic navigation (uses REQUEST_URI for active links)
- **`views/layout/footer.php`** - Shared footer (currently empty)
- **`public/css/style.css`** - Global styles
- **`public/js/script.js`** - Client-side scripts

### Routing Pattern
Navigation is URI-based using `$_SERVER['REQUEST_URI']`:
```php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
// Active links: /, /offres, /entreprises, /contact
```
When adding new pages, update header.php navigation links and ensure corresponding view logic in index.php or route handler.

### Include Pattern
Use relative paths from public/ to include views:
```php
include '../views/layout/header.php';  // Two levels up from public/
```

## Key Conventions & Patterns

1. **URL Structure**: Flat routing (/offres, /entreprises, /contact) - no subdirectories needed
2. **Template Inclusion**: All pages include header/footer via PHP includes for consistent layout
3. **Active Link Detection**: Header uses `strpos($uri, '/offres')` pattern to highlight current page
4. **Server Diagnostics**: test.php contains diagnostic code for $_SERVER inspection (useful for debugging routing issues)

## Critical Files to Know
- [views/layout/header.php](views/layout/header.php) - Navigation center; update when adding new pages
- [public/index.php](public/index.php) - Main entry point and page logic hub
- [test.php](test.php) - Server diagnostics (keep for troubleshooting REQUEST_URI issues)

## Developer Workflows

### Adding a New Page
1. Add navigation link to [views/layout/header.php](views/layout/header.php) with URI pattern matching
2. Add route logic in [public/index.php](public/index.php) to handle the new URI
3. Include header/footer in the new view for consistent branding

### Testing Locally
- Site uses relative paths (/public/css/style.css, /public/images/logo.png)
- Ensure requests go through web server, not direct file access
- Use test.php if routing issues occur - it logs REQUEST_URI and $_SERVER details

### CSS & JS Location
- Global styles: [public/css/style.css](public/css/style.css)
- Client scripts: [public/js/script.js](public/js/script.js)
- Both referenced in header; styles loaded via external link tag

## Integration Points
- **Static Assets**: /public/ folder served as web root (CSS, images, JS)
- **Logo Expected**: /public/images/logo.png referenced in header
- **Font Loading**: Google Fonts API (Poppins family) - external dependency in header.php
