<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteInputHidden extends lteInput
{

    function generateHtml()
    {
        $output = '<input type="hidden"
								name="' . $this->getWidget()->getAttributeAlias() . '"
								value="' . $this->escapeString($this->getValueWithDefaults()) . '"
								id="' . $this->getId() . '" />';
        return $output;
    }

    function generateJs()
    {
        return '';
    }
}