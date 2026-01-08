# JavaScript Coding Standards

## ECMAScript Version

- **Target**: ES6+ (ESNext)
- **Transpilation**: Webpack via `@wordpress/scripts`
- All modern JavaScript features are supported and transpiled automatically

## Module System

### ES6 Modules

Always use ES6 import/export syntax:

```javascript
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';

export function myFunction() {
  // Implementation
}

export default MyComponent;
```

### WordPress Package Imports

Common WordPress packages:

```javascript
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { speak } from '@wordpress/a11y';
```

## Variable Declarations

### Const and Let

- **Use `const` by default** for values that won't be reassigned
- **Use `let`** for values that will change
- **Never use `var`**

```javascript
const API_ENDPOINT = '/wp/v2/posts';
const container = document.querySelector('.container');

let count = 0;
let isActive = false;
```

## Initialization Pattern

### DOM Ready

Use `@wordpress/dom-ready` for initialization:

```javascript
import domReady from '@wordpress/dom-ready';

domReady(() => {
  initializeFeature();
});

function initializeFeature() {
  const container = document.querySelector('.feature-container');

  if (!container) {
    return;
  }

  // Feature implementation
}
```

### Shadow DOM Access

For plugin dashboard with Shadow DOM:

```javascript
import domReady from '@wordpress/dom-ready';

domReady(() => {
  const parent = document.querySelector('.plugin-dashboard');

  if (!parent) {
    return;
  }

  const dashboard = parent.querySelector('#wpbody-content').shadowRoot;

  // All queries within shadow root
  dashboard.querySelector('.element')?.addEventListener('click', handler);
});
```

## Functions

### Function Declarations

```javascript
// Named function
function functionName(param1, param2) {
  // Implementation
  return result;
}

// Arrow function
const arrowFunction = (param) => {
  // Implementation
  return result;
};

// Short arrow function
const multiply = (a, b) => a * b;

// Async function
async function fetchData() {
  const response = await fetch(url);
  return await response.json();
}
```

### Function Parameters

Use default parameters and destructuring:

```javascript
function createUser(name, age = 18, options = {}) {
  // Implementation
}

function processData({ id, name, email }) {
  // Destructured parameters
}
```

## Async/Await

### Preferred Pattern

Always use async/await over Promise chains:

```javascript
// ✅ Good - async/await
async function fetchUserData(userId) {
  try {
    const user = await apiFetch({ path: `/wp/v2/users/${userId}` });
    const posts = await apiFetch({ path: `/wp/v2/posts?author=${userId}` });
    return { user, posts };
  } catch (error) {
    console.error('Failed to fetch data:', error);
    return null;
  }
}

// ❌ Avoid - Promise chains
function fetchUserData(userId) {
  return apiFetch({ path: `/wp/v2/users/${userId}` })
    .then(user => apiFetch({ path: `/wp/v2/posts?author=${userId}` })
      .then(posts => ({ user, posts })))
    .catch(error => {
      console.error(error);
      return null;
    });
}
```

### Error Handling

Always use try-catch with async/await:

```javascript
async function safeAPICall() {
  try {
    const data = await apiFetch({ path: '/endpoint' });
    return data;
  } catch (error) {
    console.error('API Error:', error);
    window.EXOS.snackbar.warning(__('Request failed', 'text-domain'));
    return null;
  }
}
```

### Parallel Operations

Use `Promise.all()` for parallel async operations:

```javascript
// Sequential (slow)
const user = await fetchUser();
const settings = await fetchSettings();
const stats = await fetchStats();

// Parallel (fast)
const [user, settings, stats] = await Promise.all([
  fetchUser(),
  fetchSettings(),
  fetchStats(),
]);
```

## Array Methods

### Iteration

Prefer functional array methods:

```javascript
// forEach - side effects
items.forEach((item) => {
  console.log(item);
});

// map - transform array
const ids = items.map((item) => item.id);
const doubled = numbers.map((n) => n * 2);

// filter - select subset
const active = items.filter((item) => item.active);
const valid = inputs.filter((input) => input.value !== '');

// find - get first match
const user = users.find((u) => u.id === targetId);

// some - check if any match
const hasError = validations.some((v) => v.error);

// every - check if all match
const allValid = inputs.every((input) => input.checkValidity());

// reduce - accumulate value
const total = numbers.reduce((sum, n) => sum + n, 0);
```

## DOM Manipulation

### Query Selectors

