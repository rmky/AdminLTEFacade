<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonGroupTrait;

/**
 * The AdminLTE implementation of the ButtonGroup widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class lteButtonGroup extends lteAbstractElement
{
    use JqueryButtonGroupTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlButtonGroupWrapper($this->buildHtmlButtons());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $this->buildJsForButtons();
    }
}
?>