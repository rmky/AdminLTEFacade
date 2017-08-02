<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\AbstractAjaxTemplate\Template\Elements\JqueryToolbarsTrait;

class lteForm extends ltePanel
{
    use JqueryToolbarsTrait;

    public function generateHtml()
    {
        $output = '';
        if ($this->getWidget()->getCaption()) {
            $output = '<div class="ftitle">' . $this->getWidget()->getCaption() . '</div>';
        }
        
        $output .= '<form class="form" id="' . $this->getWidget()->getId() . '">';
        $output .= $this->buildHtmlForWidgets();
        $output .= '<div class="' . $this->getColumnWidthClasses() . ' ' . $this->getId() . '_masonry_fitem" id="' . $this->getId() . '_sizer" style=""></div>';
        $output .= '</form>';
        
        return $output;
    }

    public function generateJs()
    {
        // FIXME had to override the generate_js() method of lteContainer here, because masonry broke the form for some reason. But masonry
        // layouts are important for forms, so this needs to be fixed. Remove this method from lteForm when done.
        return $this->buildJsForChildren();
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($value)
    {
        $this->method = $value;
    }

    function buildHtmlButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getTemplate()->generateHtml($btn);
        }
        
        return $output;
    }

    function buildJsButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getTemplate()->generateJs($btn);
        }
        
        return $output;
    }
}
?>