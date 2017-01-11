<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputSelect extends lteInput {
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\InputSelect */
		$widget = $this->get_widget();
		$options = '';
		foreach ($widget->get_selectable_options() as $value => $text){
			$options .= '
					<option value="' . $value . '"' . ($this->get_value_with_defaults() === $value ? ' selected="selected"' : '') . '>' . $text . '</option>';
		}
		
		$output = '
						<label ' . ($this->get_widget()->is_required() ? 'class="required"' : '') . '
								for="' . $this->get_id() . '">
							' . $this->get_widget()->get_caption() . '
						</label>
						<select class="form-control"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $this->escape_string($this->get_value_with_defaults()) . '"
								id="' . $this->get_id() . '"
								' . ($this->get_widget()->is_required() ? 'required="true" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . '>
							' . $options . '
						</select>
					';
		
		return $this->build_html_wrapper($output);
	}
}
?>