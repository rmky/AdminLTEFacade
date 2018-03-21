<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsValueDecoratingInterface;
use exface\Core\Widgets\Display;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDisplayTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsBooleanFormatter;

/**
 *
 * @method Display getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteDisplay extends lteValue implements JsValueDecoratingInterface
{
    use JqueryDisplayTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteValue::buildHtml()
     */
    public function buildHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        $html = nl2br($widget->getValue());
        
        if (! trim($html) && $this->getWidget()->getEmptyText()) {
            $html = $this->getWidget()->getEmptyText();
        }
        
        $output = <<<HTML
        
            {$this->buildHtmlLabel()}
            <div id="{$this->getId()}" class="exf-display">{$html}</div>
            
HTML;
            
        return $this->buildHtmlGridItemWrapper($output);
    }
}
?>