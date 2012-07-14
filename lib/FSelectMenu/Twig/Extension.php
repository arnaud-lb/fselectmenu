<?php

namespace FSelectMenu\Twig;

use FSelectMenu\Renderer;

class Extension extends \Twig_Extension
{
    private $renderer;
    private $translator;

    /**
     * @param FSelectMenu\Translator\ArrayTranslator-like $translator
     */
    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function getFunctions() {
        return array(
            'fselectmenu' => new \Twig_Function_Method($this, 'fselectmenuFunction', array('needs_environment' => true)),
            'fselectmenu2' => new \Twig_Function_Method($this, 'fselectmenu2Function', array('needs_environment' => true)),
        );
    }

    protected function getRenderer(\Twig_Environment $env)
    {
        if (null === $renderer = $this->renderer) {
            $renderer = $this->renderer = new Renderer($env->getCharset(), $this->translator);
        }

        return $renderer;
    }

    public function fselectmenuFunction($env, $value, array $choices, array $options)
    {
        $renderer = $this->getRenderer($env);
        return new \Twig_Markup($renderer->render($value, $choices, $options), $env->getCharset());
    }

    public function fselectmenu2Function($env, $value, array $choices, array $options)
    {
        $renderer = $this->getRenderer($env);
        return new \Twig_Markup($renderer->render2($value, $choices, $options), $env->getCharset());
    }

    public function getName()
    {
        return 'fselectmenu';
    }
}

function fselectmenu_value_class($value)
{
    return preg_replace('#[^\w.-]+#', '-', $value);
}