```javascript
// Single element (returns null if not found)
const element = document.querySelector('.class-name');
const byId = document.getElementById('element-id');

// Multiple elements (returns NodeList)
const elements = document.querySelectorAll('.class-name');

// Safe access with optional chaining
document.querySelector('.button')?.addEventListener('click', handler);
```

### Class Manipulation

```javascript
// Add/remove classes
element.classList.add('active');
element.classList.remove('inactive');
element.classList.toggle('expanded');
element.classList.toggle('visible', isVisible);

// Multiple classes
element.classList.add('active', 'highlighted', 'primary');

// Check class
if (element.classList.contains('active')) {
  // Do something
}
```

### Attributes

```javascript
// Set attributes
element.setAttribute('aria-expanded', 'true');
element.setAttribute('data-value', '123');

// Get attributes
const value = element.getAttribute('data-value');

// Remove attributes
element.removeAttribute('disabled');

// Data attributes
const tabName = element.dataset.tab;       // Gets data-tab
const nbaId = element.dataset.nbaId;       // Gets data-nba-id
```

### Content Manipulation

```javascript
// Text content
element.textContent = 'New text';
element.innerText = __('Translated text', 'text-domain');

// HTML content (use sparingly)
element.innerHTML = '<p>HTML content</p>';

// Form values
input.value = 'new value';
const checked = checkbox.checked;
checkbox.checked = true;

// Disable/enable
button.disabled = true;
```

## Event Handling

### Event Listeners

```javascript
// Single element
element.addEventListener('click', (event) => {
  event.preventDefault();
  // Handler logic
});

// Multiple elements
elements.forEach((button) => {
  button.addEventListener('click', handleClick);
});

// Async handler
element.addEventListener('click', async (event) => {
  event.preventDefault();
  const result = await processClick();
  updateUI(result);
});
```

### Event Delegation

For dynamic content:

```javascript
container.addEventListener('click', (event) => {
  if (event.target.matches('.dynamic-button')) {
    handleDynamicClick(event.target);
  }
});
```

### Common Event Patterns

**Button Click with Loading State**:
```javascript
button.addEventListener('click', async function (event) {
  event.target.disabled = true;
  event.target.innerText = __('Loading...', 'text-domain');

  await performOperation();

  event.target.disabled = false;
  event.target.innerText = __('Done', 'text-domain');
});
```

**Form Submission**:
```javascript
form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(event.target);
  const data = Object.fromEntries(formData.entries());

  const response = await apiFetch({
    path: '/endpoint',
    method: 'POST',
    data,
  });

  if (response) {
    window.EXOS.snackbar.success(__('Saved', 'text-domain'));
  }
});
```

## API Communication

### Using @wordpress/api-fetch

```javascript
import apiFetch from '@wordpress/api-fetch';

// GET request
const posts = await apiFetch({
  path: '/wp/v2/posts',
  method: 'GET',
});

// POST request
const result = await apiFetch({
  path: '/vendor/plugin/endpoint/v1/update',
  method: 'POST',
  data: {
    id: itemId,
    value: itemValue,
  },
});

// With query parameters
const filteredPosts = await apiFetch({
  path: '/wp/v2/posts?per_page=5&status=publish',
});
```

### Using Fetch API

For non-WordPress endpoints or custom nonce handling:

```javascript
async function callCustomEndpoint(data) {
  try {
    const response = await fetch(wpData.restUrl + 'custom/endpoint', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpData.nonce,
      },
      credentials: 'include',
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    return null;
  }
}
```

## Global Variables

### Declaration

Declare global variables provided by PHP:

```javascript
/* global wpData:true */
/* global jQuery:true */

// Usage
console.log(wpData.restUrl);
console.log(wpData.nonce);
```

## Code Formatting

### Prettier Configuration

- **Indentation**: 2 spaces
- **Line Length**: Maximum 120 characters
- **Semicolons**: Always use
- **Quotes**: Single quotes for strings
- **Trailing Commas**: ES5 style (objects and arrays)
- **Bracket Spacing**: Yes (`{ key: value }`)

### Examples

```javascript
const config = {
  option1: 'value1',
  option2: 'value2',
  nested: {
    key: 'value',
  },
};

const array = ['item1', 'item2', 'item3'];

function example(param1, param2) {
  const result = param1 + param2;
  return result;
}
```

## Object and Array Operations

### Destructuring

```javascript
// Object destructuring
const { id, name, email } = user;
const { title = 'Default' } = post;

// Array destructuring
const [first, second, ...rest] = items;

// Function parameters
function processUser({ id, name, email }) {
  // Use destructured properties
}
```

