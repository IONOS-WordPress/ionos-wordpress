docker run \
  --rm \
  --user "1000:1000" \
  -v $(pwd)/packages/wp-plugin/ionos-essentials:/project/dist \
  -v $(pwd)/packages/docker/rector-php/rector-code-quality.php:/project/rector-code-quality.php \
  ionos-wordpress/rector-php \
  --clear-cache \
  --config "rector-code-quality.php" \
  process \
  .
