<?php

namespace FSelectMenu\Bundle\Twig;

use FSelectMenu\Twig\Extension as BaseExtension;

class Extension extends BaseExtension
{
    public function getFunctions()
    {
        return array(
            'fselectmenu_convert_choice_views' => new \Twig_Function_Method($this, 'fselectmenuConvertChoiceViewsFunction'),
        ) + parent::getFunctions();
    }

    public function fselectmenuConvertChoiceViewsFunction(array $choiceViews)
    {
        $choices = array();

        foreach ($choiceViews as $choiceView) {
            $choices[] = array(
                'value' => $choiceView->getValue(),
                'label' => $choiceView->getLabel(),
            );
        }

        return $choices;
    }
}

