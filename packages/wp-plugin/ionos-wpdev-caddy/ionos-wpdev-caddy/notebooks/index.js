/* global wp */
/* global ajaxurl */
/* eslint no-console: "off" */

// configure code editor defaults
Object.assign(wp.codeEditor.defaultSettings.codemirror, {
  mode: 'php',
  lineNumbers: true,
  tabSize: 2,
  indentUnit: 2,
  indentWithTabs: false,
  lineWrapping: false,
  autoCloseBrackets: true,
});

function getCellNameElement(cellElement) {
  return cellElement.querySelector('.notebook-cell-name');
}

function getCellEditorElement(cellElement) {
  return cellElement.querySelector('.notebook-cell-editor');
}

function getCellOutputElement(cellElement) {
  return cellElement.querySelector('.notebook-cell-output');
}

async function executeCell(php_code) {
  const body = new FormData();
  body.append('action', wp['ionos-wpdev-caddy-notebooks'].actions.execute);
  body.append('php_code', php_code);
  body.append('_ajax_nonce', wp.apiFetch.nonceMiddleware.nonce);

  const response = await wp.apiFetch({
    url: window.ajaxurl,
    method: 'POST',
    body,
    parse: false,
  });

  if (!response.ok) {
    throw new Error(`HTTP Error Status: ${response.status}`);
  }

  let data = await response.text();

  try {
    data = JSON.parse(data);
  } catch {
    throw new Error(`Failed to parse response as JSON: \n\n${data}`);
  }

  return data;
}

async function saveCell(notebook_name, cell_name, cell_value) {
  const body = new FormData();
  body.append('action', wp['ionos-wpdev-caddy-notebooks'].actions.save);
  body.append('notebook', notebook_name);
  body.append('cell', cell_name);
  body.append('value', cell_value);
  body.append('_ajax_nonce', wp.apiFetch.nonceMiddleware.nonce);

  return await wp.apiFetch({
    url: window.ajaxurl,
    method: 'POST',
    body,
  });
}

async function renameCell(notebook_name, from_cell_name, to_cell_name) {
  const body = new FormData();
  body.append('action', wp['ionos-wpdev-caddy-notebooks'].actions.rename);
  body.append('notebook', notebook_name);
  body.append('from_cell_name', from_cell_name);
  body.append('to_cell_name', to_cell_name);
  body.append('_ajax_nonce', wp.apiFetch.nonceMiddleware.nonce);

  return await wp.apiFetch({
    url: window.ajaxurl,
    method: 'POST',
    body,
  });
}

wp.domReady(() => {
  const settings = wp['ionos-wpdev-caddy-notebooks'];

  console.log('settings : ', settings);
  console.log('ajaxurl', ajaxurl);
  console.log('wp.CodeMirror.defaults', wp.CodeMirror.defaults);

  const { current: notebook } = settings;
  const cellTemplate = document.getElementById('notebook-cell-template').content;
  const textareaEditorMapping = new Map();

  const app_root = document.getElementById('ionos-wpdev-caddy-notebooks');

  function* id_generator(startValue = 0) {
    let count = startValue;
    while (true) {
      yield count++;
    }
  }

  const idGenerator = id_generator();
  idGenerator.next(); // skip 0

  function bindCellElement(cell) {
    const e = cellTemplate.cloneNode(true);

    const cell_id = idGenerator.next().value;

    const eName = getCellNameElement(e);
    eName.textContent = cell.name;

    const eTextarea = getCellEditorElement(e);
    eTextarea.value = cell.value;
    eTextarea.id = eName.for = cell_id + '-editor';

    const eOutputLabel = e.querySelector('.notebook-cell-output-label');
    const eOutput = getCellOutputElement(e);
    eOutput.id = eOutputLabel.for = cell_id + '-output';

    const eExecute = e.querySelector('.notebook-cell-execute');
    eExecute.onclick = async () => {
      eExecute.disabled = true;
      eOutput.classList.remove('error', 'success');
      eOutput.classList.add('progress');
      eOutput.value = 'Executing PHP Code ...';
      try {
        const response = await executeCell(textareaEditorMapping.get(eTextarea).codemirror.getValue());
        eOutput.value = response.data;
        eOutput.classList.replace('progress', response.success ? 'success' : 'error');
      } catch (error) {
        console.error('API Fetch Failure:', error);
        eOutput.value = error.message;
        eOutput.classList.replace('progress', 'error');
      } finally {
        eExecute.disabled = false;
      }
    };

    e.querySelector('.notebook-cell-reset').onclick = () =>
      textareaEditorMapping.get(eTextarea).codemirror.setValue(eTextarea.value);

    const eSave = e.querySelector('.notebook-cell-save');
    eSave.onclick = async () => {
      const value = textareaEditorMapping.get(eTextarea).codemirror.getValue();
      await saveCell(notebook.name, cell.name, value);
      eTextarea.value = value;
    };

    const eRename = e.querySelector('.notebook-cell-rename');
    eRename.onclick = async () => {
      let newName = prompt('Enter new cell name:', cell.name.replace(/\.php$/, ''));
      if (newName && newName !== cell.name) {
        newName = newName.trim();
        if (!newName) {
          alert('New cell name cannot be empty!');
          return;
        }

        newName = newName + '.php';
        const existingCell = notebook.cells.find((c) => c.name === newName);
        if (existingCell) {
          alert(`A cell with the name "${newName}.php" already exists!`);
          return;
        }

        await renameCell(notebook.name, cell.name, newName);
        eName.textContent = cell.name = newName;
      }
    };

    app_root.appendChild(e);

    const editor = wp.codeEditor.initialize(eTextarea);
    textareaEditorMapping.set(eTextarea, editor);

    eName.onclick = () => editor.codemirror.focus();
  }

  {
    for (const cell of notebook.cells) {
      bindCellElement(cell);
    }
  }
});
