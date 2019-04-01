<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;

class LteSplitVertical extends lteContainer
{
    function buildHtml()
    {
        $output = <<<HTML

                <div class="container {$this->buildCssElementClass()}" id="{$this->getId()}" style="width:100%;">
                    {$this->buildHtmlForWidgets()}
                </div>
HTML;
        
        return $output;
    }

    function buildHtmlForWidgets()
    {
        $panels = $this->getWidget()->getPanels();
        $panel_no = count($panels);
        if ($panel_no == 0) {
            throw new FacadeUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $this->getWidget()->getId() . ', at least one Panel is required.');
        }
        $panels_html = '';
        foreach ($panels as $panel) {
            
            /*
             * FIXME Make percentual vertical splits work
             * The trouble is, that it is hard to figure out, what 100% are. The current solution only
             * works if the split is the only element in the page and assumes 100% = a little less than 
             * the height of the viewport (because of the page header)
             * 
             * Another problem is making the contained widget (e.g. a chart or a data table) fill the entire
             * space. This is currently solved by passing the height to the child if there is only one
             * child. Although this will be often the case, it would be much more elegant to have a regular
             * way to make a child fill it's parent.
             */
            
            $dim = $panel->getHeight();
            if ($dim->isPercentual() && ! $this->getWidget()->hasParent()) {
                $height = 'calc(' . rtrim($dim->getValue(), "%") . 'vh - 41px)';
                $style = 'height: ' . $height . '; overflow: auto;';
            } else {
                $height = $dim->getValue();
            }
            if ($panel->countWidgets() === 1) {
                $widget = $panel->getWidgetFirst();
                $widget->setHeight($height);
                // FIXME for some reason the height in the $style does not work on tablet portrait mode - 
                // the second panel overlaps the first one. It's OK in landscape mode though.
                $style = '';
            }
            
            $panels_html .= <<<HTML

                    <div class="row" style="{$style}">
                        <div class="col-xs-12">
                            {$this->getFacade()->getElement($panel)->buildHtml()}
                        </div>
                    </div>
HTML;
        }
        return $panels_html;
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-' . $this->getWidget()->getWidgetType();
    }
}
