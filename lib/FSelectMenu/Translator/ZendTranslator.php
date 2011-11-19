<?php

namespace FSelectMenu\Translator;

class ZendTranslator
{
    private $zt;

    /**
     * @param Zend_Translate|Zend_Translate_Adapter $zt
     */
    public function __construct($zt)
    {
        $this->zt = $zt;
    }

    public function trans($id)
    {
        return $this->zt->translate($id);
    }
}

