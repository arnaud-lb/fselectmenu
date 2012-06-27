<?php
namespace FSelectMenu\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\ProcessBuilder;

use FSelectMenu\Renderer;

class JsRendererTest extends \PHPUnit_Framework_TestCase
{
    /** 
     * @dataProvider provideRenderTestData 
     */
    public function testRender($value, $choices, $options, $expect, $translations)
    {
        if (empty($_SERVER['__TESTENV_NODE_JS_BIN'])) {
            $this->markTestSkipped('The test required node js bin to be configured in phpunit.xml');
        }
        
        $executeFile = tempnam(sys_get_temp_dir(), 'fselectmenu-test');
        file_put_contents($executeFile, sprintf(file_get_contents(__DIR__.'/files/renderer-sandbox.js'),
            $value,
            json_encode($choices),
            json_encode($options),
            json_encode($translations),
            realpath(__DIR__ . '/../../../lib/FSelectMenu/Bundle/Resources/public/js/modules/renderer.js')
        ));
        
        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb
            ->add($_SERVER['__TESTENV_NODE_JS_BIN'])
            ->add($executeFile)
        ;

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($executeFile);
        
        $this->assertEquals(0, $code, 
            "The node js execution was not successful: Code: {$proc->getExitCode()} - {$proc->getExitCodeText()}\n\n".
            "Error: {$proc->getErrorOutput()}\n\n"
        );

        $result = str_replace('><', ">\n<", $proc->getOutput());
        
        $encoding = '<?xml version="1.0" encoding="utf-8"?>';
        $this->assertXmlStringEqualsXmlString($encoding.$expect, $encoding.$result);
    }

    public function provideRenderTestData()
    {
        $data = array_values(Yaml::parse(__DIR__ . '/files/render-test-data.yaml'));
        foreach ($data as &$case) {
            $case['output'] = trim($case['output']);
            $case['translator'] = $case['translator'] ?: array();
        }

        return $data;
    }
}