<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;

class lteForm extends ltePanel
{
    use JqueryToolbarsTrait;

    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = <<<HTML

                        {$this->buildHtmlForWidgets()}
                        <div class="{$this->getColumnWidthClasses()} {$this->buildCssLayoutItemClass()}" id="{$this->getId()}_sizer"></div>
HTML;
        
        if ($widget->countWidgetsVisible() > 1 && $this->getMinChildWidthRelative() > 1) {
            // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
            $children_html = <<<HTML

                    <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                        {$children_html}
                    </div>
HTML;
        }
        
        if ($widget->getCaption()) {
            $header = <<<HTML
<div class="ftitle">{$this->getWidget()->getCaption()}</div>
HTML;
        }
        
        $output = <<<HTML

                {$header}
                <form class="form" id="{$widget->getId()}">
                    {$children_html}
                </form>
HTML;
        
        return $output;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($value)
    {
        $this->method = $value;
    }

    function buildHtmlButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getFacade()->buildHtml($btn);
        }
        
        return $output;
    }

    function buildJsButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getFacade()->buildJs($btn);
        }
        
        return $output;
    }
}
?>