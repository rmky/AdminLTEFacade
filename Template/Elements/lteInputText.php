<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputText extends lteInput {
	function generate_html(){
		$output = '
						<label for="' . $this->get_id() . '">' . $this->get_widget()->get_caption() . '</label>
						<textarea class="form-control"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								id="' . $this->get_id() . '"
								style="height: ' . $this->get_height() . '; width: 100%;" 
								' . ($this->get_widget()->is_required() ? 'required="true" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . '>' 
							. $this->get_value_with_defaults() . 
							'</textarea>
					';
		return $this->build_html_wrapper($output);;
	}
}
?>