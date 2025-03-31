# About

This image provides the most recent [potrans](https://github.com/OzzyCzech/potrans) translation utility in a docker image.

## Why ?

I needed a way to provide [potrans](https://github.com/OzzyCzech/potrans) on demand and cross platform (Linux/maxOS/Windows).

=> That's exactly what a Docker image can do :-)

# Usage

@TODO: add usage documentation

See [potrans](https://github.com/OzzyCzech/potrans) homepage for all options.

## Example usage

```bash
docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" -v $(pwd):/project ionos-wordpress/potrans deepl --from="en" --to="de_DE" --no-cache --apikey='your-deepl-api-key' packages/wp-plugin/essentials/languages/essentials-de_DE.po packages/wp-plugin/essentials/languages/
```
