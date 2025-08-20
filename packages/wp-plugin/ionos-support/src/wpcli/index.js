import fetch from '@wordpress/api-fetch';

window.wp.cli = (config) => {
  window.wp.cli = async function (command) {
    if (typeof command !== 'string' || command.trim() === '') {
      console.error('Command must be a non-empty string');
      window.wp.cli.help();
      return;
    }

    try {
      console.group(`wp ${command}`);

      const response = await fetch({
        path: `/${config.REST_NAMESPACE}${config.REST_ROUTE_EXEC}`,
        method: 'POST',
        data: { command },
      });

      response.data.stderr && console.error(response.data.stderr);

      if (response.data.stdout) {
        if (command.includes(' --json ')) {
          try {
            console.table(JSON.parse(response.data.stdout));
          } catch (ex) {
            console.info(response.data.stdout);
          }
        } else {
          console.info(response.data.stdout);
        }
      }

      return response.data.stdout;
    } finally {
      console.groupEnd();
    }
  };

  window.wp.cli.serialize = async function (data) {
    if (data === undefined) {
      console.error('Parameter "data" is required');
      window.wp.cli.help();
      return;
    }

    console.group(`serialize ${JSON.stringify(data)}`);

    const response = await fetch({
      path: `/${config.REST_NAMESPACE}${config.REST_ROUTE_SERIALIZE}`,
      method: 'POST',
      data,
    });

    console.info(response.data);
    console.groupEnd();
  };

  window.wp.cli.VERSION = config.VERSION;
  window.wp.cli.help = () =>
    console.info(`
    Usage: wp.cli( ...[options] [--] <command> [<args>...] )
    Options:
      -h, --help      Show this help message
      -v, --version   Show the version

    Example usage:
      wp.cli('--help')
      wp.cli('option list --json --search=ionos*')
      wp.cli.serialize("huhu")

    ${config.VERSION}
  `);
};
