<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteInputText extends lteInput
{

    function generateHtml()
    {
        $output = '
						<label for="' . $this->getId() . '">' . $this->getWidget()->getCaption() . '</label>
						<textarea class="form-control"
								name="' . $this->getWidget()->getAttributeAlias() . '"
								id="' . $this->getId() . '"
								style="height: ' . $this->getHeight() . '; width: 100%;" 
								' . ($this->getWidget()->isRequired() ? 'required="true" ' : '') . '
								' . ($this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '') . '>' . $this->getValueWithDefaults() . '</textarea>
					';
        return $this->buildHtmlWrapper($output);
        ;
    }
}
?>