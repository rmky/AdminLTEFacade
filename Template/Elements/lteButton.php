<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\iModifyData;
use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
/**
 * generates jQuery Mobile buttons for ExFace
 * @author Andrej Kabachnik
 *
 */
class lteButton extends lteAbstractElement {

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
			// See if the action needs some more JS, that is not the click function (e.g. showing another widget)
			if ($action->implements_interface('iShowDialog')){
				$dialog_widget = $action->get_dialog_widget();
				$output .= $this->get_template()->generate_js($dialog_widget);
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
		$action = $this->get_action();
		/* @var $widget \exface\Core\Widgets\Button */
		$widget = $this->get_widget();
		
		// In any case, create a button
		$icon_classes = ($widget->get_icon_name() && !$widget->get_hide_button_icon() ? ' ' . $this->get_icon_class($widget->get_icon_name()) : '');
		$hidden_class = ($widget->is_hidden() ? ' exfHidden' : '');
		$output .= '
				<button id="' . $this->get_id() . '" type="button" class="btn ' . ($widget->get_visibility() == EXF_WIDGET_VISIBILITY_PROMOTED ? 'btn-primary ' : 'btn-default ') . $hidden_class . '" onclick="' . $this->build_js_click_function_name() . '();">
						<i class="' . $icon_classes . '"></i> ' . ($widget->get_caption() && !$widget->get_hide_button_text() ? $widget->get_caption() : '') . '
				</button>';
		return $output;
	}

	function build_js_click_function(){
		$output = '';
		/* @var $widget \exface\Core\Widgets\Button */
		$widget = $this->get_widget();
		$input_element = $this->get_template()->get_element($widget->get_input_widget(), $this->get_page_id());

		$action = $widget->get_action();

		// if the button does not have a action attached, just see if the attributes of the button
		// will cause some click-behaviour and return the JS for that
		if (!$action) {
			$output .= $this->build_js_close_dialog($widget, $input_element)
			. $this->build_js_input_refresh($widget, $input_element);
			return $output;
		}
		
		if (!is_null($action->get_input_rows_min()) || !is_null($action->get_input_rows_max())){
			if ($action->get_input_rows_min() === $action->get_input_rows_max()){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select exactly " . $action->get_input_rows_min() . " row(s)!'); return false;}";
			} elseif (is_null($action->get_input_rows_max())){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . ") {alert('Please select at least " . $action->get_input_rows_min() . " row(s)!'); return false;}";
			} elseif (is_null($action->get_input_rows_min())){
				$js_check_input_rows = "if (requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select at most " . $action->get_input_rows_max() . " row(s)!'); return false;}";
			} else {
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select from " . $action->get_input_rows_min() . " to " . $action->get_input_rows_max() . " rows first!'); return false;}";
			}
		} else {
			$js_check_input_rows = '';
		}

		$js_requestData = "
					
					var requestData = {};
					requestData.oId = '" . $widget->get_meta_object_id() . "';
					requestData.rows = Array.prototype.slice.call(" . $input_element->build_js_data_getter() . ");
					" . $js_check_input_rows;

		if ($action->implements_interface('iRunTemplateScript')){
			$output = $action->print_script($input_element->get_id());
		} elseif ($action->implements_interface('iShowDialog')) {
			// FIXME the request should be sent via POST to avoid length limitations of GET
			// The problem is, we would have to fetch the page via AJAX and insert it into the DOM, which
			// would probably mean, that we have to take care of removing it ourselves (to save memory)...
			$output = $js_requestData . "
					$('#" . $this->get_id($action->get_dialog_widget()->get_id()) . "').find('.modal-body').load(
							'" . $this->get_ajax_url() . "&resource=".$widget->get_page_id()."&element=".$widget->get_id()."&action=".$widget->get_action_alias()."&data=' + encodeURIComponent(JSON.stringify(requestData)));
					$('#" . $this->get_id($action->get_dialog_widget()->get_id()) . "').modal('show');
					" // Make sure, the input widget of the button is always refreshed, once the dialog is closed again 
					. ($this->build_js_input_refresh($widget, $input_element) ? "$('#" . $this->get_id($action->get_dialog_widget()->get_id()) . "').one('hide.bs.modal', function(){" . $this->build_js_input_refresh($widget, $input_element) . "});" : "");
			
					
		} elseif ($action->implements_interface('iShowUrl')) {
			/* @var $action \exface\Core\Interfaces\Actions\iShowUrl */
			$output = $js_requestData . "
					var " . $action->get_alias() . "Url='" . $action->get_url() . "';
					" . $this->build_js_placeholder_replacer($action->get_alias() . "Url", "requestData.rows[0]", $action->get_url(), ($action->get_urlencode_placeholders() ? 'encodeURIComponent' : null));
			if ($action->get_open_in_new_window()){
				$output .= $input_element->build_js_busy_icon_show() . "window.open(" . $action->get_alias() . "Url);" . $input_element->build_js_busy_icon_hide();
			} else {
				$output .= $input_element->build_js_busy_icon_show() . "window.location.href = " . $action->get_alias() . "Url;";
			}
		} elseif ($action->implements_interface('iShowWidget')) {
			if ($action->get_page_id() != $this->get_page_id()){
				$output = $js_requestData . $input_element->build_js_busy_icon_show() . "
				 	window.location.href = '" . $this->get_template()->create_link_internal($action->get_page_id()) . "?prefill={\"meta_object_id\":\"" . $widget->get_meta_object_id() . "\",\"rows\":[{\"" . $widget->get_meta_object()->get_uid_alias() . "\":' + requestData.rows[0]." . $widget->get_meta_object()->get_uid_alias() . " + '}]}';";
			}
		} elseif ($action->implements_interface('iModifyData') && $input_element->get_widget()->get_widget_type() != "DataTable") {
			$output = " var form = $($('#" . $input_element->get_id() . " form')[0]);
						$('#" . $input_element->get_id() . "').append($('<div class=\"overlay\"><i class=\"fa fa-refresh fa-spin\"></i></div>'));

					    var postData = $(form).serializeArray();
					    $.ajax(
					    {
					        url : '" . $this->get_ajax_url() . "&resource=".$widget->get_page_id()."&element=".$widget->get_id()."&action=".$widget->get_action_alias() . "&object=" . $widget->get_meta_object_id() . "',
					        type: 'POST',
					        data : postData,
					        success:function(data, textStatus, jqXHR) 
					        {
					            " . $this->build_js_close_dialog($widget, $input_element) . "
					            " . $this->build_js_input_refresh($widget, $input_element) . "
		                       	" . $input_element->build_js_busy_icon_show() . "
					        },
					        error: function(jqXHR, textStatus, errorThrown) 
					        {
					            " . $input_element->build_js_busy_icon_hide() . "
			                    alert(jqXHR.responseText);      
					        }
					    });";
		} elseif ($action->implements_interface('iNavigate')){
			$output = $input_element->build_js_busy_icon_show() . 'parent.history.back(); return false;';
		} else {
			$output = $js_requestData . "
						" . $input_element->build_js_busy_icon_show() . "
						$.post('" . $this->get_ajax_url() ."',
							{	action: '".$widget->get_action_alias()."',
								resource: '".$widget->get_page_id()."',
								element: '".$widget->get_id()."',
								object: '" . $widget->get_meta_object_id() . "',
								data: requestData
							},
							function(data) {
								" . $this->build_js_input_refresh($widget, $input_element) . "
								" . $input_element->build_js_busy_icon_hide() . "
							}
						);";
		}

		return $output;

	}

