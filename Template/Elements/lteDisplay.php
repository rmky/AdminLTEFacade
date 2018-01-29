<?php
namespace exface\AdminLteTemplate\Template\Elements;

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
    use JqueryDisplayTrait {
        getFormatter as getFormatterViaTrait;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Template\Elements\lteValue::generateHtml()
     */
    public function generateHtml()
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