### Spread Operator

```javascript
// Array spread
const combined = [...array1, ...array2];
const copy = [...original];

// Object spread
const merged = { ...defaults, ...options };
const updated = { ...user, email: 'new@email.com' };
```

## Template Literals

Use template literals for string interpolation:

```javascript
// ✅ Good
const message = `Hello, ${name}! You have ${count} messages.`;
const html = `<div class="${className}">${content}</div>`;

// ❌ Avoid
const message = 'Hello, ' + name + '! You have ' + count + ' messages.';
```

## Comments

### Code Comments

```javascript
// Single-line comment

/* Multi-line comment
   for detailed explanations */

// TODO: Add error handling
// FIXME: This breaks with empty arrays
```

### Documentation Comments

Keep minimal - prefer self-documenting code:

```javascript
/**
 * Fetches user data from the API.
 *
 * @param {number} userId - The user ID
 * @returns {Promise<Object>} User data object
 */
async function fetchUser(userId) {
  // Implementation
}
```

## Error Handling

### Console Methods

```javascript
// Development debugging (remove before commit)
console.log('Debug info:', data);
console.error('Error:', error);
console.warn('Warning:', warning);
console.table(arrayOfObjects);

// Grouped logging
console.group('Feature Name');
console.log('Step 1:', value1);
console.log('Step 2:', value2);
console.groupEnd();
```

### User-Facing Notifications

```javascript
// Success (green)
window.EXOS.snackbar.success(__('Operation completed', 'text-domain'));

// Warning (orange)
window.EXOS.snackbar.warning(__('Please check input', 'text-domain'));

// Critical/Error (red)
window.EXOS.snackbar.critical(__('An error occurred', 'text-domain'));
```

## Performance Best Practices

### Cache DOM Queries

```javascript
// ✅ Good - query once
const dashboard = getShadowRoot();
const elements = dashboard.querySelectorAll('.item');

// ❌ Bad - query repeatedly
for (let i = 0; i < count; i++) {
  getShadowRoot().querySelector('.item');
}
```

### Debounce Expensive Operations

```javascript
let debounceTimer;
input.addEventListener('input', (event) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    handleInput(event.target.value);
  }, 300);
});
```

### Use Event Delegation

```javascript
// ✅ Good - one listener
container.addEventListener('click', (event) => {
  if (event.target.matches('.button')) {
    handleClick(event.target);
  }
});

// ❌ Bad - listener per element
buttons.forEach((button) => {
  button.addEventListener('click', handleClick);
});
```

## Browser APIs

### LocalStorage

```javascript
// Save
localStorage.setItem('key', 'value');
localStorage.setItem('object', JSON.stringify(data));

// Retrieve
const value = localStorage.getItem('key');
const object = JSON.parse(localStorage.getItem('object'));

// Remove
localStorage.removeItem('key');
```

### Clipboard

```javascript
navigator.clipboard.writeText(text)
  .then(() => {
    window.EXOS.snackbar.success(__('Copied', 'text-domain'));
  })
  .catch(() => {
    window.EXOS.snackbar.warning(__('Failed to copy', 'text-domain'));
  });
```

### Hash Navigation

```javascript
// Get hash
const hash = window.location.hash.substring(1);

// Set hash
window.location.hash = 'section-name';

// Listen for changes
window.addEventListener('hashchange', () => {
  const newHash = window.location.hash.substring(1);
  handleHashChange(newHash);
});
```

## Code Organization

### Feature Modules

```javascript
// Feature initialization
function initializeDashboard() {
  setupTabs();
  setupDialogs();
  setupEventTracking();
}

// Specific feature setup
function setupTabs() {
  const tabButtons = dashboard.querySelectorAll('[data-tab]');
  tabButtons.forEach((button) => {
    button.addEventListener('click', handleTabClick);
  });
}

function handleTabClick(event) {
  // Tab logic
}

// Initialize
domReady(initializeDashboard);
```

### Helper Functions

```javascript
// Reusable utilities
function updateItem(id, status) {
  return apiFetch({
    path: '/endpoint',
    method: 'POST',
    data: { id, status },
  });
}

function getShadowRoot() {
  const parent = document.querySelector('.plugin-dashboard');
  return parent?.querySelector('#wpbody-content')?.shadowRoot;
}
```

---

**See Also**:
- [WordPress Integration](wordpress-integration.md)
- [E2E Testing](e2e-testing.md)
- [Security Standards](security.md)
