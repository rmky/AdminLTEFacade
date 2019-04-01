<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\Value;
use exface\Core\Widgets\DataColumn;

/**
 *
 * @method Value getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class LteValue extends lteAbstractElement
{

    function buildHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        $html = nl2br($widget->getText());
        
        if (! trim($html) && $this->getWidget()->getEmptyText()) {
            $html = $this->getWidget()->getEmptyText();
        }
        
        $output .= '<div id="' . $this->getId() . '" class="exf-value" title="' . $this->buildHintText() . '">' . $html . '</div>';
        return $this->buildHtmlGridItemWrapper($output);
    }

    /**
     * Creates a DIV element that will be treated as a masonry grid item and wraps 
     * it around the given HTML code.
     * 
     * @param string $inner_html
     * @return string
     */
    public function buildHtmlGridItemWrapper($inner_html)
    {
        if (! $this->isInTable()) {
            $cssClasses = "exf-grid-item {$this->getMasonryItemClass()}  {$this->getWidthClasses()} {$this->buildCssVisibilityClass()}";
        }
        
        $output = <<<HTML
        
                    <div class="exf-input {$cssClasses}" title="{$this->buildHintText()}">
                        {$inner_html}
                    </div>
HTML;
                        
        return $output;
    }
    
    protected function isInTable()
    {
        return $this->getWidget()->getParent() instanceof DataColumn;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    function buildJs()
    {
        return '';
    }
    
    protected function buildHtmlLabel()
    {
        if (! empty($this->getCaption()) && ! $this->getWidget()->isInTable()) {
            return '<label for="' . $this->getId() . '" class="exf-text-label">' . $this->getWidget()->getCaption() . '</label>';
        }
        return '';
    }
}
?>