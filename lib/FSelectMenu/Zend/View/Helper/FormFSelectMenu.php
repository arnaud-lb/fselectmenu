<?php
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

        $namespace = 'fselectmenu';
        if (isset($attribs['namespace'])) {
            $namespace = $attribs['namespace'];
            unset($attribs['namespace']);
        } 

        // options applying to option elements
        if (isset($attribs['optionAttribs'])) {
            $optionAttribs = $attribs['optionAttribs'];
            unset($attribs['optionAttribs']);
        } else {
            $optionAttribs = array();
        }

        // options applying to options' parent element
        if (isset($attribs['optionWrapperAttribs'])) {
            $optionWrapperAttribs = $attribs['optionWrapperAttribs'];
        } else {
            $optionWrapperAttribs = array();
        }
        if (!isset($optionWrapperAttribs['class'])) {
            $optionWrapperAttribs['class'] = '';
        }
        $optionWrapperAttribs['class'] .= sprintf(' %1$s-options-wrapper %1$s-events', $namespace);

        if (isset($attribs['rawLabels']) && true === $attribs['rawLabels']) {
            $rawLabels = true;
            unset($attribs['rawLabels']);
        } else {
            $rawLabels = false;
        }

        // force $value to array so we can compare multiple values to multiple
        // options; also ensure it's a string for comparison purposes.
        $value = array_map('strval', (array) $value);

        // check if element may have multiple values
        $multiple = false;

        if (substr($name, -2) == '[]') {
            // multiple implied by the name
            $multiple = true;
        }

        if (isset($attribs['multiple'])) {
            // Attribute set
            if ($attribs['multiple']) {
                // True attribute; set multiple attribute
                $multiple = true;

                // Make sure name indicates multiple values are allowed
                if (!empty($multiple) && (substr($name, -2) != '[]')) {
                    $name .= '[]';
                }
            } else {
                // False attribute; ensure attribute not set
                $multiple = false;
            }
            unset($attribs['multiple']);
        }

        if (!isset($attribs['class'])) {
            $attribs['class'] = '';
        }

        $attribs['class'] .= sprintf(' %1$s %1$s-events', $namespace);

        if ($multiple) {
            $attribs['class'] .= sprintf(' %1$s-multiple', $namespace);
        }

        // now start building the XHTML.
        if (true === $disable) {
            $attribs['class'] .= sprintf(' %1$s-disabled', $namespace);
        }

        $selectedLabels = array();
        $nativeSelect = sprintf(
            '<select name="%2$s" id="%3$s" class="%1$s-native" %4$s>'
            , $namespace
            , $this->view->escape($name)
            , $this->view->escape($id)
            , $disable ? ' disabled="disabled"' : ''
        );
        $selected = array_flip($value);

        foreach($options as $opt_value => $opt_label) {
            if (is_array($opt_label)) {
                foreach($opt_label as $val => $lab) {
                    $isSelected = false;
                    if (isset($selected[$val])) {
                        $selectedLabels[$val] = $lab;
                        $isSelected = true;
                    }
                    $nativeSelect .= '<option' . ($isSelected ? 'selected="selected"' : '') . ' value="'.$this->view->escape($val).'">'.$this->view->escape($lab).'</option>';
                }
            } else {
                $isSelected = false;
                if (isset($selected[$opt_value])) {
                    $selectedLabels[$opt_value] = $opt_label;
                    $isSelected = true;
                }
                $nativeSelect .= '<option' . ($isSelected ? ' selected="selected"' : '') . ' value="'.$this->view->escape($opt_value).'">'.$this->view->escape($opt_label).'</option>';
            }
        }

        $nativeSelect .= '</select>';

        if (empty($selectedLabels)) {
            foreach($options as $opt_value => $opt_label) {
                if (is_array($opt_label)) {
                    foreach($opt_label as $val => $lab) {
                        $selectedLabels[$val] = $lab;
                        break;
                    }
                } else {
                    $selectedLabels[$opt_value] = $opt_label;
                }
                break;
            }
        }

        if (!isset($attribs['tabindex'])) {
            $attribs['tabindex'] = '0';
        }

        // Build the surrounding element.
        $xhtml = '<span '
                . ' id="' . $this->view->escape($id) . '_html"'
                . $this->_htmlAttribs($attribs)
                . ">\n    "
                . $nativeSelect;

        if (isset($attribs['data-fixedlabel'])) {
            $label = $attribs['data-fixedlabel'];
        } else {
            $label = implode(', ', $selectedLabels);
        }
        $label = $label ?: "\xC2\xA0"; // nbsp; fixes rendering issues when the label is empty

        $xhtml .= sprintf('<span class="%1$s-label-wrapper"><span class="%1$s-label">%2$s</span><span class="%1$s-icon"></span></span>', $namespace,  $rawLabels ? $label : $this->view->escape($label));
        $xhtml .= sprintf('<span %2$s><span class="%1$s-options">', $namespace, $this->_htmlAttribs($optionWrapperAttribs));

        // build the list of options
        $list       = array();
        $translator = $this->getTranslator();
        foreach ((array) $options as $opt_value => $opt_label) {
            if (is_array($opt_label)) {
                $opt_disable = '';
                if (is_array($disable) && in_array($opt_value, $disable)) {
                    $opt_disable = 'disabled';
                }
                if (null !== $translator) {
                    $opt_value = $translator->translate($opt_value);
                }
                $list[] = sprintf('<span class="%1$s-optgroup %2$s">%3$s', $namespace, $opt_disable, $this->view->escape($opt_value));
                foreach ($opt_label as $val => $lab) {
                    $list[] = $this->_build($val, $lab, $value, $disable, $optionAttribs, $rawLabels, $namespace);
                }
                $list[] .= '</span>';
            } else {
                $list[] = $this->_build($opt_value, $opt_label, $value, $disable, $optionAttribs, $rawLabels, $namespace);
            }
        }

        // add the options to the xhtml and close the select
        $xhtml .= implode("\n    ", $list) . "\n</span></span></span>";

        return $xhtml;
    }

    /**
     * Builds the options' markup
     *
     * @param string $value Options Value
     * @param string $label Options Label
     * @param array  $selected The option value(s) to mark as 'selected'
     * @param array|bool $disable Whether the select is disabled, or individual options are
     * @return string Option Tag XHTML
     */
    protected function _build($value, $label, $selected, $disable, $optionAttribs, $rawLabels, $namespace)
    {
        if (is_bool($disable)) {
            $disable = array();
        }

        $opt = '<span'
             . ' data-value="' . $this->view->escape($value) . '" data-label="' . $this->view->escape($rawLabels ? $label : $this->view->escape($label)) . '"'
             ;

        if (isset($optionAttribs[$value])) {
            $attribs = $optionAttribs[$value];
        }
        if (!isset($attribs['class'])) {
            $attribs['class'] = '';
        }
        $attribs['class'] .= ' %1$s-option';

        if (isset($selected[$value])) {
            $attribs['class'] .= ' %1$s-selected';
        }

        // disabled?
        if (in_array($value, $disable)) {
            $attribs['class'] .= ' %1$s-disabled';
        }

        $attribs['class'] = sprintf($attribs['class'], $namespace);

        $opt .= $this->_htmlAttribs($attribs);

        $opt .= '>' . ($rawLabels ? $label : $this->view->escape($label)) . "</span>";

        return $opt;
    }
}
