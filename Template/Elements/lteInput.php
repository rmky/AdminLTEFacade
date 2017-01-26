<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInput extends lteAbstractElement {
	
	protected function init(){
		parent::init();
		$this->set_element_type('text');
	}
	
	function generate_html(){
		$output = '
						<label for="' . $this->get_id() . '">' . $this->get_widget()->get_caption() . '</label>
						<input class="form-control"
								type="' . $this->get_element_type() . '"
								name="' . $this->get_widget()->get_attribute_alias() . '" 
								value="' . $this->escape_string($this->get_value_with_defaults()) . '" 
								id="' . $this->get_id() . '"  
								' . ($this->get_widget()->is_required() ? 'required="true" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . '/>
					';
		return $this->build_html_wrapper($output);
	}
	
	public function build_html_wrapper($inner_html){
		$output = '
					<div class="fitem exf_input exf_grid_item ' . $this->get_width_classes() . '" title="' . $this->build_hint_text() . '">
							' . $inner_html . '
					</div>';
		return $output;
	}
	
	public function get_value_with_defaults(){
		$value = $this->get_widget()->get_value();
		if (is_null($value) || $value === ''){
			$value = $this->get_widget()->get_default_value();
		}
		return $value;
	}
	
	function generate_js(){
		return '';
	}
}
?>