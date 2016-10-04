<?php

namespace Zerifas\Supermodel\Test\Console;

use PHPUnit_Framework_TestCase;
use Zerifas\Supermodel\Console\Template;

class TemplateTest extends PHPUnit_Framework_TestCase
{
    const TEST_TEMPLATE_PATHNAME = '/tmp/test.template.php';

    protected $template;

    public static function setUpBeforeClass()
    {
        file_put_contents(self::TEST_TEMPLATE_PATHNAME, '<?= $key1, \': \', $key2 ?>');
    }

    public static function tearDownAfterClass()
    {
        unlink(self::TEST_TEMPLATE_PATHNAME);
    }

    public function setUp()
    {
        parent::setUp();
        Template::setPath('/tmp');
        $this->template = new Template('test');
    }

    public function testSetWithKeyValue()
    {
        $this->template->set('key1', 'value1');
        $this->template->set('key2', 'value2');

        ob_start();
        $this->template->render();
        $actual = trim(ob_get_clean());

        $this->assertEquals('value1: value2', $actual);
    }

    public function testSetWithArray()
    {
        $this->template->set([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        ob_start();
        $this->template->render();
        $actual = trim(ob_get_clean());

        $this->assertEquals('value1: value2', $actual);
    }

    public function testRenderWithArray()
    {
        $this->template->set([
            'key1' => 'WRONG',
            'key2' => 'WRONG',
        ]);

        ob_start();
        $this->template->render([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        $actual = trim(ob_get_clean());

        $this->assertEquals('value1: value2', $actual);
    }
}
