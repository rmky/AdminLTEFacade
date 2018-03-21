<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class lteSplitHorizontal extends lteSplitVertical
{

    function buildHtmlForWidgets()
    {
        $panels = $this->getWidget()->getPanels();
        $panel_no = count($panels);
        if ($panel_no == 0) {
            throw new TemplateUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $this->getWidget()->getId() . ', at least one Panel is required.');
        } elseif ($panel_no <= 12) {
            $col_width = floor(12 / $panel_no);
            $col_rest = 12 % $panel_no;
        } else {
            $col_width = 1;
            $col_rest = 0;
        }
        
        $panels_html = '';
        foreach ($panels as $panel) {
            $panels_html .= <<<HTML

                            <div class="col-xs-12 col-md-{$col_width}">
                                {$this->getTemplate()->getElement($panel)->buildHtml()}
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
