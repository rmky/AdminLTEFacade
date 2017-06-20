<?php
namespace exface\AdminLteTemplate\Template\Elements;

class ltePanel extends lteContainer
{

    function generateHtml()
    {
        $children_html = <<<HTML

                                {$this->buildHtmlForChildren()}
                                <div class="{$this->getColumnWidthClasses()} {$this->getId()}_masonry_fitem" id="{$this->getId()}_sizer"></div>
HTML;
        
        if ($this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) {
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
                            {$children_html}
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
}
?>