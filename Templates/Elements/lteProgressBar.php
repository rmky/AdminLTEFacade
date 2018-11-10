<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlProgressBarTrait;

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
     * @see \exface\AdminLteTemplate\Templates\Elements\lteDisplay::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlProgressBar($this->getValueWithDefaults());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteValue::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
}
?>