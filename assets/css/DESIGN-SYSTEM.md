# BuildWatch Design System Documentation

## Overview

This design system provides a unified foundation for the BuildWatch construction management platform, ensuring consistency, accessibility, and maintainability across all pages.

## Table of Contents

1. [Design Tokens](#design-tokens)
2. [Components](#components)
3. [Usage Examples](#usage-examples)
4. [Migration Guide](#migration-guide)
5. [Accessibility Guidelines](#accessibility-guidelines)

---

## Design Tokens

### Colors

#### Primary Colors
```css
--color-primary: #0B5394
--color-primary-dark: #073763
--color-primary-light: #4A90E2
```

#### Secondary Colors
```css
--color-secondary: #0D9488
--color-secondary-dark: #0F766E
--color-secondary-light: #14B8A6
```

#### Semantic Colors
```css
--color-success: #10B981
--color-warning: #F59E0B
--color-danger: #EF4444
--color-info: #3B82F6
```

### Typography

**Font Family:**
```
'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif
```

**Scale:**
- Display: 3rem (48px)
- H1: 2.25rem (36px)
- H2: 1.875rem (30px)
- H3: 1.5rem (24px)
- H4: 1.25rem (20px)
- H5: 1.125rem (18px)
- Body: 1rem (16px)
- Caption: 0.75rem (12px)

### Spacing

8px grid system:
- xs: 0.25rem (4px)
- sm: 0.5rem (8px)
- md: 1rem (16px)
- lg: 1.5rem (24px)
- xl: 2rem (32px)
- 2xl: 3rem (48px)

### Shadows

Elevation system:
- sm: `0 1px 3px 0 rgba(0, 0, 0, 0.1)`
- md: `0 4px 6px -1px rgba(0, 0, 0, 0.1)`
- lg: `0 10px 15px -3px rgba(0, 0, 0, 0.1)`
- xl: `0 20px 25px -5px rgba(0, 0, 0, 0.1)`

---

## Components

### Buttons

#### Basic Usage
```html
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-secondary">Secondary Button</button>
<button class="btn btn-success">Success Button</button>
<button class="btn btn-danger">Danger Button</button>
```

#### Sizes
```html
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary">Medium</button>
<button class="btn btn-primary btn-lg">Large</button>
```

#### Variants
```html
<!-- Solid -->
<button class="btn btn-primary">Solid</button>

<!-- Outline -->
<button class="btn btn-outline-primary">Outline</button>

<!-- Ghost -->
<button class="btn btn-ghost">Ghost</button>

<!-- Icon Button -->
<button class="btn btn-icon" aria-label="Close">
  <i class="fas fa-times"></i>
</button>
```

#### States
```html
<!-- Disabled -->
<button class="btn btn-primary" disabled>Disabled</button>

<!-- Loading -->
<button class="btn btn-primary btn-loading">Loading...</button>
```

### Forms

#### Basic Input
```html
<div class="form-group">
  <label for="email" class="form-label required">Email Address</label>
  <input type="email" id="email" name="email" class="form-input" required>
  <div class="invalid-feedback" id="email-error">Please enter a valid email.</div>
</div>
```

#### Input Sizes
```html
<input type="text" class="form-input form-input-sm">
<input type="text" class="form-input">
<input type="text" class="form-input form-input-lg">
```

#### Validation States
```html
<!-- Error -->
<input type="email" class="form-input is-invalid">
<div class="invalid-feedback">Error message here</div>

<!-- Success -->
<input type="email" class="form-input is-valid">
<div class="valid-feedback">Looks good!</div>
```

#### Checkbox & Radio
```html
<div class="checkbox">
  <input type="checkbox" id="remember">
  <label for="remember" class="checkbox-label">Remember me</label>
</div>
```

### Cards

#### Basic Card
```html
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Card Title</h3>
  </div>
  <div class="card-body">
    <p class="card-text">Card content goes here.</p>
  </div>
  <div class="card-footer">
    <button class="btn btn-primary">Action</button>
  </div>
</div>
```

#### Card Variants
```html
<!-- Colored Border -->
<div class="card card-primary">...</div>
<div class="card card-secondary">...</div>

<!-- Interactive -->
<div class="card card-interactive">...</div>

<!-- Stat Card -->
<div class="card stat-card">
  <div class="stat-icon stat-icon-primary">📊</div>
  <div class="stat-value">42</div>
  <div class="stat-label">Total Projects</div>
</div>
```

### Modals

```html
<div class="modal">
  <div class="modal-backdrop"></div>
  <div class="modal-content modal-lg">
    <div class="modal-header">
      <h2 class="modal-title">Modal Title</h2>
      <button class="modal-close" aria-label="Close modal">×</button>
    </div>
    <div class="modal-body">
      <p>Modal content here</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary">Cancel</button>
      <button class="btn btn-primary">Save</button>
    </div>
  </div>
</div>
```

### Navigation

#### Sidebar
```html
<aside class="sidebar">
  <div class="sidebar-header">
    <h1 class="logo">BuildWatch</h1>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li>
        <a href="#" class="nav-item active">
          <i class="fas fa-home"></i> Dashboard
        </a>
      </li>
      <li>
        <a href="#" class="nav-item">
          <i class="fas fa-project-diagram"></i> Projects
        </a>
      </li>
    </ul>
  </nav>
</aside>
```

#### Breadcrumbs
```html
<nav class="breadcrumb" aria-label="Breadcrumb">
  <div class="breadcrumb-item">
    <a href="/">Home</a>
  </div>
  <div class="breadcrumb-item">
    <a href="/projects">Projects</a>
  </div>
  <div class="breadcrumb-item">Current Page</div>
</nav>
```

### Notifications

#### Alert
```html
<div class="alert alert-success" role="alert">
  <i class="fas fa-check-circle"></i>
  Operation completed successfully!
</div>
```

#### Toast Notification
```javascript
// JavaScript to show toast
showToast('Success!', 'Operation completed successfully', 'success');
```

---

## Usage Examples

### Complete Login Page

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - BuildWatch</title>
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>
  
  <div class="auth-container">
    <main id="main-content" class="auth-form-wrapper">
      <a href="/" class="back-to-home">
        <i class="fas fa-arrow-left"></i> Back to Home
      </a>
      
      <div class="auth-form-header">
        <h1 class="auth-form-title">Client Portal</h1>
        <p class="auth-form-subtitle">Sign in to track your projects</p>
      </div>
      
      <form class="auth-form">
        <div class="form-group">
          <label for="email" class="form-label required">Email</label>
          <input type="email" id="email" class="form-input" required>
          <div class="invalid-feedback" id="email-error"></div>
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label required">Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" class="form-input" required>
            <button type="button" class="password-toggle" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="password-error"></div>
        </div>
        
        <button type="submit" class="btn btn-primary auth-submit-btn">
          Sign In
        </button>
      </form>
    </main>
  </div>
</body>
</html>
```

---

## Migration Guide

### Replacing Old Styles

#### Buttons
```html
<!-- Old -->
<button class="btn-primary">Click me</button>

<!-- New -->
<button class="btn btn-primary">Click me</button>
```

#### Forms
```html
<!-- Old -->
<input type="email" style="padding: 10px; border: 1px solid #ddd;">

<!-- New -->
<input type="email" class="form-input">
```

#### Cards
```html
<!-- Old -->
<div class="dashboard-card">...</div>

<!-- New -->
<div class="card">...</div>
```

### Removing Inline Styles

Replace all inline styles with classes:

```html
<!-- ❌ Old -->
<div style="padding: 20px; margin: 10px;">

<!-- ✅ New -->
<div class="p-6 mb-4">
```

---

## Accessibility Guidelines

### ARIA Labels

Always provide labels for icon-only buttons:
```html
<button class="btn btn-icon" aria-label="Close modal">
  <i class="fas fa-times"></i>
</button>
```

### Form Labels

Always associate labels with inputs:
```html
<label for="email" class="form-label">Email</label>
<input type="email" id="email" class="form-input" aria-describedby="email-error">
<div class="invalid-feedback" id="email-error" role="alert"></div>
```

### Keyboard Navigation

Ensure all interactive elements are keyboard accessible:
- Use native HTML elements when possible
- Ensure custom components have tabindex="0"
- Test with Tab key navigation

### Focus Indicators

All interactive elements have visible focus states:
```css
/* Automatically applied via design system */
:focus-visible {
  outline: 3px solid var(--focus-ring-color);
  outline-offset: 2px;
}
```

---

## File Structure

```
assets/css/
├── design-tokens.css      # Design variables
├── base.css               # Resets & base styles
├── buttons.css            # Button components
├── forms.css              # Form components
├── cards.css              # Card components
├── modals.css             # Modal system
├── navigation.css         # Navigation
├── notifications.css      # Notifications
├── grid.css               # Grid utilities
├── utilities.css          # Helper classes
├── main.css              # Master import
├── authentication.css     # Auth pages
└── accessibility.css      # Accessibility

```

---

## Support

For questions or issues with the design system, please refer to this documentation or contact the development team.

---

**Last Updated:** 2024
**Version:** 1.0.0

