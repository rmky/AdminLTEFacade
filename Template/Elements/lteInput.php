<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryLiveReferenceTrait;

class lteInput extends lteText {
	
	use JqueryLiveReferenceTrait;
	
	protected function init(){
		parent::init();
		$this->set_element_type('text');
		// If the input's value is bound to another element via an expression, we need to make sure, that other element will
		// change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
		// has not generated it's JS code yet!
		$this->register_live_reference_at_linked_element();
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
		if ($this->get_widget()->get_value_expression() && $this->get_widget()->get_value_expression()->is_reference()){
			$value = '';
		} else {
			$value = $this->get_widget()->get_value();
		}
		if (is_null($value) || $value === ''){
			$value = $this->get_widget()->get_default_value();
		}
		return $value;
	}
	
	function generate_js(){
		$output = '';
		
		if ($this->get_widget()->is_required()) {
			$output .= $this->build_js_required();
		}
		$output .= $this->build_js_on_change_handler();
		
		return $output;
	}
	
	function build_js_required() {
		$output = '
					// checks if a value is set when the element is created
					if ($(\'#' .$this->get_id() . '\').first().val()) {
						$(\'#' .$this->get_id() . '\').first().parent().removeClass(\'invalid\');
					} else {
						$(\'#' .$this->get_id() . '\').first().parent().addClass(\'invalid\');
					};
					
					// checks if a value is set when the element is changed
					$(\'#' .$this->get_id() . '\').on(\'input change\', function() {
						if (this.value) {
							$(this).parent().removeClass(\'invalid\');
						} else {
							$(this).parent().addClass(\'invalid\');
						}
					});';
		
		return $output;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_data_getter($action, $custom_body_js)
	 */
	public function build_js_data_getter(ActionInterface $action = null){
		if ($this->get_widget()->is_display_only()){
			return '{}';
		} else {
			return parent::build_js_data_getter($action);
		}
	}
	
	protected function build_js_on_change_handler(){
		if ($this->get_on_change_script()){
			// verknuepfter Wert wird initialisiert, bei Aenderungen aktualisiert
			$output = '
					' . $this->get_on_change_script() . '
					$("#' . $this->get_id() . '").on("input change", function() {
						' . $this->get_on_change_script() . '
					});';
		} else {
			$output = '';
		}
		
		return $output;
	}
	
	function build_js_value_setter($value){
		$output = '
				var ' . $this->get_id() . ' = $("#' . $this->get_id() . '");
				' . $this->get_id() . '.val(' . $value . ');
				' . $this->get_id() . '.trigger("change");';
		
		return $output;
	}
	
}
?>