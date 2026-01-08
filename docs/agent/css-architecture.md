# CSS Architecture Standards

## CSS Framework Preference

### EXOS CSS Framework

**For dashboard and admin UI components, prefer using EXOS CSS framework classes over writing custom CSS rules.**

EXOS provides a comprehensive set of utility classes and components specifically designed for admin interfaces. Using framework classes ensures:
- Consistent styling across admin features
- Reduced CSS bundle size
- Faster development
- Better maintainability
- Automatic theme integration

**EXOS Framework URL**: `https://ce1.uicdn.net/exos/framework/3.0/exos.min.css`

**✅ Good - Use EXOS classes**:
```html
<!-- Use EXOS utility classes -->
<div class="exos-card exos-padding-md exos-margin-bottom-lg">
  <h2 class="exos-heading-lg exos-text-primary">Dashboard Title</h2>
  <button class="exos-button exos-button--primary">Action</button>
</div>

<!-- Use EXOS layout classes -->
<div class="exos-grid exos-grid--2-cols exos-gap-md">
  <div class="exos-card">Card 1</div>
  <div class="exos-card">Card 2</div>
</div>
```

**❌ Avoid - Writing custom CSS when EXOS provides it**:
```css
/* Don't write custom styles for common patterns */
.custom-button {
  padding: 12px 24px;
  background: #0073aa;
  border-radius: 4px;
  /* ... when .exos-button exists */
}

.custom-grid {
  display: grid;
  gap: 1rem;
  /* ... when .exos-grid exists */
}
```

**When to Write Custom CSS**:
- EXOS doesn't provide the required component
- Unique plugin-specific styling beyond EXOS capabilities
- Custom animations or advanced interactions
- Component-specific modifications using CSS variables

**Combining EXOS with Custom CSS**:
```html
<!-- Use EXOS base + custom modifier -->
<div class="exos-card custom-dashboard-widget">
  <!-- EXOS provides structure, custom CSS adds specifics -->
</div>
```

```css
/* Extend EXOS with custom styling */
.custom-dashboard-widget {
  /* Only add what EXOS doesn't provide */
  background-image: linear-gradient(to bottom, var(--color-bg-light), var(--color-bg));
}
```

## CSS Methodology

### Prefer Native CSS Over SCSS

Use native CSS features when possible rather than SCSS preprocessing:

**✅ Use Native CSS**:
- CSS Variables (Custom Properties)
- **CSS Nesting** (native nesting, not SCSS) - Use where possible for cleaner, more maintainable code
- CSS `calc()`, `clamp()`, `min()`, `max()`
- CSS Grid and Flexbox
- CSS `@container` queries
- CSS `@layer` for cascade control

**Use SCSS Only When**:
- Complex mixins are genuinely needed
- Significant code reuse requires `@include`

### CSS Variables (Custom Properties)

Prefer CSS variables over SCSS variables:

```css
/* ✅ Good - Native CSS variables */
:root {
  --color-primary: #0073aa;
  --color-secondary: #006799;
  --spacing-unit: 8px;
  --font-size-base: 16px;
  --border-radius: 4px;
}

.button {
  background-color: var(--color-primary);
  padding: calc(var(--spacing-unit) * 2);
  border-radius: var(--border-radius);
}

/* ❌ Avoid - SCSS variables when CSS variables work */
$color-primary: #0073aa;
$spacing-unit: 8px;

.button {
  background-color: $color-primary;
  padding: $spacing-unit * 2;
}
```

**Benefits of CSS Variables**:
- Runtime modification via JavaScript
- Inheritance and cascade behavior
- Browser DevTools support
- No build step required
- Scoping with CSS selectors

### Native CSS Nesting

**Use native CSS nesting where possible** instead of SCSS nesting for cleaner, more maintainable code without build dependencies.

**✅ Good - Native CSS Nesting**:
```css
.card {
  padding: 1rem;
  border: 1px solid var(--color-border);

  /* Nesting with & */
  &__header {
    font-weight: bold;
    margin-bottom: 0.5rem;
  }

  &__content {
    line-height: 1.6;
  }

  /* Nested modifiers */
  &--featured {
    border-color: var(--color-primary);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  /* Nested pseudo-classes */
  &:hover {
    transform: translateY(-2px);
  }

  /* Nested media queries */
  @media (min-width: 768px) {
    padding: 2rem;
  }
}
```

**✅ Good - Nesting descendant selectors**:
```css
.navigation {
  display: flex;
  gap: 1rem;

  /* Nested descendants (no & needed for descendants) */
  ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  li {
    display: inline-block;
  }

  a {
    text-decoration: none;
    color: var(--color-text);

    &:hover {
      color: var(--color-primary);
    }
  }
}
```

**❌ Avoid - SCSS nesting when native CSS works**:
```scss
// SCSS syntax - unnecessary when native CSS nesting is available
.card {
  padding: 1rem;

  .card__header {  // Don't use SCSS for this
    font-weight: bold;
  }
}
```

