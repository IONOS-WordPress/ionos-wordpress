# JavaScript Coding Standards

## Core Principles

- **No jQuery** - Use native JavaScript or WordPress packages
- **ES6+** - Modern syntax, transpiled via `@wordpress/scripts`
- **Async/await** - Always prefer over `.then()` chains
- **Native DOM** - Modern browser APIs, no library overhead

## Module System

```javascript
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
```

## Variables

```javascript
const API_ENDPOINT = '/wp/v2/posts'; // Immutable
let count = 0; // Mutable
// Never use var
```

## Initialization

**ALWAYS use `@wordpress/dom-ready` - NEVER use native `DOMContentLoaded`:**

```javascript
import domReady from '@wordpress/dom-ready';

domReady(() => {
  const container = document.querySelector('.feature');
  if (!container) return;
  // Initialize
});
```

**❌ Don't:**

```javascript
// Never use native DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
  // Bad - use domReady instead
});
```

## Functions

**Arrow functions for single expressions:**

```javascript
const multiply = (a, b) => a * b;
const ids = items.map((item) => item.id);
```

**Anonymous functions for multi-line:**

```javascript
button.addEventListener('click', async function (event) {
  event.target.disabled = true;
  await performOperation();
  event.target.disabled = false;
});
```

**Named when reused:**

```javascript
function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
```

## Async/Await

**Always use try/catch:**

```javascript
async function fetchUserData(userId) {
  try {
    const user = await apiFetch({ path: `/wp/v2/users/${userId}` });
    return user;
  } catch (error) {
    console.error('Failed:', error);
    window.EXOS.snackbar.warning(__('Request failed', 'text-domain'));
    return null;
  }
}
```

**Parallel operations:**

```javascript
const [user, settings, stats] = await Promise.all([fetchUser(), fetchSettings(), fetchStats()]);
```

## Array Methods

**Prefer `for` loops over `forEach()`:**

```javascript
// ✅ for...of - early exit, better performance
for (const item of items) {
  if (item.invalid) break;
  processItem(item);
}

// ✅ Functional methods for transformations
const ids = items.map((item) => item.id);
const active = items.filter((item) => item.active);
const user = users.find((u) => u.id === targetId);
```

## DOM Manipulation

```javascript
// Query
const element = document.querySelector('.class');
const elements = document.querySelectorAll('.items');
const parent = element.closest('.parent');

// Create & Insert
const button = document.createElement('button');
button.textContent = __('Click', 'text-domain');
container.append(button); // Modern method

// Classes
element.classList.add('active');
element.classList.toggle('expanded');

// Attributes
button.disabled = true;
element.dataset.tab = 'settings'; // data-tab="settings"

// Content
element.textContent = 'Safe text'; // No HTML parsing
```

## Event Handling

```javascript
// Single element
element.addEventListener('click', async (event) => {
  event.preventDefault();
  await handleClick();
});

// Delegation for dynamic content
container.addEventListener('click', (event) => {
  if (event.target.matches('.button')) {
    handleClick(event.target);
  }
});
```

## API Communication

```javascript
import apiFetch from '@wordpress/api-fetch';

// GET
const posts = await apiFetch({ path: '/wp/v2/posts' });

// POST
const result = await apiFetch({
  path: '/vendor/plugin/v1/endpoint',
  method: 'POST',
  data: { id: 123, value: 'test' },
});
```

## EXOS Framework (Dashboard Only)

**Snackbar notifications:**

```javascript
window.EXOS.snackbar.success(__('Saved', 'text-domain'));
window.EXOS.snackbar.warning(__('Check input', 'text-domain'));
window.EXOS.snackbar.critical(__('Error occurred', 'text-domain'));
```

**React components:**

```javascript
const { Button, Card } = window.EXOS.react;

const button = React.createElement(
  Button,
  {
    variant: 'primary',
    onClick: handleClick,
  },
  __('Click Me', 'text-domain')
);
```

## jQuery Migration Reference

```javascript
// Selection
$('.class')              → document.querySelectorAll('.class')
$('#id')                 → document.querySelector('#id')

// Classes
$('.el').addClass('x')   → element.classList.add('x')
$('.el').toggleClass()   → element.classList.toggle('x')

// Content
$('.el').text('x')       → element.textContent = 'x'
$('.el').html('<p>')     → element.innerHTML = '<p>'
$('.el').val('x')        → element.value = 'x'

// DOM
$('.c').append(el)       → container.append(el)
$('.el').remove()        → element.remove()

// Events
$('.el').on('click', h)  → element.addEventListener('click', h)
$(document).ready(fn)    → domReady(fn) // Import from @wordpress/dom-ready

// DOM Ready (NEVER use native DOMContentLoaded)
$(document).ready()      → domReady() // @wordpress/dom-ready
DOMContentLoaded         → domReady() // ALWAYS use @wordpress package

// AJAX
$.ajax()                 → apiFetch() or fetch()
```

## Code Formatting

- **Indentation**: 2 spaces
- **Line Length**: 120 characters max
- **Semicolons**: Always
- **Quotes**: Single quotes
- **Trailing Commas**: ES5 style

## Common Patterns

**Form submission:**

```javascript
form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const formData = new FormData(event.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const response = await apiFetch({
      path: '/endpoint',
      method: 'POST',
      data,
    });
    window.EXOS.snackbar.success(__('Saved', 'text-domain'));
  } catch (error) {
    window.EXOS.snackbar.critical(__('Failed', 'text-domain'));
  }
});
```

**Debounce:**

```javascript
let timer;
input.addEventListener('input', (event) => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    handleInput(event.target.value);
  }, 300);
});
```

## Performance

- Cache DOM queries
- Use event delegation
- Avoid layout thrashing
- Prefer `transform` and `opacity` for animations

---

**See Also**: [WordPress Integration](wordpress-integration.md), [E2E Testing](e2e-testing.md), [Security Standards](security.md)
