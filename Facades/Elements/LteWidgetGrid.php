<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLayoutTrait;

class LteWidgetGrid extends lteContainer
{
    use JqueryLayoutTrait;

    /**
     * The HTML for a Panel is either a div or a box depending on where the panel is located.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        $children_html = $this->buildHtmlChildrenWrapperGrid($this->buildHtmlForChildren());
        return $this->buildHtmlGridItemWrapper($children_html);
    }
    
    /**
     * Wraps the given HTML in a div with the id of this element and classes required to work within layouts.
     * 
     * @param string $contents_html
     * @return string
     */
    protected function buildHtmlGridItemWrapper($contents_html)
    {
        return <<<HTML
        
                <div id="{$this->getId()}" class="exf-grid-item {$this->getMasonryItemClass()} {$this->buildCssWidthClasses()} {$this->buildCssElementClass()}">
                    {$contents_html}
                </div>
HTML;
    }
    
    /**
     * Wraps the given HTML in a simple DIV container in contrast to a box in buildHtmlChildrenWrapperBox().
     * 
     * @param string $contents_html
     * @return string
     */
    protected function buildHtmlChildrenWrapperGrid($contents_html)
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<script src="' . $this->getFacade()->buildUrlToSource('LIBS.RESIZESENSOR.JS') . '"></script>';
        return $headers;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLayoutTrait::buildJsLayouter()
     */
    public function buildJsLayouter() : string
    {
        return $this->buildJsFunctionPrefix() . 'layouter()';
    }

    protected function buildJsLayouterFunction() : string
    {
        $widget = $this->getWidget();
        
        // Auch das Layout des Containers wird erneuert nachdem das eigene Layout aktualisiert
        // wurde.
        $layoutWidgetScript = '';
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $layoutWidgetScript = <<<JS
{$this->getFacade()->getElement($layoutWidget)->buildJsLayouter()};
JS;
        }
        
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {
        if (!$("#{$this->getId()}_masonry_grid").data("masonry")) {
            if ($("#{$this->getId()}_masonry_grid").find(".{$this->buildCssLayoutItemClass()}").length > 0) {
                $("#{$this->getId()}_masonry_grid").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->buildCssLayoutItemClass()}"
                }).on('layoutComplete', function() {var el = $("#{$this->getId()}_masonry_grid"); if(el.height() === 1){el.css('height', 'initial');}});
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
        return $this->getId() . '_masonry_exf-grid-item';
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.PANEL.COLUMNS_BY_DEFAULT");
    }

    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     *
     * @return boolean
     */
    public function inheritsNumberOfColumns() : bool
    {
        return true;
    }
    
    protected function getMinChildWidthRelative()
    {
        $minChildWidthValue = 1;
        foreach ($this->getWidget()->getChildren() as $child) {
            $childWidth = $child->getWidth();
            if ($childWidth->isRelative() && ! $childWidth->isMax()) {
                if ($childWidth->getValue() < $minChildWidthValue) {
                    $minChildWidthValue = $childWidth->getValue();
                }
            }
        }
        return $minChildWidthValue;
    }
}
?>