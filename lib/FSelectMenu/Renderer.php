<?php

namespace FSelectMenu;

use Symfony\Component\Form\Extension\Core\View\ChoiceView;

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
            'disabledValues' => array(),
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

        if (count($options['disabledValues']) > 0) {
            $options['disabledValues'] = array_combine($options['disabledValues'], $options['disabledValues']);
        }

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
                , $value
                , $options['disabledValues']);

        if (null !== $options['fixedLabel']) {
            $label = $options['fixedLabel'];
        } else {
            $label = $this->getSelectedLabel(
                $options['emptyLabel']
                , $options['preferredChoices']
                , $choices, $value);
        }
        
        if ($label instanceof ChoiceView) {
            $label = $this->translator->trans($label->getLabel());
        } else {
            $label = $this->translator->trans($label);
        }

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
            $html[] = $this->buildChoices(array('' => $options['emptyLabel'])
                , $value
                , $options['rawLabels'], $options['optionAttrs']
                , $options['disabledValues']);
        }

        if (count($options['preferredChoices']) > 0) {
            $html[] = $this->buildChoices($options['preferredChoices'], $value
                    , $options['rawLabels'], $options['optionAttrs']
                    , $options['disabledValues']);
            if (null !== $options['separator']) {
                $html[] = '<span class="fselectmenu-option fselectmenu-disabled fselectmenu-separator">' . ($options['rawLabels'] ? $options['separator'] : $this->escape($options['separator'])) . '</span>';
            }
        }

        $html[] = $this->buildChoices($choices, $value
                , $options['rawLabels'], $options['optionAttrs']
                , $options['disabledValues']);

        $html[] = '</span></span></span>';

        return \implode('', $html);
    }

    private function buildChoices(array $choices, $value, $rawLabels, array $optionAttrs, array $disabledValues)
    {
        $html = array();

        foreach ($choices as $choiceValue => $choiceLabel) {

            if (\is_array($choiceLabel)) {
                $html[] = '<span class="fselectmenu-optgroup">';
                $html[] = '<span class="fselectmenu-optgroup-title">';
                $html[] = $this->escape($this->translator->trans($choiceValue));
                $html[] = '</span>';
                $html[] = $this->buildChoices($choiceLabel, $value, $rawLabels, $optionAttrs, $disabledValues);
                $html[] = '</span>';
                continue;
            }

            if ($choiceLabel instanceof ChoiceView) {
                $choiceLabel = $this->translator->trans($choiceLabel->getLabel());
            } else {
                $choiceLabel = $this->translator->trans($choiceLabel);
            }

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
                    . ($value === (string) $choiceValue ? " fselectmenu-selected" : '')
                    . (isset($disabledValues[$choiceValue]) ? " fselectmenu-disabled" : '')
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
        foreach ($choices as $choiceValue => $choiceLabel) {
            if (\is_array($choiceLabel)) {
                foreach ($choiceLabel as $cValue => $cLabel) {
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

    private function buildNativeElement(array $attrs, $emptyLabel, array $preferredChoices, array $choices, $separator, $value, array $disabledValues)
    {
        $html = array();

        $html[] = '<select';
        foreach($attrs as $attrName => $attrValue) {
            $html[] = ' '.$this->escape($attrName)
                    .'="'.$this->escape($attrValue).'"';
        }
        $html[] = '>';

        if (null !== $emptyLabel) {
            $html[] = $this->buildNativeChoices(array('' => $emptyLabel), $value, $disabledValues);
        }

        if (count($preferredChoices) > 0) {
            $html[] = $this->buildNativeChoices($preferredChoices, $value, $disabledValues);
            if (null !== $separator) {
                $html[] = '<option value="" disabled="disabled">' . $this->escape($separator) . '</option>';
            }
        }

        $html[] = $this->buildNativeChoices($choices, $value, $disabledValues);
        $html[] = '</select>';

        return \implode('', $html);
    }

    private function buildNativeChoices(array $choices, $value, array $disabledValues)
    {
        $html = array();

        foreach($choices as $optValue => $optLabel) {

            if (\is_array($optLabel)) {
                $title = $this->translator->trans($optValue);
                $html[] = '<optgroup title="'.$this->escape($title).'">';
                $html[] = $this->buildNativeChoices($optLabel, $value, $disabledValues);
                $html[] = '</optgroup>';
                continue;
            }
            
            if ($optLabel instanceof ChoiceView) {
                $label = $this->translator->trans($optLabel->getLabel());
            } else {
                $label = $this->translator->trans($optLabel);
            }
            
            $selected = $value === (string) $optValue;
            $disabled = isset($disabledValues[$optValue]);

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
}
