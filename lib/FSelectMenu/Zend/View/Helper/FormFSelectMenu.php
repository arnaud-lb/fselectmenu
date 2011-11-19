<?php

use FSelectMenu\Renderer;
use FSelectMenu\Translator\ZendTranslator;

class FSelectMenu_Zend_View_Helper_FormFSelectMenu extends Zend_View_Helper_FormElement
{
    /**
     * Generates 'select' list of options.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are extracted in place of added parameters.
     *
     * @param mixed $value The option value to mark as 'selected'; if an
     * array, will mark all values in the array as 'selected' (used for
     * multiple-select elements).
     *
     * @param array|string $attribs Attributes added to the 'select' tag.
     *
     * @param array $options An array of key-value pairs where the array
     * key is the radio value, and the array value is the radio text.
     *
     * @param string $listsep When disabled, use this list separator string
     * between list values.
     *
     * @return string The select tag and options XHTML.
     */
    public function formFSelectMenu($name, $value = null, $attribs = null,
        $options = null, $listsep = "<br />\n")
    {
        $info = $this->_getInfo($name, $value, $attribs, $options, $listsep);
        extract($info); // name, id, value, attribs, options, listsep, disable

        $translator = $this->getTranslator();
        $translator = $translator ? new ZendTranslator($translator) : null;
        $renderer = new Renderer($this->view->getEncoding(), $translator);

        $fopts = array();

        // options applying to option elements
        if (isset($attribs['optionAttribs'])) {
            $fopts[Renderer::OPTION_ATTRS_OPT] = $attribs['optionAttribs'];
            unset($attribs['optionAttribs']);
        }

        // options applying to options' parent element
        if (isset($attribs['optionWrapperAttribs'])) {
            $fopts[Renderer::OPTION_ATTRS_OPT] = $attribs['optionWrapperAttribs'];
            unset($attribs['optionWrapperAttribs']);
        }

        if (isset($attribs['rawLabels']) && true === $attribs['rawLabels']) {
            $fopts[Renderer::RAW_LABELS_OPT] = true;
            unset($attribs['rawLabels']);
        }

        if (true === $disable) {
            $attribs['disabled'] = 'disabled';
        }

        $attribs['id'] = $id.'_html';

        $fopts[Renderer::NATIVE_ATTRS_OPT] = array(
            'id' => $id,
            'name' => $name,
        );

        if (isset($attribs['data-fixedlabel'])) {
            $fopts[Renderer::FIXED_LABEL_OPT] = $attribs['data-fixedlabel'];
            unset($attribs['data-fixedlabel']);
        }

        $fopts[Renderer::ATTRS_OPT] = $attribs;

        return $renderer->render($value, $options, $fopts);
    }
}
