<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteTextHeading extends lteText
{

    function generateHtml()
    {
        $output = '';
        $output .= '<h' . $this->getWidget()->getHeadingLevel() . ' id="' . $this->getId() . '">' . $this->getWidget()->getText() . '</h' . $this->getWidget()->getHeadingLevel() . '>';
        return $this->buildHtmlWrapper($output);
    }
}
?>