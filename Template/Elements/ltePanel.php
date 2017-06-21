<?php
namespace exface\AdminLteTemplate\Template\Elements;

class ltePanel extends lteContainer
{

    private $number_of_columns = null;

    private $searched_for_number_of_columns = false;

    function generateHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = <<<HTML

                                {$this->buildHtmlForChildren()}
                                <div class="{$this->getColumnWidthClasses()} {$this->getId()}_masonry_fitem" id="{$this->getId()}_sizer"></div>
HTML;
        
        if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countVisibleWidgets() > 1)) {
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
                            <div class="row">
                                {$children_html}
                            </div>
                        </div>
                    </div>
HTML;
        } else {
            $children_html = <<<HTML

                    <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                        {$children_html}
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
        $output = <<<JS

                {$this->buildJsLayouterFunction()}
                
                {$this->buildJsLayouter()};
                $("#{$this->getId()}").find(".{$this->getId()}_masonry_fitem").on("resize", function(event){
                    {$this->buildJsLayouter()};
                });
JS;
        
        return $output . $this->buildJsForChildren();
    }

    public function buildJsLayouter()
    {
        return $this->getId() . '_layouter()';
    }

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
     * Determines the number of columns of a widget, based on the width of widget, the number
     * of columns of the parent layout widget and the default number of columns of the widget.
     *
     * @return number
     */
    public function getNumberOfColumns()
    {
        if (! $this->searched_for_number_of_columns) {
            $widget = $this->getWidget();
            if (! is_null($widget->getNumberOfColumns())) {
                $this->number_of_columns = $widget->getNumberOfColumns();
            } elseif ($widget->getWidth()->isRelative() && !$widget->getWidth()->isMax()) {
                $width = $widget->getWidth()->getValue();
                if ($width < 1) {
                    $width = 1;
                }
                $this->number_of_columns = $width;
            } else {
                if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
                    $parentColumnNumber = $this->getTemplate()->getElement($layoutWidget)->getNumberOfColumns();
                }
                if (! is_null($parentColumnNumber)) {
                    $this->number_of_columns = $parentColumnNumber;
                } else {
                    $this->number_of_columns = $this->getTemplate()->getConfig()->getOption("WIDGET.PANEL.COLUMNS_BY_DEFAULT");
                }
            }
            $this->searched_for_number_of_columns = true;
        }
        return $this->number_of_columns;
    }
}
?>