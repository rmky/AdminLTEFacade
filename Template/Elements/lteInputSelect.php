<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\InputSelect;

/**
 * 
 * @method InputSelect get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteInputSelect extends lteInput {
	
	public function generate_html(){
		/* @var $widget \exface\Core\Widgets\InputSelect */
		$widget = $this->get_widget();
		$options = '';
		foreach ($widget->get_selectable_options() as $value => $text){
			if ($this->get_widget()->get_multi_select() && count($this->get_widget()->get_values()) > 1){
				$selected = in_array($value, $this->get_widget()->get_values());
			} else {
				$selected = strcasecmp($this->get_value_with_defaults(), $value) == 0 ? true : false;
			}
			$options .= '
					<option value="' . $value . '"' . ($selected ? ' selected="selected"' : '') . '>' . $text . '</option>';
		}
		
		$output = '
						<label for="' . $this->get_id() . '">' . $this->get_widget()->get_caption() . '</label>
						<select class="form-control"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $this->escape_string($this->get_value_with_defaults()) . '"
								id="' . $this->get_id() . '"
								' . ($this->get_widget()->is_required() ? 'required="true" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . '
								' . ($this->get_widget()->get_multi_select() ? 'multiple' : '') . '>
							' . $options . '
						</select>
					';
		
		return $this->build_html_wrapper($output);
	}
}
?>