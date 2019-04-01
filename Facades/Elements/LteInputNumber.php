<?php
namespace exface\AdminLTEFacade\Facades\Elements;

class LteInputNumber extends lteInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('number');
    }
}