<?php

class SimpleTest extends \phpunit_framework_testcase
{
    public static function provider()
    {
        $args = [];
        foreach (glob(__DIR__ . '/fixture/*.php') as $file) {
            $args[] = [$file];
        }
        @mkdir(__DIR__ . '/tmp/');
        return $args;
    }

    /** @dataProvider provider */
    public function testAll($path)
    {
        $dir = __DIR__ . '/tmp/' . substr(basename($path), 0, -4);
        @mkdir($dir);
        require $path;

        $fixtures = glob(substr($path, 0, -4) . "/*");
        $generated = glob($dir  . "/*");
        $this->assertEquals(count($fixtures), count($generated));

        foreach ($fixtures as $file) {
            $base = substr($file, strlen($path)-4);
            $this->assertTrue(is_file($dir . '/' . $base));

            $xml = preg_replace('/>\W+</', '><', file_Get_contents($file));
            $xml = str_replace('%date%', date('c'), $xml);
            $this->assertEquals($xml, file_get_contents($dir . '/'. $base));
        }
    }

}
