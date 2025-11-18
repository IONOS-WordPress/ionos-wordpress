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
  body.append('action', wp['ionos-wpdev-caddy-notebooks'].ajax_action);
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

wp.domReady(() => {
  const settings = wp['ionos-wpdev-caddy-notebooks'];

  console.log('settings : ', settings);
  console.log('ajaxurl', ajaxurl);
  console.log('wp.CodeMirror.defaults', wp.CodeMirror.defaults);

  const { current: notebook } = settings;
  const cellTemplate = document.getElementById('notebook-cell-template').content;
  const bindings = new Map();
  const textareaEditorMapping = new Map();

  const app_root = document.getElementById('ionos-wpdev-caddy-notebooks');

  function bindCellElement(cell) {
    const e = cellTemplate.cloneNode(true);

    const eName = getCellNameElement(e);
    eName.setAttribute('for', cell.name + '-editor');
    eName.textContent = cell.name;

    const eTextarea = getCellEditorElement(e);
    eTextarea.value = cell.value;
    eTextarea.id = eName.for;

    const eOutputLabel = e.querySelector('.notebook-cell-output-label');
    eOutputLabel.setAttribute('for', cell.name + '-output');
    const eOutput = getCellOutputElement(e);
    eOutput.id = cell.name + '-output';

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

    app_root.appendChild(e);

    const editor = wp.codeEditor.initialize(eTextarea);
    textareaEditorMapping.set(eTextarea, editor);

    eName.onclick = () => editor.codemirror.focus();

    bindings.set(e, cell);
  }

  {
    for (const cell of notebook.cells) {
      bindCellElement(cell);
    }
  }
});
