<?php

namespace FSelectMenu;

use FSelectMenu\Translator\ArrayTranslator;

class Renderer
{
    const ATTRS_OPT = 'attrs';
    const NATIVE_ATTRS_OPT = 'nativeAttrs';
    const OPTION_ATTRS_OPT = 'optionAttrs';
    const OPTION_WRAPPER_ATTRS_OPT = 'optionWrapperAttrs';
    const RAW_LABELS_OPT = 'rawLabels';
    const FIXED_LABEL_OPT = 'fixedLabel';

    private $charset;
    private $translator;

    public function __construct($charset = 'utf-8', $translator = null)
    {
        $this->charset = $charset;

        if (null === $translator) {
            $translator = new ArrayTranslator;
        }
        $this->translator = $translator;
    }

    /**
     * Renders a select menu
     *
     * $choices is an array of select menu items. Each item is an array with
     * the following elements:
     *
     *  - label:    label
     *  - value:    value
     *  - attrs:    optional list of html attributes
     *  - choices:  nested array of select menu items. If present, this item
     *              is treated as an option group.
     *
     * @param string $value     The selected value
     * @param array  $choices   Choices
     * @param array  $options   Options
     */
    public function render2($value, array $choices, array $options = array())
    {
        $value = (string) $value;

        // options

        $options = \array_replace_recursive(array(
            // fselectmenu element attributes
            'attrs' => array(
                'class' => 'fselectmenu-style-default',
                'tabindex' => '0',
            ),
            // native select element attributes
            'nativeAttrs' => array(
                'class' => '',
            ),
            // options wrapper element attributes
            'optionWrapperAttrs' => array(
                'class' => '',
            ),
            // whether to escape labels
            'rawLabels' => false,
            // always display this label
            'fixedLabel' => null,
            'emptyLabel' => null,
            'preferredChoices' => array(),
            'separator' => '-------------------',
        ), $options);

        $options['attrs']['class'] .= " fselectmenu fselectmenu-events"
                                . ' ' . $this->valueClass($value);

        $options['nativeAttrs']['class'] .= " fselectmenu-native";

        if (!empty($options['nativeAttrs']['disabled'])) {
            $options['attrs']['class'] .= " fselectmenu-disabled";
        }

        $options['optionWrapperAttrs']['class']
                .= " fselectmenu-options-wrapper fselectmenu-events"
                . ' ' . $this->valueClass($value);

        $choices = $this->fixupChoiceList($choices);
        $options['preferredChoices'] = $this->fixupChoiceList($options['preferredChoices']);

        // build the fselectmenu element

        $html = array();

        if (null !== $options['fixedLabel']) {
            $options['attrs']['data-fixedlabel'] = $options['fixedLabel'];
        }

        $html[] = '<span';
        foreach($options['attrs'] as $attrName => $attrValue) {
            $html[] = ' '.$this->escape($attrName)
                .'="'.$this->escape($attrValue).'"';
        }
        $html[] = '>';
        $html[] = $this->buildNativeElement($options['nativeAttrs']
                , $options['emptyLabel'], $options['preferredChoices']
                , $choices
                , $options['separator']
                , $value);

        if (null !== $options['fixedLabel']) {
            $label = $options['fixedLabel'];
        } else {
            $label = $this->getSelectedLabel(
                $options['emptyLabel']
                , $options['preferredChoices']
                , $choices, $value);
        }
        $label = $this->translator->trans($label);

        // fixes rendering issues when the label is empty
        $label = $label ?: "\xC2\xA0"; // &nbsp;

        $html[] = '<span class="fselectmenu-label-wrapper">';
        $html[] = '<span class="fselectmenu-label">';
        $html[] = $options['rawLabels'] ? $label : $this->escape($label);
        $html[] = '</span>';
        $html[] = '<span class="fselectmenu-icon"></span>';
        $html[] = '</span>';

        $html[] = "<span";
        foreach($options['optionWrapperAttrs'] as $attrName => $attrValue) {
            $html[] = ' '.$this->escape($attrName)
                    .'="'.$this->escape($attrValue).'"';
        }
        $html[] = '><span class="fselectmenu-options">';

        if (null !== $options['emptyLabel']) {

            $list = array(
                array(
                    'value' => '',
                    'label' => $options['emptyLabel'],
                ),
            );
            $list = $this->fixupChoiceList($list);

            $html[] = $this->buildChoices($list
                , $value
                , $options['rawLabels']);
        }

        if (count($options['preferredChoices']) > 0) {
            $html[] = $this->buildChoices($options['preferredChoices'], $value
                    , $options['rawLabels']);
            if (null !== $options['separator']) {
                $html[] = '<span class="fselectmenu-option fselectmenu-disabled fselectmenu-separator">' . ($options['rawLabels'] ? $options['separator'] : $this->escape($options['separator'])) . '</span>';
            }
        }

        $html[] = $this->buildChoices($choices, $value
                , $options['rawLabels']);

        $html[] = '</span></span></span>';

        return \implode('', $html);
    }

