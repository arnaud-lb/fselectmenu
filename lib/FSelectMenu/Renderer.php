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

    public function render($value, array $choices, array $options = array())
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
            // individual options attribs
            'optionAttrs' => array(),
            // options wrapper element attributes
            'optionWrapperAttrs' => array(
                'class' => '',
            ),
            // whether to escape labels
            'rawLabels' => false,
            // always display this label
            'fixedLabel' => null,
        ), $options);

        $options['attrs']['class'] .= " fselectmenu fselectmenu-events";

        $options['nativeAttrs']['class'] .= " fselectmenu-native";

        if (!empty($options['nativeAttrs']['disabled'])) {
            $options['attrs']['class'] .= " fselectmenu-disabled";
        }

        $options['optionWrapperAttrs']['class']
                .= " fselectmenu-options-wrapper fselectmenu-events";

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
                , $choices, $value);

        if (null !== $options['fixedLabel']) {
            $label = $options['fixedLabel'];
        } else {
            $label = $this->getSelectedLabel($choices, $value);
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

        $html[] = $this->buildChoices($choices, $value
                , $options['rawLabels'], $options['optionAttrs']);

        $html[] = '</span></span></span>';

        return \implode('', $html);
    }

    private function buildChoices(array $choices, $value, $rawLabels, array $optionAttrs)
    {
        $html = array();

        foreach ($choices as $choiceValue => $choiceLabel) {

            if (\is_array($choiceLabel)) {
                $html[] = '<span class="fselectmenu-optgroup">';
                $html[] = '<span class="fselectmenu-optgroup-title">';
                $html[] = $this->escape($this->translator->trans($choiceValue));
                $html[] = '</span>';
                $html[] = $this->buildChoices($choiceLabel, $value, $rawLabels, $optionAttrs);
                $html[] = '</span>';
                continue;
            }

            $choiceLabel = $this->translator->trans($choiceLabel);

            if (isset($optionAttrs[$choiceValue])) {
                $attrs = $optionAttrs[$choiceValue];
            } else {
                $attrs = array();
            }

            $attrs += array(
                'data-value' => $choiceValue,
                'data-label' => $rawLabels
                    ? $choiceLabel
                    : $this->escape($choiceLabel),
                'class' => "fselectmenu-option"
                    . ($value === $choiceValue ? " fselectmenu-selected" : ''),
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

    private function getSelectedLabel($choices, $value)
    {
        $selectedLabel = null;

        foreach ($choices as $choiceValue => $choiceLabel) {
            if (\is_array($choiceLabel)) {
                foreach ($choiceLabel as $cValue => $cLabel) {
                    if (null === $selectedLabel) {
                        $selectedLabel = $cLabel;
                    }
                    if ($value === $cValue) {
                        $selectedLabel = $cLabel;
                        break 2;
                    }
                }
            } else {
                if (null === $selectedLabel) {
                    $selectedLabel = $choiceLabel;
                }
                if ($value === $choiceValue) {
                    $selectedLabel = $choiceLabel;
                    break;
                }
            }
        }

        return $selectedLabel;
    }

    private function buildNativeElement($attrs, $choices, $value)
    {
        $html = array();

        $html[] = '<select';
        foreach($attrs as $attrName => $attrValue) {
            $html[] = ' '.$this->escape($attrName)
                    .'="'.$this->escape($attrValue).'"';
        }
        $html[] = '>';
        $html[] = $this->buildNativeChoices($choices, $value);
        $html[] = '</select>';

        return \implode('', $html);
    }

    private function buildNativeChoices($choices, $value)
    {
        $html = array();

        foreach($choices as $optValue => $optLabel) {

            if (\is_array($optLabel)) {
                $title = $this->translator->trans($optValue);
                $html[] = '<optgroup title="'.$this->escape($title).'">';
                $html[] = $this->buildNativeChoices($optLabel, $value);
                $html[] = '</optgroup>';
                continue;
            }

            $label = $this->translator->trans($optLabel);

            $selected = $value === $optValue;
            $html[] .= '<option'
                    .($selected ? ' selected="selected"' : '')
                    .' value="'.$this->escape($optValue)
                    .'">'.$this->escape($label).'</option>';
        }

        return \implode('', $html);
    }

    private function escape($str)
    {
        return \htmlspecialchars($str, \ENT_QUOTES, $this->charset);
    }
}
