<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutTrait;

class ltePanel extends lteContainer implements JqueryLayoutInterface
{
    
    use JqueryLayoutTrait;

    /**
     * The HTML for a Panel is either a div or a box depending on where the panel is located.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateHtml()
     */
    public function generateHtml()
    {
        $widget = $this->getWidget();
        
        if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $children_html = $this->buildHtmlChildrenWrapperBox($this->buildHtmlForChildren());   
        } elseif ($widget->countWidgetsVisible() > 1) {
            // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
            $children_html = $this->buildHtmlChildrenWrapperPlain($this->buildHtmlForChildren());
        }
        
        return $this->buildHtmlWrapper($children_html);
    }
    
    /**
     * Wraps the given HTML in a div with the id of this element and classes required to work within layouts.
     * 
     * @param string $contents_html
     * @return string
     */
    protected function buildHtmlWrapper($contents_html)
    {
        return <<<HTML
        
                <div id="{$this->getId()}" class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
                    {$contents_html}
                </div>
HTML;
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
     * Wraps the given HTML in a simple DIV container in contrast to a box in buildHtmlChildrenWrapperBox().
     * 
     * @param string $contents_html
     * @return string
     */
    protected function buildHtmlChildrenWrapperPlain($contents_html)
    {
        return <<<HTML

                    <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                        {$contents_html}
                        {$this->buildHtmlLayoutSizer()}
                    </div>

HTML;
    }
    
    /**
     * Returns a DIV, that can be used as a dynamic sizer in the masonry grid layout
     * 
     * @return string
     */
    protected function buildHtmlLayoutSizer()
    {
        return <<<HTML
<div class="{$this->getColumnWidthClasses()} {$this->buildCssLayoutItemClass()}" id="{$this->getId()}_sizer"></div>                        
HTML;
    }

    /**
     * The JavaScript for a panel contains the layouter code and the JS for the panels children.
     * 
     * The layouter (masonry in this case) positions html elements with the class defined by
     * buildCssLayoutItemClass(). It is called when loading the page, when the panel is resized
     * and when one of the layout items is resized.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    public function generateJs()
    {
        // Mit dem ResizeSensor kann ein onResize-Event fuer ein <div> abgefangen werden.
        $output = <<<JS

                {$this->buildJsLayouterFunction()}
                new ResizeSensor(document.getElementById("{$this->getId()}"), function() {
                    {$this->buildJsLayouter()};
                });
                $('.{$this->buildCssLayoutItemClass()}').on('resize', function(){ {$this->buildJsLayouter()} });
                {$this->buildJsLayouter()};
JS;
        
        return $output . $this->buildJsForChildren();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateHeaders()
     */
    function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<script src="exface/vendor/npm-asset/css-element-queries/src/ResizeSensor.js"></script>';
        return $headers;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutInterface::buildJsLayouterFunction()
     */
    public function buildJsLayouterFunction()
    {
        $widget = $this->getWidget();
        
        // Auch das Layout des Containers wird erneuert nachdem das eigene Layout aktualisiert
        // wurde.
        $layoutWidgetScript = '';
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $layoutWidgetScript = <<<JS
{$this->getTemplate()->getElement($layoutWidget)->buildJsLayouter()};
JS;
        }
        
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {
        if (!$("#{$this->getId()}_masonry_grid").data("masonry")) {
            if ($("#{$this->getId()}_masonry_grid").find(".{$this->buildCssLayoutItemClass()}").length > 0) {
                $("#{$this->getId()}_masonry_grid").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->buildCssLayoutItemClass()}"
                });
            }
        } else {
            $("#{$this->getId()}_masonry_grid").masonry("reloadItems");
            $("#{$this->getId()}_masonry_grid").masonry();
        }
        {$layoutWidgetScript}
    }
JS;
        
        return $output;
    }
    
    /**
     * Returns CSS class for layout items within this panel
     * @return string
     */
    protected function buildCssLayoutItemClass()
    {
        return $this->getId() . '_masonry_fitem';
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.PANEL.COLUMNS_BY_DEFAULT");
    }

    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     *
     * @return boolean
     */
    public function inheritsColumnNumber()
    {
        return true;
    }
}
?>