    /**
     * Renders a select menu (compatibility wrapper; deprecated)
     *
     * $choices is an associated array in which keys are selectmenu items
     * values, and values are items labels.
     *
     * @deprecated
     * @param string $value     The selected value
     * @param array  $choices   Choices
     * @param array  $options   Options
     */
    public function render($value, array $choices, array $options = array())
    {
        $value = (string) $value;

        $options = \array_replace_recursive(array(
            // individual options attribs
            'optionAttrs' => array(),
            'disabledValues' => array(),
            'preferredChoices' => array(),
        ), $options);


        $choices = $this->makeChoiceList(
            $choices
            , $options['optionAttrs']
            , $options['disabledValues']
        );

        $options['preferredChoices'] = $this->makeChoiceList(
            $options['preferredChoices']
            , $options['optionAttrs']
            , $options['disabledValues']
        );

        unset($options['optionAttrs']);
        unset($options['disabledValues']);

        return $this->render2($value, $choices, $options);
    }

    private function buildChoices(array $choices, $value, $rawLabels)
    {
        $html = array();

        foreach ($choices as $choice) {

            if (isset($choice['choices'])) {
                $html[] = '<span class="fselectmenu-optgroup">';
                $html[] = '<span class="fselectmenu-optgroup-title">';
                $html[] = $this->escape($this->translator->trans($choice['label']));
                $html[] = '</span>';
                $html[] = $this->buildChoices($choice['choices'], $value, $rawLabels);
                $html[] = '</span>';
                continue;
            }

            $choiceLabel = $choice['label'];
            $choiceLabel = $this->translator->trans($choiceLabel);

            $choiceValue = $choice['value'];

            $disabled = $choice['disabled'];

            $attrs = $choice['attrs'];
            $attrs += array(
                'data-value' => $choiceValue,
                'data-label' => $rawLabels
                    ? $choiceLabel
                    : $this->escape($choiceLabel),
                'class' => "fselectmenu-option"
                    . ($value === (string) $choiceValue ? " fselectmenu-selected" : '')
                    . ($disabled ? " fselectmenu-disabled" : '')
                    .' '.$this->valueClass($choiceValue),
            );

            $opt = '<span';
            foreach ($attrs as $attrName => $attrValue) {
                    $opt .= ' '.$this->escape($attrName)
                        . '="'.$this->escape($attrValue).'"';
            }
            $opt .= '>';
            $opt .= $rawLabels ? $choiceLabel : $this->escape($choiceLabel);
            $opt .= '</span>';

            $html[] = $opt;
        }

        return \implode('', $html);
    }

    private function getSelectedLabel($emptyLabel, $preferredChoices, $choices, $value)
    {
        if (null !== $emptyLabel && '' === (string) $value) {
            return $emptyLabel;
        }

        $label = $this->getSelectedLabelFromChoices($preferredChoices, $value, null, $found);

        if (!$found) {
            $label = $this->getSelectedLabelFromChoices($choices, $value, $label);
        }

        return $label;
    }

