<?php

namespace FSelectMenu\Translator;

class ArrayTranslator
{
    private $messages;

    public function __construct(array $messages = array())
    {
        $this->messages = $messages;
    }

    public function trans($id)
    {
        if (isset($this->messages[$id])) {
            return $this->messages[$id];
        }
        return $id;
    }
}

