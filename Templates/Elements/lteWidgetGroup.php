<?php
namespace exface\AdminLteTemplate\Templates\Elements;

class lteWidgetGroup extends ltePanel
{
    protected function buildHtmlCaption()
    {
        $caption = $this->getWidget()->getCaption();
        return $caption ? '<h3 class="page-header" style="font-size: 18px;">' . $caption . '</h3>' : '';
    }
}
?>
