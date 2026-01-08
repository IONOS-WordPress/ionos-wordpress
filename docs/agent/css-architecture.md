# CSS Architecture Standards

## Framework Preference

**Use EXOS CSS framework** (`https://ce1.uicdn.net/exos/framework/3.0/exos.min.css`) for dashboard/admin UI:

```html
<!-- ✅ Use EXOS classes -->
<div class="exos-card exos-padding-md exos-margin-bottom-lg">
  <h2 class="exos-heading-lg exos-text-primary">Dashboard Title</h2>
  <button class="exos-button exos-button--primary">Action</button>
</div>
```

Write custom CSS only when EXOS doesn't provide the component or for unique plugin-specific styling.

## CSS Methodology

### Prefer Native CSS Over SCSS

- **CSS Variables** (not SCSS variables)
- **Native CSS Nesting** (not SCSS nesting)
- CSS `calc()`, `clamp()`, `min()`, `max()`
- CSS Grid and Flexbox
- Use SCSS only for complex mixins or significant code reuse

### CSS Variables

```css
:root {
  --color-primary: #0073aa;
  --spacing-unit: 8px;
  --border-radius: 4px;
}

.button {
  background-color: var(--color-primary);
  padding: calc(var(--spacing-unit) * 2);
  border-radius: var(--border-radius);
}
```

### Native CSS Nesting

```css
.card {
  padding: 1rem;
  border: 1px solid var(--color-border);

  &__header {
    font-weight: bold;
  }

  &--featured {
    border-color: var(--color-primary);
  }

  &:hover {
    transform: translateY(-2px);
  }

  @media (min-width: 768px) {
    padding: 2rem;
  }
}
```

## BEM Naming

```css
/* Block */
.card { }

/* Element */
.card__header { }
.card__content { }

/* Modifier */
.card--featured { }
.card--large { }
```

## Modern CSS Features

```css
/* Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-unit);
}

/* Flexbox */
.button-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

/* Modern Units */
.container {
  width: clamp(320px, 90vw, 1200px);
  padding: clamp(1rem, 5vw, 3rem);
}

/* Logical Properties */
.card {
  padding-block: 1rem;        /* top and bottom */
  padding-inline: 2rem;       /* left and right */
}
```

## Responsive Design (Mobile-First)

```css
.card {
  padding: 1rem;
}

@media (min-width: 768px) {
  .card {
    padding: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .card {
    padding: 2rem;
  }
}
```

## Animations

```css
/* ✅ GPU-accelerated */
.element {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.element:hover {
  transform: translateY(-2px);
}

/* Respect user preferences */
@media (prefers-reduced-motion: reduce) {
  .element {
    transition: none;
  }
}
```

## Accessibility

```css
/* Focus states */
.button:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Screen reader only */
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

- **Indentation**: 2 spaces
- **Line Length**: 120 characters max
- **Semicolons**: Always
- **Quotes**: Single quotes

---

**See Also**: [JavaScript Standards](javascript-standards.md), [WordPress Integration](wordpress-integration.md)
