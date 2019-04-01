<?php
namespace exface\AdminLteFacade\Facades\Elements;

class ltePanel extends lteWidgetGrid
{
    /**
     * The HTML for a Panel is either a div or a box depending on where the panel is located.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $children_html = $this->buildHtmlChildrenWrapperBox($this->buildHtmlForChildren());   
        } elseif ($widget->countWidgetsVisible() > 1) {
            // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
            $children_html = $this->buildHtmlChildrenWrapperGrid($this->buildHtmlForChildren());
        }
        
        return $this->buildHtmlGridItemWrapper($children_html);
    }
    
    protected function buildHtmlCaption()
    {
        if ($this->getWidget()->getCaption()) {
            $header = <<<HTML
                        <div class="box-header">
                            <h3 class="box-title">{$this->getWidget()->getCaption()}</h3>
                        </div>
HTML;
        } else {
            $header = '';
        }
        
        return $header;
    }
    
    /**
     * Wraps the given HTML in a AdminLTE box widget with everything needed for layouting within it.
     *
     * @param string $contents_html
     * @return string
     */
    protected function buildHtmlChildrenWrapperBox($contents_html)
    {
        return <<<HTML
        
                   <div class="box">
                        {$this->buildHtmlCaption()}
                        <div class="box-body grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                            {$contents_html}
                            {$this->buildHtmlLayoutSizer()}
                        </div>
                    </div>
                    
HTML;
    }   
}