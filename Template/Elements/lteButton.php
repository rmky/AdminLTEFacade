<?php namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryButtonTrait;
use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Button;

/**
 * Generates jQuery Mobile buttons for ExFace
 * 
 * @author Andrej Kabachnik
 *
 */
class lteButton extends lteAbstractElement {
	
	use JqueryButtonTrait;

	function generate_js(){
		$output = '';
		$hotkey_handlers = array();
		$action = $this->get_action();
		
		// If the button has an action, make some action specific HTML depending on the action
		if ($action){
			if ($action->implements_interface('iShowDialog')){
				$dialog_widget = $action->get_dialog_widget();
				$output .= "$('" . str_replace(array("'", "\r", "\n"), array("\'", "", ""), $this->get_template()->generate_html($dialog_widget)) . "').modal({show:false}).appendTo('body');";
			}
		}
		
		// Get the java script required for the action itself
		if ($action){
			// Actions with template scripts may contain some helper functions or global variables.
			// Print the here first.
			if ($action && $action->implements_interface('iRunTemplateScript')){
				$output .= $this->get_action()->print_helper_functions();
			}
		}
		
		if ($click = $this->build_js_click_function()) {
			
			// Generate the function to be called, when the button is clicked
			$output .= "
				function " . $this->build_js_click_function_name() . "(input){
					" . $click . "
				}
				";
			
			// Handle hotkeys
			if ($this->get_widget()->get_hotkey()){
				$hotkey_handlers[$this->get_widget()->get_hotkey()][] = $this->build_js_click_function_name();
			}
		}
		
		foreach ($hotkey_handlers as $hotkey => $handlers){
			// TODO add hotkey detection here
		}
		
		return $output;
	}

	/**
	 * @see \exface\Templates\jeasyui\Widgets\abstractWidget::generate_html()
	 */
	function generate_html(){
		$output = '';
		/* @var $widget \exface\Core\Widgets\Button */
		$widget = $this->get_widget();
		
		// In any case, create a button
		$icon_classes = ($widget->get_icon_name() && !$widget->get_hide_button_icon() ? ' ' . $this->build_css_icon_class($widget->get_icon_name()) : '');
		$hidden_class = ($widget->is_hidden() ? ' exfHidden' : '');
		$disabled_class = $widget->is_disabled() ? ' disabled' : '';
		$align_class = $this->get_align_class();
		$output .= '
				<button id="' . $this->get_id() . '" type="button" class="btn ' . ($widget->get_visibility() == EXF_WIDGET_VISIBILITY_PROMOTED ? 'btn-primary ' : 'btn-default ') . $hidden_class . $disabled_class . $align_class . '" onclick="' . $this->build_js_click_function_name() . '();">
						<i class="' . $icon_classes . '"></i> ' . ($widget->get_caption() && !$widget->get_hide_button_text() ? $widget->get_caption() : '') . '
				</button>';
		return $output;
	}
	
	protected function build_js_click_show_dialog(ActionInterface $action, AbstractJqueryElement $input_element){
		$widget = $this->get_widget();
		
		$output = $this->build_js_request_data_collector($action, $input_element);
		$output .= "
						" . $this->build_js_busy_icon_show() . "
						$.ajax({
							type: 'POST',
							url: '" . $this->get_ajax_url() ."',
							dataType: 'html',
							data: {
								action: '".$widget->get_action_alias()."',
								resource: '" . $widget->get_page_id() . "',
								element: '" . $widget->get_id() . "',
								data: requestData
							},
							success: function(data, textStatus, jqXHR) {
								" . $this->build_js_close_dialog($widget, $input_element) . "
								" . $this->build_js_input_refresh($widget, $input_element) . "
		                       	" . $this->build_js_busy_icon_hide() . "
		                       	if ($('#ajax-dialogs').length < 1){
		                       		$('body').append('<div id=\"ajax-dialogs\"></div>');
                       			}
		                       	$('#ajax-dialogs').empty().append(data);
		                       	$('#" . $this->get_template()->get_element($action->get_dialog_widget())->get_id() . "').modal().modal('show');
                       			$(document).trigger('exface.AdminLteTemplate.Dialog.Complete', ['" . $this->get_template()->get_element($action->get_dialog_widget())->get_id() . "']);
		                       	"
								// Make sure, the input widget of the button is always refreshed, once the dialog is closed again
								. ($this->build_js_input_refresh($widget, $input_element) ? "$('#" . $this->get_template()->get_element($action->get_dialog_widget())->get_id() . "').one('hide.bs.modal', function(){" . $this->build_js_input_refresh($widget, $input_element) . "});" : "") . "
							},
							error: function(jqXHR, textStatus, errorThrown){
								" . $this->build_js_show_error('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . "
								" . $this->build_js_busy_icon_hide() . "
							}
						});
					";
		
		return $output;
	}

	protected function build_js_close_dialog($widget, $input_element){
		return ($widget->get_widget_type() == 'DialogButton' && $widget->get_close_dialog_after_action_succeeds() ? "$('#" . $input_element->get_id() . "').modal('hide');" : "" );
	}
	
	/**
	 * Returns javascript code with global variables and functions needed for certain button types
	 */
	protected function build_js_globals(){
		$output = '';
		/* Commented out because moved to generate_js()
		// If the button reacts to any hotkey, we need to declare a global variable to collect keys pressed
		if ($this->get_widget()->get_hotkey() == 'any'){
			$output .= 'var exfHotkeys = [];';
		}
		*/
		return $output;
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