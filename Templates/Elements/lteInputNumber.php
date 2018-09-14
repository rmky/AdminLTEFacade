<?php
namespace exface\AdminLteTemplate\Templates\Elements;

class lteInputNumber extends lteInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('number');
    }
}