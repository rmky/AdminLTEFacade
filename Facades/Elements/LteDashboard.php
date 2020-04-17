<?php
namespace exface\AdminLTEFacade\Facades\Elements;

class LteDashboard extends LteWidgetGrid
{
    protected function buildCssWidthDefaultValue() : string
    {
        return '100%';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteAbstractElement::buildCssWidthClasses()
     */
    public function buildCssWidthClasses()
    {
        $width = $this->getWidget()->getWidth();
        if ($width->isUndefined() === true) {
            return 'col-xs-12';
        }
        return parent::buildCssWidthClasses();
    }
}