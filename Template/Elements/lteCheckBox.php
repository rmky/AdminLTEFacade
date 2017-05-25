<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteCheckBox extends lteAbstractElement
{

    function generateHtml()
    {
        $output = '	<div class="fitem exf_input checkbox exf_grid_item ' . $this->getWidthClasses() . '" title="' . $this->buildHintText() . '">
						<label>
							<input type="checkbox" value="1" 
									form="" 
									id="' . $this->getWidget()->getId() . '_checkbox"
									onchange="$(\'#' . $this->getWidget()->getId() . '\').val(this.checked);"' . '
									' . ($this->getWidget()->getValue() ? 'checked="checked" ' : '') . '
									' . ($this->getWidget()->isDisabled() ? 'disabled="disabled"' : '') . ' />
							' . $this->getWidget()->getCaption() . '
						</label>
						<input type="hidden" name="' . $this->getWidget()->getAttributeAlias() . '" id="' . $this->getWidget()->getId() . '" value="' . $this->getWidget()->getValue() . '" />
					</div>';
        return $output;
    }

    function generateJs()
    {
        return '';
    }
}
?>