**Browser Support Note**: Native CSS nesting is supported in all modern browsers (Chrome 112+, Firefox 117+, Safari 16.5+). For older browsers, consider using a PostCSS plugin to transform nested CSS during build.

**Benefits of Native CSS Nesting**:
- No build step required for modern browsers
- Keeps styles co-located with parent
- Easier to maintain and refactor
- Reduces selector specificity issues
- Works with CSS Variables and other native features

## Naming Conventions

### BEM Methodology

Follow BEM (Block Element Modifier) naming:

```css
/* Block */
.card { }

/* Element */
.card__content { }
.card__header { }
.card__footer { }

/* Modifier */
.card--featured { }
.card--large { }

/* Element with Modifier */
.card__button--primary { }
.card__button--secondary { }
```

**Examples from Codebase**:

```css
.page-tabbar__items { }
.page-tabbar__link { }
.page-tabbar__link--active { }
.page-tabbar__label { }

.button { }
.button--primary { }
.button--secondary { }
.button--promoting { }

.panel__item { }
.panel__item-header { }
.panel__item-content { }
.panel__item--expanded { }
.panel__item--closed { }
```

### Class Naming Rules

- Use **lowercase** with **hyphens**
- Be **descriptive** and **semantic**
- Avoid abbreviations unless widely understood
- Use BEM syntax consistently

```css
/* ✅ Good */
.site-header { }
.user-profile__avatar { }
.notification--error { }

/* ❌ Avoid */
.SiteHeader { }
.usrProf { }
.notif { }
```

## CSS Structure

### Property Organization

Group related properties together:

```css
.element {
  /* Positioning */
  position: relative;
  top: 0;
  left: 0;
  z-index: 10;

  /* Display & Box Model */
  display: flex;
  width: 100%;
  padding: 1rem;
  margin: 0 auto;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);

  /* Typography */
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  text-align: left;

  /* Visual */
  background-color: var(--color-background);
  color: var(--color-text);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

  /* Animation */
  transition: all 0.3s ease;
}
```

### Selector Specificity

Keep specificity low for maintainability:

```css
/* ✅ Good - Low specificity */
.button { }
.button--primary { }

/* ⚠️ Acceptable - Moderate specificity */
.card .button { }

/* ❌ Avoid - High specificity */
div.card div.content button.button-primary { }
#container .wrapper .card .button { }
```

### Avoid Deep Nesting

```css
/* ✅ Good - Flat structure */
.navigation { }
.navigation__list { }
.navigation__item { }
.navigation__link { }

/* ❌ Avoid - Deep nesting */
.navigation ul li a { }
```

## Modern CSS Features

### CSS Grid

Use Grid for two-dimensional layouts:

```css
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-unit);
}

.layout {
  display: grid;
  grid-template-areas:
    "header header"
    "sidebar content"
    "footer footer";
  grid-template-columns: 250px 1fr;
  gap: 1rem;
}

.header { grid-area: header; }
.sidebar { grid-area: sidebar; }
.content { grid-area: content; }
.footer { grid-area: footer; }
```

### Flexbox

Use Flexbox for one-dimensional layouts:

```css
.button-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: space-between;
}

.card__header {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
```

### Modern Units

Use modern CSS units:

```css
/* ✅ Good - Modern units */
.container {
  width: clamp(320px, 90vw, 1200px);
  padding: clamp(1rem, 5vw, 3rem);
  font-size: clamp(1rem, 2.5vw, 1.5rem);
}

.responsive-spacing {
  margin-block: min(2rem, 5vh);
  padding-inline: max(1rem, 3vw);
}

/* Gap instead of margin hacks */
.flex-container {
  display: flex;
  gap: 1rem; /* Modern, clean spacing */
}
```

### Logical Properties

Use logical properties for internationalization:

```css
/* ✅ Good - Logical properties */
.card {
  padding-block: 1rem;        /* top and bottom */
  padding-inline: 2rem;       /* left and right */
  margin-block-start: 1rem;   /* margin-top */
  border-inline-end: 1px solid; /* border-right */
}

/* ❌ Avoid - Physical properties */
.card {
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 2rem;
  padding-right: 2rem;
}
```

## Responsive Design

### Mobile-First Approach

Write mobile styles first, then enhance:

```css
/* Mobile first (default) */
.card {
  padding: 1rem;
  font-size: 1rem;
}

/* Tablet */
@media (min-width: 768px) {
  .card {
    padding: 1.5rem;
    font-size: 1.125rem;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .card {
    padding: 2rem;
    font-size: 1.25rem;
  }
}
```

### Container Queries

Use container queries for component-based responsive design:

```css
.card-container {
  container-type: inline-size;
  container-name: card;
}

.card {
  padding: 1rem;
}

@container card (min-width: 400px) {
  .card {
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 2fr;
  }
}
```

## Color and Theming

### CSS Custom Properties for Themes

