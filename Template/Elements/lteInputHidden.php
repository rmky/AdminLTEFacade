<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputHidden extends lteInput {
	
	function generate_html(){
		$output = '<input type="hidden" 
								name="' . $this->get_widget()->get_attribute_alias() . '" 
								value="' . addslashes($this->get_widget()->get_value()) . '" 
								id="' . $this->get_id() . '" />';
		return $output;
	}

}