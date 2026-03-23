/*
  this script is not intended to be executed directly. its part of the `pnpm stretch-extra --check` command.

  it checks if the plugins and themes defined in the stretch extra configuration are up to date by comparing
  the version extracted from the download URL with the latest version available from the wordpress.org API or the Update URI provided in the configuration. It returns 0 if all plugins/themes are up to date, 1 if at least one plugin/theme is outdated,
  and -1 if at least one plugin/theme is ahead of the latest version.
*/

const VERBOSE = (process.env.VERBOSE ?? '') === 'true';

async function check_latest_version(update_uri, current_version, type_singular, slug) {
  VERBOSE && console.log(`Fetching update information for ${type_singular} ${slug} from Update URI: ${update_uri} ...`);
  const response = await fetch(update_uri, {
    headers: {
      accept: 'application/json',
    },
  }).then((res) => res.json());
  const latest_version = response?.version;

  if (current_version !== latest_version) {
    console.error(
      `${type_singular} ${slug} : current version(=${current_version}) is different to latest version (=${latest_version}).`
    );
    return 1;
  }

  VERBOSE &&
    console.log(
      `${type_singular} ${slug} is up to date. current version: ${current_version}, Latest version: ${latest_version}`
    );
  return 0;
}

const STRETCH_EXTRA_CONFIG_JSON = JSON.parse(process.env.STRETCH_EXTRA_CONFIG_JSON);
VERBOSE && console.log('STRETCH_EXTRA_CONFIG_JSON:', STRETCH_EXTRA_CONFIG_JSON);

// overall return code of the check operation, will be set to 1 if at least one plugin/theme is outdated, -1 if at least one plugin/theme is ahead of the latest version, or 0 if all plugins/themes are up to date
let ret_code = 0;

// type_plural is either 'plugins' or 'themes'
for (const [type_plural, items] of Object.entries(STRETCH_EXTRA_CONFIG_JSON)) {
  VERBOSE && console.log(`Processing ${type_plural} ...`);
  for (const item of items) {
    const url = item['url'];
    // fallback to slug extraction from URL if slug is not provided in config
    const slug =
      item['slug'] ??
      url
        .split('/')
        .slice(-1)[0]
        .replace(/\.(\.|\d)+\.zip$/, '');
    // type_singular is either 'plugin' or 'theme'
    const type_singular = type_plural.substring(0, type_plural.length - 1); // remove plural 's' from type_plural to get type_singular (plugin/theme)

    // current version is extracted from the URL by matching the last occurrence of a version-like pattern
    // (e.g. 1.2.3) before the .zip extension, or 'latest' if no version pattern is found
    const current_version =
      url
        .split('/')
        .slice(-1)[0]
        .match(/(\d+\.)(\d+\.)(\*|\d+)/g)?.[0] ?? 'latest';

    if (current_version === 'latest') {
      VERBOSE &&
        console.log(
          `skip ${type_singular} ${slug} : ${type_singular} version could not be extracted from URL ${url}, assuming 'latest'`
        );
      continue;
    }

    VERBOSE && console.log(`Current version of ${type_singular} ${slug} is ${current_version}`);

    if (url.startsWith('https://downloads.wordpress.org')) {
      // fetch plugin / theme information from wordpress.org API to get the latest version
      ret_code |= await check_latest_version(
        `https://api.wordpress.org/${type_plural}/info/1.2/?action=${type_singular}_information&request[slug]=${slug}`,
        current_version,
        type_singular,
        slug
      );
    } else if (item?.data?.['Update URI']) {
      ret_code |= await check_latest_version(item.data['Update URI'], current_version, type_singular, slug);
    } else {
      console.warn(
        `skip ${type_singular} ${slug} : don't know how to get update informations for ${type_singular} ${slug}`
      );
    }
  }
}

process.exit(ret_code);
