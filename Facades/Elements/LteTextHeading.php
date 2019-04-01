<?php
namespace exface\AdminLteFacade\Facades\Elements;

class lteTextHeading extends lteText
{

    function buildHtml()
    {
        $output = '';
        $output .= '<h' . $this->getWidget()->getHeadingLevel() . ' id="' . $this->getId() . '">' . $this->getWidget()->getText() . '</h' . $this->getWidget()->getHeadingLevel() . '>';
        return $this->buildHtmlGridItemWrapper($output);
    }
}
?>