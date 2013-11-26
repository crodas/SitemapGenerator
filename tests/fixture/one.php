<?php

use crodas\SitemapGenerator\SitemapGenerator;
use crodas\SitemapGenerator\Multiple;

$generator = new SitemapGenerator("https://demo.com/sitemap", $dir);

$generator = $generator->addMap(['foo', 'bar', 'xxx', 'yyy'], function($obj) {
    if ($obj == 'bar') {
        return '/bar';
    }
    return new Multiple([
        '/1/' . $obj,
        '/2/' . $obj,
        '/3/' . $obj,
    ]);
}, 'foobar.xml');
