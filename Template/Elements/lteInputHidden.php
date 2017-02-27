<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputHidden extends lteInput {
	
	function generate_html(){
		$output = '<input type="hidden"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $this->escape_string($this->get_value_with_defaults()) . '"
								id="' . $this->get_id() . '" />';
		return $output;
	}

	function generate_js() {
		return '';
	}
}