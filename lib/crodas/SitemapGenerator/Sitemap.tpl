<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach ($urls as $url)
    @if (!empty($url))
    <url>
        <loc>{{$url->url}}</loc>
        @if ($url->lastmod)
            <lastmod>{{$url->lastmod}}</lastmod>
        @end
        @if ($url->changefreq)
            <changefreq>{{$url->changefreq}}</changefreq>
        @end
        <priority>0.8</priority>
    </url>
    @end
@end
</urlset>
