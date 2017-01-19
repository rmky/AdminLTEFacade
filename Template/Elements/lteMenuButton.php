<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Widgets\Button;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryButtonTrait;
/**
 * generates jQuery Mobile buttons for ExFace
 * @author Andrej Kabachnik
 *
 */
class lteMenuButton extends lteAbstractElement {

	use JqueryButtonTrait;
	
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
			$disabled_class = $b->is_disabled() ? 'disabled' : '';
			$buttons_html .= '
					<li' . ($b->is_disabled() ? ' class="disabled"' : '') . '>
						<a data-target="#"' . ($b->is_disabled() ? '' : ' onclick="' . $this->build_js_button_function_name($b) . '();"') . '>
							<i class="' . $this->build_css_icon_class($b->get_icon_name()) . '"></i>'
												. $b->get_caption() . '
						</a>
					</li>';
		}
		$icon = ($this->get_widget()->get_icon_name() ? '<i class="' . $this->build_css_icon_class($this->get_widget()->get_icon_name()) . '"></i> ' : '');
		$align_class = $this->get_align_class();
		
		$output .= <<<HTML

<div class="btn-group{$align_class}">
	<button type="button" class="btn btn-default" data-toggle="dropdown">{$icon}{$this->get_widget()->get_caption()}</button>
	<ul class="dropdown-menu" role="menu">
		{$buttons_html}
	</ul>
</div>
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
			if ($js_click_function = $this->get_template()->get_element($b)->build_js_click_function()) {
				$output .= "
					function " . $this->build_js_button_function_name($b) . "(){
						" . $js_click_function . "
					}
					";
			}
		}
		return $output;
	}
	
	function build_js_button_function_name(Button $button){
		return $this->get_template()->get_element($button)->build_js_click_function_name();
	}
	
	function get_align_class() {
		$align = $this->get_widget()->get_align();
		if ($align == 'left') { $align_class = ' pull-left'; }
		elseif ($align == 'right') { $align_class = ' pull-right'; }
		else { $align_class = ''; }
		return $align_class;
	}
}
?>