    private function getSelectedLabelFromChoices($choices, $value, $selectedLabel = null, &$found = false)
    {
        foreach ($choices as $choice) {
            if (isset($choice['choices'])) {
                foreach ($choice['choices'] as $cChoice) {
                    $cLabel = $cChoice['label'];
                    $cValue = $cChoice['value'];
                    if (null === $selectedLabel) {
                        $selectedLabel = $cLabel;
                    }
                    if ($value === (string) $cValue) {
                        $selectedLabel = $cLabel;
                        $found = true;
                        break 2;
                    }
                }
            } else {

                $choiceValue = $choice['value'];
                $choiceLabel = $choice['label'];

                if (null === $selectedLabel) {
                    $selectedLabel = $choiceLabel;
                }
                if ($value === (string) $choiceValue) {
                    $selectedLabel = $choiceLabel;
                    $found = true;
                    break;
                }
            }
        }

        return $selectedLabel;
    }

    private function buildNativeElement(array $attrs, $emptyLabel, array $preferredChoices, array $choices, $separator, $value)
    {
        $html = array();

        $html[] = '<select';
        foreach($attrs as $attrName => $attrValue) {
            $html[] = ' '.$this->escape($attrName)
                    .'="'.$this->escape($attrValue).'"';
        }
        $html[] = '>';

        if (null !== $emptyLabel) {

            $list = array(
                array(
                    'value' => '',
                    'label' => $emptyLabel,
                ),
            );
            $list = $this->fixupChoiceList($list);

            $html[] = $this->buildNativeChoices($list, $value);
        }

        if (count($preferredChoices) > 0) {
            $html[] = $this->buildNativeChoices($preferredChoices, $value);
            if (null !== $separator) {
                $html[] = '<option value="" disabled="disabled">' . $this->escape($separator) . '</option>';
            }
        }

        $html[] = $this->buildNativeChoices($choices, $value);
        $html[] = '</select>';

        return \implode('', $html);
    }

    private function buildNativeChoices(array $choices, $value)
    {
        $html = array();

        foreach($choices as $choice) {

            if (isset($choice['choices'])) {
                $title = $this->translator->trans($choice['label']);
                $html[] = '<optgroup title="'.$this->escape($title).'">';
                $html[] = $this->buildNativeChoices($choice['choices'], $value);
                $html[] = '</optgroup>';
                continue;
            }

            $optLabel = $choice['label'];
            $optValue = $choice['value'];

            $label = $this->translator->trans($optLabel);

            $selected = $value === (string) $optValue;
            $disabled = $choice['disabled'];

            $html[] .= '<option'
                    .($selected ? ' selected="selected"' : '')
                    .($disabled ? ' disabled="disabled"' : '')
                    .' value="'.$this->escape($optValue).'"'
                    .' class="'.$this->escape($this->valueClass($optValue)).'"'
                    .'>'.$this->escape($label).'</option>';
        }

        return \implode('', $html);
    }

    // http://mathiasbynens.be/notes/html5-id-class
    private function valueClass($value)
    {
        return 'fselectmenu-value-' . \preg_replace('#\s+#', '-', $value);
    }

    private function escape($str)
    {
        return \htmlspecialchars($str, \ENT_QUOTES, $this->charset);
    }

    /**
     * Converts an array of choices
     *
     * Converts from the format expected by render() to the format expected
     * by render2()
     */
    private function makeChoiceList(array $choices, array $optionAttrs, array $disabledValues)
    {
        $list = array();
        $disabledValues = array_combine($disabledValues, $disabledValues);

        foreach ($choices as $value => $label) {
            if (is_array($label)) {
                $list[] = array(
                    'choices' => $this->makeChoiceList($label, $optionAttrs, $disabledValues),
                    'label' => $value,
                );
            } else {
                $item = array(
                    'value' => $value,
                    'label' => $label,
                );
                if (isset($optionAttrs[$value])) {
                    $item['attrs'] = $optionAttrs[$value];
                }
                if (isset($disabledValues[$value])) {
                    $item['disabled'] = $disabledValues[$value];
                }
                $list[] = $item;
            }
        }

        return $list;
    }

    private function fixupChoiceList(array $origList)
    {
        $list = array();

        $defaults = array(
            'choices' => null,
            'label' => null,
            'value' => null,
            'attrs' => array(),
            'disabled' => false,
        );

        foreach ($origList as $item) {

            $item = $item + $defaults;

            if (is_array($item['choices'])) {
                $item['choices'] = $this->fixupChoiceList($item['choices']);
            }

            $list[] = $item;
        }

        return $list;
    }
}
