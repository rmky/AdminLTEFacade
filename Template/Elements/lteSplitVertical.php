<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class lteSplitVertical extends lteContainer
{

    function buildHtml()
    {
        $output = <<<HTML

                <div class="container" id="{$this->getId()}" style="width:100%;">
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
            throw new TemplateUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $this->getWidget()->getId() . ', at least one Panel is required.');
        }
        $panels_html = '';
        foreach ($panels as $panel) {
            $panels_html .= <<<HTML

                    <div class="row">
                        <div class="col-xs-12">
                            {$this->getTemplate()->getElement($panel)->buildHtml()}
                        </div>
                    </div>
HTML;
        }
        return $panels_html;
    }
}
