<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonGroupTrait;

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
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlButtonGroupWrapper($this->buildHtmlButtons());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $this->buildJsForButtons();
    }
}
?>