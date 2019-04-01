<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlProgressBarTrait;

/**
 *
 * @method ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteProgressBar extends lteDisplay
{
    use HtmlProgressBarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteDisplay::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlProgressBar($this->getWidget()->getValueWithDefaults());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteValue::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
}
?>