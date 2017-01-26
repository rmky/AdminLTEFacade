<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteCheckBox extends lteAbstractElement {
	
	function generate_html(){
		$output = '	<div class="fitem exf_input checkbox exf_grid_item ' . $this->get_width_classes() . '" title="' . $this->build_hint_text() . '">
						<label>
							<input type="checkbox" value="1" 
									form="" 
									id="' . $this->get_widget()->get_id() . '_checkbox"
									onchange="$(\'#' . $this->get_widget()->get_id() . '\').val(this.checked);"' . '
									' . ($this->get_widget()->get_value() ? 'checked="checked" ' : '') . '
									' . ($this->get_widget()->is_disabled() ? 'disabled="disabled"' : '') . ' />
							' . $this->get_widget()->get_caption() . '
						</label>
						<input type="hidden" name="' . $this->get_widget()->get_attribute_alias() . '" id="' . $this->get_widget()->get_id() . '" value="' . $this->get_widget()->get_value() . '" />
					</div>';
		return $output;
	}
	
	function generate_js(){
		return '';
	}
}
?>