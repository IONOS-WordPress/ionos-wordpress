/*
  js module injected into caddy admin page
*/

const caddySettings = wp['ionos-wpdev-caddy'];

// load and process catalogs
const loadedCatalogs = ((responses)=>{
  return Object.fromEntries(
    responses
      .map((response, index) => {
        if (response.status === 'fulfilled') {
          const url = caddySettings.catalogs[index];
          let catalog = response.value.default;

          console.log(`Catalog(='${catalog.caption}') loaded from '${url}' :`, catalog);
          return [url, catalog];
        } else {
          console.error(`Failed to load Catalog(=${caddySettings.catalogs[index]}) :`, response.reason);
        }
      })
  );
})(
  // load all catalogs in parallel ignoring failures
  await Promise.allSettled(
    caddySettings.catalogs.map(url => import(url, { with: { type: 'json' } }))
  )
);

// all html elements of our page having a id attribute <string, HTMLElement>
const controls = {};

function onCatalogChanged() {
  const value = document.getElementById('catalogs').value;
  const selectedCatalog = loadedCatalogs[value]; 
  const snippetSelect = document.getElementById('catalog_snippet');
  
  snippetSelect.replaceChildren(
    ...selectedCatalog.items.map(item => {
      const option = document.createElement('option');
      option.value = item.body;
      option.textContent = item.caption ?? item.body.substring(0, 30) + '...';
      option.title = item.description ?? item.body;
      return option;
    })
  );
}

// Map<textarea, wp.codeEditor> for all codemirror'ized textareas
const textareaEditorMapping = new Map();

function onCatalogSnippetChanged() {
  const select = document.getElementById('catalog_snippet');
  const value = select.value;
  const textarea = select.form.querySelector('textarea');
  
  textarea.value = value;
  const editor = textareaEditorMapping.get(textarea);
  editor.codemirror.setValue(value);
  editor.codemirror.focus();
}

function populateCatalogSelect() {
  // populate catalog select dropdown
  const options = Object.entries(loadedCatalogs).map(([url, catalog]) => {
    const option = document.createElement('option');
    option.value = url;
    option.textContent = catalog.caption ?? url.split('/').pop();
    option.title ??= catalog.description;
    return option;
  });
  document.getElementById('catalogs').replaceChildren(...options);
}

// initialize editor for all textareas on the page
wp.domReady(()=> {
  // collect all elements having a id attribute into controls
  for( const control of document.querySelectorAll('.wrap [id]')) {
    controls[control.id] = control;
  }

  // populate catalog select dropdown
  populateCatalogSelect();

  // event listener registration
  const catalogSelect = document.getElementById('catalogs');
  catalogSelect.onchange = onCatalogChanged;

  const catalogSnippetSelect = document.getElementById('catalog_snippet');
  catalogSnippetSelect.onchange = onCatalogSnippetChanged;

  document.getElementById('execute').onclick = async () => {
    const textarea = document.getElementById('php_editor');
    const editor = textareaEditorMapping.get(textarea);

    const body = new FormData();
    body.append('action', wp['ionos-wpdev-caddy'].ajax_action);
    body.append('php_code', editor.codemirror.getValue());
    body.append('_ajax_nonce', wp.apiFetch.nonceMiddleware.nonce);

    const { output } = controls;
    output.classList.remove('error', 'success');
    output.value = 'Executing PHP Code ...';
    output.classList.add('progress');
    try {
      const response = await wp.apiFetch({
          url   : wp['ionos-wpdev-caddy'].ajax_url, 
          method: 'POST',
          body,
      });

      output.value = response.data;
      output.classList.replace('progress', response.success ? 'success' : 'error');

      if (response.success) {
        console.log('API Fetch Success:', response.data);
      } else {
        console.error('API Fetch Error:', response.data);
      }
    } catch (error) {
      console.error('API Fetch Failure:', error);

      output.value = response.data;
      output.classList.replace('progress', 'error');
    }
  };

  document.getElementById('reset').onclick = onCatalogSnippetChanged;
  document.getElementById('save').onclick = () => {
    const textarea = document.getElementById('php_editor');
    const editor = textareaEditorMapping.get(textarea);
    textarea.value = editor.codemirror.getValue();

    const selectedSnippetCaption = catalogSnippetSelect.options[catalogSnippetSelect.selectedIndex].label;

    const snippetCaption = (prompt("Enter catalog snippet name to save to:", selectedSnippetCaption) || '').trim();
    if (snippetCaption) {
      const selectedCatalogUrl = catalogSelect.value;
      const selectedCatalog = loadedCatalogs[selectedCatalogUrl];

      const existingItemIndex = selectedCatalog.items.findIndex(item => item.caption === snippetCaption);
      if (existingItemIndex !== -1) {
        // Update existing item
        selectedCatalog.items[existingItemIndex].body = textarea.value;
      } else {
        // Add new item
        selectedCatalog.items.push({
          caption: snippetCaption,
          description: '',
          body: textarea.value,
        });
      }

      // trigger updating of snippet select
      onCatalogChanged();
      // select the saved snippet
      for( const option of catalogSnippetSelect.options) {
        if( option.label === snippetCaption) {
          option.selected = true;
          break;
        }
      }
    }
  };

  document.getElementById('export').onclick = () => {
    const selectedCatalogUrl = catalogSelect.value;
    const selectedCatalog = loadedCatalogs[selectedCatalogUrl];
    const dialog = document.getElementById('export_dialog');

    dialog.querySelector('textarea').value = JSON.stringify(selectedCatalog, null, 2);
    dialog.showModal();
  };

  document.getElementById('import').onclick = () => {
    const selectedCatalogUrl = catalogSelect.value;
    const selectedCatalog = loadedCatalogs[selectedCatalogUrl];
    const dialog = document.getElementById('import_dialog');

    dialog.querySelector('textarea').value = JSON.stringify(selectedCatalog, null, 2);
    dialog.showModal();
  };

  document.getElementById('import_catalog').onclick = () => {
    const json = JSON.parse(
      document.getElementById('import_dialog').querySelector('textarea').value
    );

    let url = `#${Object.keys(loadedCatalogs).length}`;
    const matchedEntry = Object.entries(loadedCatalogs).find(([url, catalog]) => catalog.caption === json.caption);

    if (matchedEntry) {
      url = matchedEntry[0];
    }
    
    loadedCatalogs[url] = json;

    document.getElementById('import_dialog').close();
    populateCatalogSelect();
    document.getElementById('catalogs').value = url;
    onCatalogChanged();
  };

  // initialize codemirror
  const textarea = document.getElementById('php_editor');
  const editor = wp.codeEditor.initialize( textarea);
  textareaEditorMapping.set(textarea, editor);

  // assign label to codemirror editor focus
  textarea.onclick = () => editor.codemirror.focus();

  // fire initial events to populate selects and editors 
  onCatalogChanged();
  onCatalogSnippetChanged();
});
