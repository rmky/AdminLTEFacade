<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryFilterTrait;

class lteFilter extends lteAbstractElement
{
    use JqueryFilterTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->getInputElement()->buildHtml();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->getInputElement()->buildJs();
    } 
}
?>