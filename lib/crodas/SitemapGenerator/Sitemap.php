<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace crodas\SitemapGenerator;

use Iterator;
use Closure;
use ArrayIterator;

class Sitemap implements Iterator
{
    protected $cursor;
    protected $step;

    protected $perPage;
    protected $url;
    protected $processed = 0;

    public function __construct($cursor, Closure $step)
    {
        if (is_array($cursor)) {
            $cursor = new ArrayIterator($cursor);
        }
        if (!$cursor instanceof Iterator) {
            throw new \Exception("\$cusor must implement Iterator");
        }
        if ($cursor instanceof \MongoCursor) {
            if (!$cursor->info()['started_iterating']) {
                /* start iterating */
                $cursor->getNext();
            }
        }
        $this->cursor = $cursor;
        $this->step   = $step;
    }

    public function multipleFiles($url, $perPage)
    {
        if (strpos($url, '%d') === false) {
            throw new \InvalidArgumentException("$url must have at least one %d");
        }
        $this->perPage = ((int)$perPage) ?: 1;
        $this->url     = $url;
        return $this;
    }

    protected function cleanUp(&$xml)
    {
        $xml = preg_replace('/>\W+</', '><', $xml);
    }

    public function generate($file, $base = '')
    {
        if ($this->perPage) {
            $page = 1;
            do {
                $xml = Templates::get('sitemap')->render([
                    'urls' => $this
                ], true);
                $pdir  = dirname($file);
                $pfile = $pdir . "/" . sprintf(basename($this->url), $page);
                $this->cleanUp($xml);
                \crodas\File::write($pfile, $xml);
                $page++;
            } while ($this->hasMore());
            $pages = array();
            for($i=1; $i < $page; $i++) {
                $pages[] = sprintf($this->url, $i);
            }
            $xml = Templates::get('index')->render(compact('pages'), true);
            $this->cleanUp($xml);
            \crodas\File::write($file, $xml);
        } else {
            $xml = Templates::get('sitemap')->render([
                'urls' => $this
            ], true);
            $this->cleanUp($xml);
            \crodas\File::write($file, $xml);
        }
    }


    public function setHost($host)
    {
        if (!parse_url($host, PHP_URL_SCHEME)) {
            throw new \RuntimeException("$host seems invalid");
        }
        if (!parse_url($host, PHP_URL_HOST)) {
            throw new \RuntimeException("$host seems invalid");
        }
        $this->host = $host;
        return $this;
    }

    public function current()
    {
        if (empty($this->current)) {
            $step    = $this->step;
            $current = $step($this->cursor->current());
            if (empty($current)) {
                /** skip **/
                return false;
            }
            if ($current instanceof Multiple) {
                $this->queue = (array)$current;
                $current = array_shift($this->queue);
            }
        } else {
            $current = $this->current;
        }
        if (!parse_url($current, PHP_URL_SCHEME)) {
            $current = $this->host . $current;
        }
        return (object)['url' => $current, 'lastmod' => 0, 'changefreq' => ''];
    }


    public function rewind()
    {
        if ($this->processed == 0) {
            $this->cursor->rewind();
        }
        // do nothing otherwise
        $this->processed = 0;
    }

    public function key()
    {
        if (is_array($this->cursor)) {
            return key($this->cursor);
        }
        return $this->cursor->key();
    }

    public function hasMore()
    {
        if (!empty($this->current) || !empty($this->queue)) {
            return true;
        }
        return $this->cursor->valid();
    }


    public function valid()
    {
        if ($this->perPage && $this->processed>0 && $this->processed % $this->perPage == 0) {
            return false;
        }

        return $this->hasMore();
    }

    public function next()
    {
        $this->current = NULL;
        $this->processed++;
        if (!empty($this->queue)) {
            $this->current = array_shift($this->queue);
            return;
        }
        $this->cursor->next();
    }

}
