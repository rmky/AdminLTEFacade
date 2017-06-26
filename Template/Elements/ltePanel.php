<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\AbstractAjaxTemplate\Template\Elements\JqueryLayoutInterface;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryLayoutTrait;

class ltePanel extends lteContainer implements JqueryLayoutInterface
{
    
    use JqueryLayoutTrait;

    function generateHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = <<<HTML

                                {$this->buildHtmlForChildren()}
                                <div class="{$this->getColumnWidthClasses()} {$this->getId()}_masonry_fitem" id="{$this->getId()}_sizer"></div>
HTML;
        
        if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            if ($this->getWidget()->getCaption()) {
                $header = <<<HTML

                        <div class="box-header">
                            <h3 class="box-title">{$this->getWidget()->getCaption()}</h3>
                        </div>
HTML;
            }
            
            $children_html = <<<HTML

                    <div class="box">
                        {$header}
                        <div class="box-body grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                            <!--div class="row"-->
                                {$children_html}
                            <!--/div-->
                        </div>
                    </div>
HTML;
        } else {
            $children_html = <<<HTML

                    <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                        <!--div class="row"-->
                            {$children_html}
                        <!--/div-->
                    </div>
HTML;
        }
        
        $output = <<<HTML

                <div id="{$this->getId()}" class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
                    {$children_html}
                </div>
HTML;
        
        return $output;
    }

    function generateJs()
    {
        // Mit dem ResizeSensor kann ein onResize-Event fuer ein <div> abgefangen werden.
        $output = <<<JS

                {$this->buildJsLayouterFunction()}
                new ResizeSensor(document.getElementById("{$this->getId()}"), function() {
                    {$this->buildJsLayouter()};
                });
JS;
        
        return $output . $this->buildJsForChildren();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::generateHeaders()
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
     * @see \exface\AbstractAjaxTemplate\Template\Elements\JqueryLayoutInterface::buildJsLayouterFunction()
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

    function {$this->getId()}_layouter() {
        if (!$("#{$this->getId()}_masonry_grid").data("masonry")) {
            if ($("#{$this->getId()}_masonry_grid").find(".{$this->getId()}_masonry_fitem").length > 0) {
                $("#{$this->getId()}_masonry_grid").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->getId()}_masonry_fitem"
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