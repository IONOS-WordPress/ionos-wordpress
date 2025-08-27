the wordpress filter `\add_filter('update_plugins_api.<domain>')` can return much more configuration than we allow right now.

example output of impex plugin

```php
[
'name' => 'cm4all-wp-impex',
'slug' => 'cm4all-wp-impex',
'version' => '1.6.0',
'author' => '<a href="https:\\/\\/cm4all.com">Lars Gersmann, CM4all<\\/a>',
'author_profile' => 'https:\\/\\/profiles.wordpress.org\\/cm4all\\/',
'contributors' => [
'cm4all' => [
'profile' => 'https:\\/\\/profiles.wordpress.org\\/cm4all\\/',
'avatar' => 'https:\\/\\/secure.gravatar.com\\/avatar\\/c50169eef63e643a96efc174cf099032?s=96&d=monsterid&r=g',
'display_name' => 'cm4all',
],
],
'requires' => '5.7',
'tested' => '6.2.6',
'requires_php' => '7.4',
'requires_plugins' => [
],
'rating' => 0,
'ratings' => [
1 => 0,
2 => 0,
3 => 0,
4 => 0,
5 => 0,
],
'num_ratings' => 0,
'support_url' => 'https:\\/\\/wordpress.org\\/support\\/plugin\\/cm4all-wp-impex\\/',
'support_threads' => 0,
'support_threads_resolved' => 0,
'active_installs' => 10,
'last_updated' => '2024-02-12 8:58am GMT',
'added' => '2022-02-02',
'homepage' => 'https:\\/\\/github.com\\/IONOS-WordPress\\/cm4all-wp-impex',
'sections' => [
'description' => '<p>ImpEx is a WordPress plugin that allows you to import and ...<\\/p>',
'faq' => '<p>Impex uses modern browser features as building blocks...<\\/p>',
'changelog' => '<p><em>Features<\\/em><\\/p>',
'screenshots' => '<ol><li><a href="https:\\/\\/ps.w.org\\/cm4all-wp...-wp-impex\\/<\\/ol>',
'reviews' => '',
],
'download_link' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.6.0.zip',
'upgrade_notice' => [
'' => '<p>There is currently no upgrade needed.<\\/p>',
],
'screenshots' => [
1 => [
'src' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/screenshot-1.png?rev=2778231',
'caption' => '',
],
],
'tags' => [
'export' => 'export',
'import' => 'import',
'migration' => 'migration',
],
'versions' => [
'1.1.0' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.1.0.zip',
'1.6.0' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.6.0.zip',
...
'trunk' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.zip',
],
'business_model' => false,
'repository_url' => '',
'commercial_support_url' => '',
'donate_link' => '',
'banners' => [
'low' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/banner-772x250.png?rev=2778231',
'high' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/banner-1544x500.png?rev=2778231',
],
'preview_link' => '',
]
```