	/**
	 * @return ActionInterface
	 */
	private function get_action(){
		return $this->get_widget()->get_action();
	}

	protected function build_js_input_refresh($widget, $input_element){
		return ($widget->get_refresh_input() && $input_element->build_js_refresh() ? $input_element->build_js_refresh() . ";" : "");
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
	
	public function build_js_click_function_name(){
		return $this->get_function_prefix() . 'click';
	}
	
	/**
	 * Returns a javascript snippet, that replaces all placholders in a give string by values from a given javascript object.
	 * Placeholders must be in the general ExFace syntax [#placholder#], while the value object must have a property for every
	 * placeholder with the same name (without "[#" and "#]"!).
	 * @param string $js_var - e.g. result (the variable must be already instantiated!)
	 * @param string $js_values_array - e.g. values = {placeholder = "someId"}
	 * @param string $string_with_placeholders - e.g. http://localhost/pages/[#placeholder#]
	 * @param string $js_sanitizer_function - a Javascript function to be applied to each value (e.g. encodeURIComponent) - without braces!!!
	 * @return string - e.g. result = result.replace('[#placeholder#]', values['placeholder']);
	 */
	protected function build_js_placeholder_replacer($js_var, $js_values_object, $string_with_placeholders, $js_sanitizer_function = null){
		$output = '';
		$placeholders = $this->get_template()->get_workbench()->utils()->find_placeholders_in_string($string_with_placeholders);
		foreach ($placeholders as $ph){
			$value = $js_values_object . "['" . $ph . "']";
			if ($js_sanitizer_function){
				$value = $js_sanitizer_function . '(' . $value . ')';
			}
			$output .= $js_var . " = " . $js_var . ".replace('[#" . $ph . "#]', " . $value . ");";
		}
		return $output;
	}
}
?>