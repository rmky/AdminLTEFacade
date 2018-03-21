<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\Value;

/**
 *
 * @method Value getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteValue extends lteAbstractElement
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
        $output = <<<HTML
        
                    <div class="exf-input exf-grid-item {$this->getMasonryItemClass()} {$this->getWidthClasses()} {$this->buildCssVisibilityClass()}" title="{$this->buildHintText()}">
                        {$inner_html}
                    </div>
HTML;
                        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    function buildJs()
    {
        return '';
    }
    
    protected function buildHtmlLabel()
    {
        if ($this->getWidget()->getCaption() && ! $this->getWidget()->getHideCaption()) {
            return '<label for="' . $this->getId() . '" class="exf-text-label">' . $this->getWidget()->getCaption() . '</label>';
        }
        return '';
    }
}
?>