<?php
namespace exface\AdminLTEFacade\Facades\Elements;

class LteInputHidden extends lteInput
{

    function buildHtml()
    {
        $output = '<input type="hidden"
								name="' . $this->getWidget()->getAttributeAlias() . '"
								value="' . $this->escapeString($this->getWidget()->getValueWithDefaults(), false, true) . '"
								id="' . $this->getId() . '" />';
        return $output;
    }

    function buildJs()
    {
        return $this->buildJsEventHandlers();
    }
}