```css
:root {
  --color-primary: #0073aa;
  --color-primary-hover: #006799;
  --color-text: #23282d;
  --color-text-light: #6c757d;
  --color-background: #ffffff;
  --color-background-alt: #f7f7f7;
  --color-border: #e2e4e7;
  --color-success: #46b450;
  --color-warning: #ffb900;
  --color-error: #dc3232;
}

/* Dark mode override */
@media (prefers-color-scheme: dark) {
  :root {
    --color-text: #ffffff;
    --color-background: #1e1e1e;
    --color-background-alt: #2d2d2d;
    --color-border: #3a3a3a;
  }
}

/* Usage */
.button {
  background-color: var(--color-primary);
  color: var(--color-background);
}

.button:hover {
  background-color: var(--color-primary-hover);
}
```

## Animations and Transitions

### Performant Animations

Animate only compositor-safe properties:

```css
/* ✅ Good - GPU-accelerated */
.element {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.element:hover {
  transform: translateY(-2px);
  opacity: 0.9;
}

/* ❌ Avoid - Causes reflow */
.element {
  transition: top 0.3s ease, height 0.3s ease;
}
```

### Reduced Motion

Respect user preferences:

```css
.element {
  transition: transform 0.3s ease;
}

@media (prefers-reduced-motion: reduce) {
  .element {
    transition: none;
  }
}
```

## Typography

### Fluid Typography

Use `clamp()` for responsive text:

```css
:root {
  --font-size-small: clamp(0.875rem, 0.5vw + 0.75rem, 1rem);
  --font-size-base: clamp(1rem, 1vw + 0.75rem, 1.125rem);
  --font-size-large: clamp(1.25rem, 2vw + 1rem, 1.5rem);
  --font-size-heading: clamp(1.5rem, 3vw + 1rem, 2.5rem);
}

body {
  font-size: var(--font-size-base);
  line-height: 1.6;
}

h1 {
  font-size: var(--font-size-heading);
  line-height: 1.2;
}
```

### Font Stack

```css
:root {
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
    Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
  --font-family-mono: 'Monaco', 'Consolas', 'Courier New', monospace;
}

body {
  font-family: var(--font-family-base);
}

code, pre {
  font-family: var(--font-family-mono);
}
```

## Accessibility

### Focus States

Always provide visible focus indicators:

```css
.button {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

.button:focus-visible {
  outline-color: var(--color-primary);
}

/* Remove outline for mouse users, keep for keyboard */
.button:focus:not(:focus-visible) {
  outline: none;
}
```

### Color Contrast

Ensure sufficient contrast:

```css
/* Maintain WCAG AA minimum 4.5:1 for normal text */
.text {
  color: #23282d;
  background-color: #ffffff;
}

/* 3:1 minimum for large text (18pt+) */
.heading {
  color: #464646;
  background-color: #ffffff;
  font-size: 1.5rem;
}
```

### Screen Reader Only

```css
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
```

## Code Formatting

### General Rules

- **Indentation**: 2 spaces
- **Line Length**: Maximum 120 characters
- **Semicolons**: Always include
- **Quotes**: Single quotes for strings
- **Property Order**: Logical grouping

### Example

```css
.card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background-color: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
```

## Performance Best Practices

1. **Minimize reflows** - Avoid animating layout properties
2. **Use `transform` and `opacity`** for animations
3. **Avoid universal selectors** (`*`)
4. **Limit selector complexity** - Keep under 3 levels
5. **Use `will-change` sparingly** - Only for known animations
6. **Leverage CSS containment** - `contain: layout paint`

```css
.animated-card {
  /* GPU acceleration hint */
  will-change: transform;
}

.animated-card.animating {
  transform: translateY(-10px);
}

.animated-card:not(.animating) {
  /* Remove hint when not animating */
  will-change: auto;
}
```

## WordPress-Specific Styles

### Admin Notices

```css
.notice {
  padding: 1rem;
  margin: 1rem 0;
  border-left: 4px solid;
}

.notice--success {
  border-color: var(--color-success);
  background-color: #ecf7ed;
}

.notice--error {
  border-color: var(--color-error);
  background-color: #fef7f7;
}

.notice--warning {
  border-color: var(--color-warning);
  background-color: #fffbf0;
}
```

### WordPress Admin Overrides

```css
/* Override WordPress admin styles when needed */
.wp-admin .custom-component {
  all: unset; /* Reset WordPress styles */
  /* Apply custom styles */
}
```

## Shadow DOM Styles

For components using Shadow DOM (like plugin dashboard):

```css
/* Styles must be encapsulated within shadow root */
:host {
  /* Host element styles */
  display: block;
  contain: layout style paint;
}

:host([hidden]) {
  display: none;
}

/* Styles don't leak out or in */
.dashboard-component {
  /* Isolated from global styles */
}
```

## Comments

```css
/* Section Comment */

/* Component: Card
   Description of the card component */
.card { }

/* State modifier */
.card--active { }

/**
 * Multi-line comment for
 * complex explanations
 */
```

---

**See Also**:
- [JavaScript Standards](javascript-standards.md)
- [WordPress Integration](wordpress-integration.md)
