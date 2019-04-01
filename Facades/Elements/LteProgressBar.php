<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlProgressBarTrait;

/**
 *
 * @method ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class LteProgressBar extends lteDisplay
{
    use HtmlProgressBarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteDisplay::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlProgressBar($this->getWidget()->getValueWithDefaults());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteValue::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
}
?>