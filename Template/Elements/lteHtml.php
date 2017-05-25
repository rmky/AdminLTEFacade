<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteHtml extends lteText
{

    function init()
    {}

    function generateHtml()
    {
        $output = '';
        if ($this->getWidget()->getCss()) {
            $output .= '<style>' . $this->getWidget()->getCss() . '</style>';
        }
        if ($this->getWidget()->getCaption() && ! $this->getWidget()->getHideCaption()) {
            $output .= '<label for="' . $this->getId() . '">' . $this->getWidget()->getCaption() . '</label>';
        }
        
        $output .= '<div id="' . $this->getId() . '">' . $this->getWidget()->getHtml() . '</div>';
        return $this->buildHtmlWrapper($output);
    }

    function generateJs()
    {
        return $this->getWidget()->getJavascript();
    }
}
?>