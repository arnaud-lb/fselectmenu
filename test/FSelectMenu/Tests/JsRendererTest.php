<?php
namespace FSelectMenu\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\ProcessBuilder;

use FSelectMenu\Renderer;
use FSelectMenu\Translator\ArrayTranslator;

class JsRendererTest extends \PHPUnit_Framework_TestCase
{
    private $javascriptRendererCode = <<<'EOF'
var fs = require('fs');
var vm = require('vm');

var rendererValue = "%s";
var rendererChoices = %s;
var rendererOptions = %s;
var rendererFileContent = fs.readFileSync("%s", 'utf8');

var sandbox = {
    console: console,
    
    renderer: null,
    
    rendererValue: rendererValue,
    rendererChoices: rendererChoices,
    rendererOptions: rendererOptions,
};

// window
sandbox.window = sandbox;

// create a context for the vm using the sandbox data
var context = vm.createContext(sandbox);

// load fake requirejs
vm.runInContext('define = function(func) { renderer = func(); };', context, 'requirejs.js');

// load renderer code
vm.runInContext(rendererFileContent, context, 'renderer.js');

//run renderer.render()
vm.runInContext('console.log(renderer.render(rendererValue, rendererChoices, rendererOptions));', context, 'test.js');
EOF;
    
    /** 
     * @dataProvider provideRenderTestData 
     */
    public function testRender($value, $choices, $options, $expect, $translator = null)
    {
        if (empty($_SERVER['__TESTENV_NODE_JS_BIN'])) {
            $this->markTestSkipped('The test required node js bin to be configured in phpunit.xml');
        }
        
        $executeFile = tempnam(sys_get_temp_dir(), 'fselectmenu-test');
        file_put_contents($executeFile, sprintf($this->javascriptRendererCode,
            $value,
            json_encode($choices),
            json_encode($options),
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
        
        $this->assertXmlStringEqualsXmlString($expect, $result);
    }

    public function provideRenderTestData()
    {
        $data = array_values(Yaml::parse(__DIR__ . '/render-test-data.yaml'));
        foreach ($data as &$case) {
            $case['output'] = trim($case['output']);
            if ($case['translator']) {
                $case['translator'] = new ArrayTranslator($case['translator']);
            }
        }

        return $data;
    }
}