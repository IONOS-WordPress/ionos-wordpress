import fetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'ionos/support/v1';

async function runWPCLICommand(command) {
  const response = await fetch(`/wp-json/${API_NAMESPACE}/run`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ command }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  return response.json();
}

async function execWPCLICommand(command) {
  const response = await fetch(`/wp-json/${API_NAMESPACE}/exec`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ command }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  return response.json();
}

window.wp.cli = (config) => {
  window.wp.cli = {
    run: runWPCLICommand,
    exec: execWPCLICommand,
    version: config.version,
  };
};

console.log(window.wp.cli.version);
