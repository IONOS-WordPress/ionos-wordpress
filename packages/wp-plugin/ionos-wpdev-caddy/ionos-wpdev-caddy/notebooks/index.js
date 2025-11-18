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
