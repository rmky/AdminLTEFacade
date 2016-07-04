<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Widgets\Button;
/**
 * generates jQuery Mobile buttons for ExFace
 * @author aka
 *
 */
class lteMenuButton extends lteAbstractElement {

	/**
	 * @see \exface\Templates\jeasyui\Widgets\abstractWidget::generate_html()
	 */
	function generate_html(){		
		$buttons_html = '';
		$output = '';
		/* @var $b \exface\Core\Widgets\Button */
		foreach ($this->get_widget()->get_buttons() as $b){
			// If the button has an action, make some action specific HTML depending on the action
			if ($action = $b->get_action()){
				if ($action->implements_interface('iShowDialog')){
					$dialog_widget = $action->get_dialog_widget();
					$output .= $this->get_template()->generate_html($dialog_widget);
				}
			}
			// In any case, create a menu entry
			$buttons_html .= '<li><a data-target="#" onclick="' . $this->generate_js_button_function_name($b) . '();"><i class="' . $this->get_icon_class($b->get_icon_name()) . '"></i>' . $b->get_caption() . '</a></li>';
		}
		$icon = ($this->get_widget()->get_icon_name() ? '<i class="' . $this->get_icon_class($this->get_widget()->get_icon_name()) . '"></i> ' : '');
		
		$output .= <<<HTML

<button type="button" class="btn btn-default" data-toggle="dropdown">{$icon}{$this->get_widget()->get_caption()}</button>
<ul class="dropdown-menu" role="menu">
	{$buttons_html}
</ul>		
HTML;
		
		return $output;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \exface\AdminLteTemplate\Template\Elements\jqmAbstractElement::generate_js()
	 */
	function generate_js(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $b){
			if ($click = $b->generate_js_click_function()) {
				$output .= "
					function " . $this->generate_js_button_function_name($b) . "(){
						" . $b->generate_js_click_function() . "
					}
					";
			}
		}
		return $output;
	}
	
	function generate_js_button_function_name(Button $button){
		return $this->get_template()->get_element($button)->generate_js_click_function_name();
	}
}
?>