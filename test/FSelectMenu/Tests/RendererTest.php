<?php

namespace FSelectMenu\Tests;

use FSelectMenu\Renderer;
use FSelectMenu\Translator\ArrayTranslator;

class RendererTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getTestRenderData */
    public function testRender($value, $choices, $options, $expect, $translator = null)
    {
        $renderer = new Renderer('utf-8', $translator);
        $result = $renderer->render($value, $choices, $options);

        $expect = str_replace('><', ">\n<", $expect);
        $result = str_replace('><', ">\n<", $result);

        $this->assertSame($expect, $result);
    }

    public function getTestRenderData()
    {
        $out = function($attrs = null, $in = '') {
            if (null === $attrs) {
                $attrs = ' class="fselectmenu-style-default fselectmenu fselectmenu-events" tabindex="0"';
            }
            return '<span'.$attrs.'>' . $in . '</span>';
        };
        $native = function($attrs = null, $in = '') {
            if (null === $attrs) {
                $attrs = ' class=" fselectmenu-native"';
            }
            return '<select'.$attrs.'>'.$in.'</select>';
        };
        $label = function($label = '') {
            if ('' === $label) {
                $label = "\xC2\xA0";
            }
            return '<span class="fselectmenu-label-wrapper"><span class="fselectmenu-label">'.$label.'</span><span class="fselectmenu-icon"></span></span>';
        };
        $opts = function($attrs = null, $opts = '') {
            if (null === $attrs) {
                $attrs = ' class=" fselectmenu-options-wrapper fselectmenu-events"';
            }
            return '<span'.$attrs.'><span class="fselectmenu-options">' . $opts . '</span></span>';
        };

        return array(
            
            // Empty
            array('', array(), array(),
                $out(null, $native() . $label() . $opts()),
            ),

            // A few choices
            array(
                'x',
                array(
                    'a' => 'A',
                    'x' => 'X',
                    'z' => 'Z',
                ), array(),
                $out(null, $native(null,
                    '<option value="a">A</option>'
                    .'<option selected="selected" value="x">X</option>'
                    .'<option value="z">Z</option>'
                ) . $label('X') . $opts(null,
                    '<span data-value="a" data-label="A" class="fselectmenu-option">A</span>'
                    .'<span data-value="x" data-label="X" class="fselectmenu-option fselectmenu-selected">X</span>'
                    .'<span data-value="z" data-label="Z" class="fselectmenu-option">Z</span>'
                )),
            ),

            // With optgroup
            array(
                'x',
                array(
                    'a' => array(
                        'b' => 'B',
                        'x' => 'X',
                        'z' => 'Z',
                    ),
                ), array(),
                $out(null, $native(null,
                    '<optgroup title="a">'
                    .'<option value="b">B</option>'
                    .'<option selected="selected" value="x">X</option>'
                    .'<option value="z">Z</option>'
                    .'</optgroup>'
                ) . $label('X') . $opts(null,
                    '<span class="fselectmenu-optgroup">'
                    .'<span class="fselectmenu-optgroup-title">a</span>'
                    .'<span data-value="b" data-label="B" class="fselectmenu-option">B</span>'
                    .'<span data-value="x" data-label="X" class="fselectmenu-option fselectmenu-selected">X</span>'
                    .'<span data-value="z" data-label="Z" class="fselectmenu-option">Z</span>'
                    .'</span>'
                )),
            ),

            // A few choices, value not in choices
            // The label should be the first choice
            array(
                '',
                array(
                    'a' => 'A',
                    'x' => 'X',
                ), array(),
                $out(null, $native(null,
                    '<option value="a">A</option>'
                    .'<option value="x">X</option>'
                ) . $label('A') . $opts(null,
                    '<span data-value="a" data-label="A" class="fselectmenu-option">A</span>'
                    .'<span data-value="x" data-label="X" class="fselectmenu-option">X</span>'
                )),
            ),

            // A few choices with optgroup, value not in choices
            // The label should be the first choice
            array(
                '',
                array(
                    'a' => array(
                        'b' => 'B',
                        'x' => 'X',
                        'z' => 'Z',
                    ),
                ), array(),
                $out(null, $native(null,
                    '<optgroup title="a">'
                    .'<option value="b">B</option>'
                    .'<option value="x">X</option>'
                    .'<option value="z">Z</option>'
                    .'</optgroup>'
                ) . $label('B') . $opts(null,
                    '<span class="fselectmenu-optgroup">'
                    .'<span class="fselectmenu-optgroup-title">a</span>'
                    .'<span data-value="b" data-label="B" class="fselectmenu-option">B</span>'
                    .'<span data-value="x" data-label="X" class="fselectmenu-option">X</span>'
                    .'<span data-value="z" data-label="Z" class="fselectmenu-option">Z</span>'
                    .'</span>'
                )),
            ),

            // A few choices, value is empty
            array(
                '',
                array(
                    'a' => 'A',
                    '' => 'X',
                    'z' => 'Z',
                ), array(),
                $out(null, $native(null,
                    '<option value="a">A</option>'
                    .'<option selected="selected" value="">X</option>'
                    .'<option value="z">Z</option>'
                ) . $label('X') . $opts(null,
                    '<span data-value="a" data-label="A" class="fselectmenu-option">A</span>'
                    .'<span data-value="" data-label="X" class="fselectmenu-option fselectmenu-selected">X</span>'
                    .'<span data-value="z" data-label="Z" class="fselectmenu-option">Z</span>'
                )),
            ),

            // Custom class
            array('', array(), array(
                    'attrs' => array(
                        'class' => 'fselectmenu-style-foo',
                    ),
                ),
                $out(' class="fselectmenu-style-foo fselectmenu fselectmenu-events" tabindex="0"', $native() . $label() . $opts()),
            ),

            // Custom tabindex
            array('', array(), array(
                    'attrs' => array(
                        'tabindex' => '42',
                    ),
                ),
                $out(' class="fselectmenu-style-default fselectmenu fselectmenu-events" tabindex="42"', $native() . $label() . $opts()),
            ),

            // Options wrapper attrs
            array('', array(), array(
                    'optionWrapperAttrs' => array(
                        'class' => 'foo',
                    ),
                ),
                $out(null, $native() . $label() . $opts(' class="foo fselectmenu-options-wrapper fselectmenu-events"')),
            ),

            // Escape
            array(
                '<a>',
                array(
                    '<o>' => array(
                        '<a>' => '<A>',
                    ),
                ), array(),
                $out(null, $native(null,
                    '<optgroup title="&lt;o&gt;">'
                    .'<option selected="selected" value="&lt;a&gt;">&lt;A&gt;</option>'
                    .'</optgroup>'
                ) . $label('&lt;A&gt;') . $opts(null,
                    '<span class="fselectmenu-optgroup">'
                    .'<span class="fselectmenu-optgroup-title">&lt;o&gt;</span>'
                    .'<span data-value="&lt;a&gt;" data-label="&amp;lt;A&amp;gt;" class="fselectmenu-option fselectmenu-selected">&lt;A&gt;</span>'
                    .'</span>'
                )),
            ),

            // Raw labels
            array(
                'x',
                array(
                    'a' => '<A>',
                ), array(
                    'rawLabels' => true,
                ),
                $out(null, $native(null,
                    '<option value="a">&lt;A&gt;</option>'
                ) . $label('<A>') . $opts(null,
                    '<span data-value="a" data-label="&lt;A&gt;" class="fselectmenu-option"><A></span>'
                )),
            ),

            // Fixed label
            array(
                '',
                array(
                    'a' => 'A',
                ), array(
                    'fixedLabel' => 'foo',
                ),
                $out(' class="fselectmenu-style-default fselectmenu fselectmenu-events" tabindex="0" data-fixedlabel="foo"', $native(null,
                    '<option value="a">A</option>'
                ) . $label('foo') . $opts(null,
                    '<span data-value="a" data-label="A" class="fselectmenu-option">A</span>'
                )),
            ),

            // Option attribs
            array(
                'x',
                array(
                    'a' => 'A',
                    'x' => 'X',
                    'z' => 'Z',
                ), array(
                    'optionAttrs' => array(
                        'a' => array('foo' => 'bar'),
                        'z' => array('bar' => 'foo'),
                    ),
                ),
                $out(null, $native(null,
                    '<option value="a">A</option>'
                    .'<option selected="selected" value="x">X</option>'
                    .'<option value="z">Z</option>'
                ) . $label('X') . $opts(null,
                    '<span foo="bar" data-value="a" data-label="A" class="fselectmenu-option">A</span>'
                    .'<span data-value="x" data-label="X" class="fselectmenu-option fselectmenu-selected">X</span>'
                    .'<span bar="foo" data-value="z" data-label="Z" class="fselectmenu-option">Z</span>'
                )),
            ),

            // Translation
            array(
                'x',
                array(
                    'a' => array(
                        'b' => 'B',
                        'x' => 'X',
                        'z' => 'Z',
                    ),
                ), array(),
                $out(null, $native(null,
                    '<optgroup title="tr_a">'
                    .'<option value="b">tr_B</option>'
                    .'<option selected="selected" value="x">tr_X</option>'
                    .'<option value="z">tr_Z</option>'
                    .'</optgroup>'
                ) . $label('tr_X') . $opts(null,
                    '<span class="fselectmenu-optgroup">'
                    .'<span class="fselectmenu-optgroup-title">tr_a</span>'
                    .'<span data-value="b" data-label="tr_B" class="fselectmenu-option">tr_B</span>'
                    .'<span data-value="x" data-label="tr_X" class="fselectmenu-option fselectmenu-selected">tr_X</span>'
                    .'<span data-value="z" data-label="tr_Z" class="fselectmenu-option">tr_Z</span>'
                    .'</span>'
                )),
                new ArrayTranslator(array(
                    'a' => 'tr_a',
                    'B' => 'tr_B',
                    'X' => 'tr_X',
                    'Z' => 'tr_Z',
                )),
            ),

        );
    }
}

