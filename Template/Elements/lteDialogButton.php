<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;

/**
 * generates jEasyUI-Buttons for ExFace dialogs
 * @author Andrej Kabachnik
 *
 */
class lteDialogButton extends lteButton {
	protected function build_js_click_call_server_action(ActionInterface $action, AbstractJqueryElement $input_element){
		// Check if all required attributes are filled in before sending the request.
		$output = "
				var invalidElements = [];";
		
		foreach ($input_element->get_widget()->get_input_widgets() as $child) {
			if ($child->is_required() && !$child->is_hidden()) {
				$childDataGetter = $this->get_template()->get_element($child)->build_js_value_getter($action);
				if (!$alias = $child->get_caption()) {
					$alias = method_exists($child, 'get_attribute_alias') ? $child->get_attribute_alias() : $child->get_meta_object()->get_alias_with_namespace();
				}
				$output .= "
				if(!{$childDataGetter}) { invalidElements.push('" . $alias . "'); }";
			}
		}
		
		$output .= "
				if(invalidElements.length > 0) {
					{$this->build_js_show_message_error('"' . $this->translate('MESSAGE.FILL_REQUIRED_ATTRIBUTES') . '" + invalidElements.join(", ")')}
				} else {
					" . parent::build_js_click_call_server_action($action, $input_element) . "
				}";
		
		return $output;
	}
}
?>