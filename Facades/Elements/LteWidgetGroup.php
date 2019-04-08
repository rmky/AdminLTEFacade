<?php
namespace exface\AdminLTEFacade\Facades\Elements;

class LteWidgetGroup extends ltePanel
{
    protected function buildHtmlCaption()
    {
        $caption = $this->getWidget()->getCaption();
        return $caption ? '<h3 class="page-header">' . $caption . '</h3>' : '';
    }
    
    public function buildCssElementClass()
    {
        return 'exf-widgetgroup';
    }
}
?>
