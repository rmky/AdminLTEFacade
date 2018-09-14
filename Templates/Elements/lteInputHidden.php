<?php
namespace exface\AdminLteTemplate\Templates\Elements;

class lteInputHidden extends lteInput
{

    function buildHtml()
    {
        $output = '<input type="hidden"
								name="' . $this->getWidget()->getAttributeAlias() . '"
								value="' . $this->escapeString($this->getValueWithDefaults()) . '"
								id="' . $this->getId() . '" />';
        return $output;
    }

    function buildJs()
    {
        return $this->buildJsEventHandlers();
    }
}