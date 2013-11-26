SitemapGenerator
================

Very simple  sitemap (offline) generator.

How does it work?
-----------------

Basically it receives an array or iterator (most of the time a cursor form your database) and a callback to format the URL.

```php
require __DIR__ . '/vendor/autoload.php';

use crodas\SitemapGenerator\SitemapGenerator;

$generator = new SitemapGenerator("https://corruptos.net/sitemap", __DIR__ . "/public_html/sitemap/");
$generator->limit(1000);

$generator = $generator->addMap(['foo', 'bar', 'xxx', 'yyy'], function($obj) {
    return new Multiple([
        '/1/' . $obj,
        '/2/' . $obj,
        '/3/' . $obj,
    ]);
}, 'foobar.xml');

$generator = $generator->addMap($databaseResult, function($obj) {
    return $obj->url;
}, 'foobar.xml');
```
