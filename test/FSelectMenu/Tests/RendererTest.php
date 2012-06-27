<?php
namespace FSelectMenu\Tests;

use Symfony\Component\Yaml\Yaml;

use FSelectMenu\Renderer;
use FSelectMenu\Translator\ArrayTranslator;

class RendererTest extends \PHPUnit_Framework_TestCase
{
    /** 
     * @dataProvider provideRenderTestData 
     */
    public function testRender($value, $choices, $options, $expect, $translator = null)
    {
        $renderer = new Renderer('utf-8', $translator);
        $result = $renderer->render($value, $choices, $options);

        $result = str_replace('><', ">\n<", $result);

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