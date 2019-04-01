<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Interfaces\JsValueDecoratingInterface;
use exface\Core\Widgets\Display;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisplayTrait;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsBooleanFormatter;

/**
 *
 * @method Display getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class LteDisplay extends lteValue implements JsValueDecoratingInterface
{
    use JqueryDisplayTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteValue::buildHtml()
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