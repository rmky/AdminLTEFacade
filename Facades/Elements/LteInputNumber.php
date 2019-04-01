<?php
namespace exface\AdminLteFacade\Facades\Elements;

class lteInputNumber extends lteInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('number');
    }
}