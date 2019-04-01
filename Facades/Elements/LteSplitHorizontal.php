<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;

class LteSplitHorizontal extends lteSplitVertical
{

    function buildHtmlForWidgets()
    {
        $widget = $this->getWidget();
        $height = $widget->getHeight();
        
        $panels = $widget->getPanels();
        $panel_no = count($panels);
        if ($panel_no == 0) {
            throw new FacadeUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $widget->getId() . ', at least one Panel is required.');
        } elseif ($panel_no <= 12) {
            $col_width = floor(12 / $panel_no);
            $col_rest = 12 % $panel_no;
        } else {
            $col_width = 1;
            $col_rest = 0;
        }
        
        $panels_html = '';
        foreach ($panels as $panel) {
            $style = '';
            if (! $height->isUndefined()) {
                $style .= 'height: ' . $this->getHeight();
                $panel->setHeight($height);
            }
            $panels_html .= <<<HTML

                            <div class="col-xs-12 col-md-{$col_width}" style="{$style}">
                                {$this->getFacade()->getElement($panel)->buildHtml()}
                            </div>
HTML;
        }
        if ($col_rest != 0) {
            $panels_html .= <<<HTML

                            <div class="hidden-xs col-md-{$col_rest}"></div>
HTML;
        }
        $panels_html = <<<HTML
                        <div class="row">
                            {$panels_html}
						</div>
HTML;
        return $panels_html;
    }
}
