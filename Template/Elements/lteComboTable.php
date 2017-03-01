<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 * In jQuery Mobile a ComboTable is represented by a filterable UL-list. The code is based on the JQM-example below.
 * jqm example: http://demos.jquerymobile.com/1.4.5/listview-autocomplete-remote/
 * @author Andrej Kabachnik
 *
 */
class lteComboTable extends lteInput {
	private $min_chars_to_search = 1;

	function generate_html(){
		$value = $this->escape_string($this->get_value_with_defaults());
		$output = '
						<label for="' . $this->get_id() . '">' . $this->get_widget()->get_caption() . '</label>
						<input type="hidden"
								id="' . $this->get_id() . '" 
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $value . '" />
						<input class="form-control"
								id="' . $this->get_id() . '_ms"
								' . ($value ? "value='[\"" . $value . "\"]' " : '') . '/>
					';
		return $this->build_html_wrapper($output);
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\ComboTable */
		$widget = $this->get_widget();
		
		// Add other options
		$options = [];
		if (!$widget->get_multi_select()) {$options[] = 'maxSelection: 1'; }
		if ($widget->is_disabled()) { $options[] = 'disabled: true'; }
		$other_options = implode(",\n\t\t", $options);
		$other_options = $other_options ? ', ' . $other_options : '';
		
		$output = <<<JS
		
$(document).ready(function() {
	
	var ms = $('#{$this->get_id()}_ms').magicSuggest({
		data: "{$this->get_ajax_url()}",
		dataUrlParams: {
			resource: "{$this->get_page_id()}",
			element: "{$widget->get_table()->get_id()}",
			object: "{$widget->get_table()->get_meta_object()->get_id()}",
			action: "{$widget->get_lazy_loading_action()}",
			length: {$widget->get_max_suggestions()},
			start: 0,
			initialLoad: true
		},
		queryParam: 'q',
		resultsField: 'data',
		valueField: '{$widget->get_value_column()->get_data_column_name()}',
		displayField: '{$widget->get_text_column()->get_data_column_name()}',
		allowFreeEntries: false
		{$other_options}
	});
	
	$(ms).on("selectionchange", function(e,m){
		$("#{$this->get_id()}").val(m.getValue()).trigger("change");
		{$this->get_on_change_script()}
	});
	
	$(ms).on("beforeload", function(e,m){
		{$this->build_js_on_beforeload_live_reference()}
	});
	
	$(ms).on("load", function(e,m){
		{$this->build_js_on_load_live_reference()}
	});
});		
JS;
		
		if ($widget->is_required()) {
			$output .= $this->build_js_required();
		}
		
		return $output;
	}
	
	function generate_headers(){
		$headers = parent::generate_headers();
		$headers[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
		$headers[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
		return $headers;
	}
	
	/**
	 * Erzeugung einer JavaScript-Funktion zum Auslesen des Wertes. Die zurueckgegebenen
	 * Werte sind per MagicSuggest valueField definiert. Sind mehrere Werte ausgewaehlt
	 * wird eine Komma-separierte Liste dieser Werte zurueckgegeben. Ist eine spezifische
	 * Spalte ausgewaehlt, wird statt dem valueField der Wert dieser Spalte zurueckgegeben.
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_value_getter()
	 */
	function build_js_value_getter($column = null, $row = null){
		if ($this->get_widget()->get_multi_select() || is_null($column) || $column === ''){
			$output = '$("#' . $this->get_id() . '_ms").magicSuggest().getValue().join()';
		} else {
			$output = '(function() {
					var row = $("#' . $this->get_id() . '_ms").magicSuggest().getSelection();
					if (row.length > 0) { return row[0]["' . $column . '"]; } else { return ""; }
				})()';
		}
		
		return $output;
	}
	
	/**
	 * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes. Ist multiselect false
	 * wird der Wert nur gesetzt wenn genau ein Wert uebergeben wird. Anschliessend wird
	 * der Inhalt des MagicSuggest neu geladen (um ordentliche Label anzuzeigen falls
	 * auch ein entsprechender Filter gesetzt ist).
	 * 
	 * {@inheritDoc}
	 * @see \exface\AdminLteTemplate\Template\Elements\lteInput::build_js_value_setter()
	 */
	function build_js_value_setter($value){
		$widget = $this->get_widget();
		
		$output = '
				var ' . $this->get_id() . '_ms = $("#' . $this->get_id() . '_ms").magicSuggest();
				var value = ' . $value . ', valueArray;
				if (value) { valueArray = $.map(value.split(","), $.trim); } else { valueArray = []; }
				' . $this->get_id() . '_ms.clear();';
		
		if ($this->get_widget()->get_multi_select()) {
			$output .= '
				' . $this->get_id() . '_ms.setValue(valueArray);';
		} else {
			$output .= '
				if (valueArray.length == 1) {
					' . $this->get_id() . '_ms.setValue(valueArray);
				}';
		}
		
		$output .= '
				' . $this->get_id() . '_ms.getDataUrlParams().jsValueSetterUpdate = true;
				' . $this->get_id() . '_ms.setData("' . $this->get_ajax_url() . '");';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher vor dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Es werden die gesetzten Filter den dataUrlParams hinzuge-
	 * fuegt (werden nach dem Laden wieder entfernt, da sich die Werte durch Live-
	 * Referenzen aendern koennen).
	 * 
	 * @return string
	 */
	function build_js_on_beforeload_live_reference() {
		$widget = $this->get_widget();
		
		$fltrId = 0;
		// Add filters from widget
		$filters = [];
		if ($widget->get_table()->has_filters()){
			foreach ($widget->get_table()->get_filters() as $fltr){
				if ($fltr->get_value_expression() && $fltr->get_value_expression()->is_reference()){
					//filter is a live reference
					$link = $fltr->get_value_expression()->get_widget_link();
					$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
					$filters[] = 'dataUrlParams.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = ' . $linked_element->build_js_value_getter($link->get_column_id()) . ';';
				} else {
					//filter has a static value
					$filters[] = 'dataUrlParams.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = "' . $fltr->get_value() . '";';
				}
			}
		}
		$filters_script = implode("\n\t\t\t\t\t", $filters);
		// Add value filter (to show proper label for a set value)
		$value_filters = [];
		$value_filters[] = 'dataUrlParams.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $widget->get_value_column()->get_data_column_name() . ' = m.getValue().join();';
		$value_filters_script = implode("\n\t\t\t\t\t", $value_filters);
		
		$output = '
				var dataUrlParams = m.getDataUrlParams();
				
				if (dataUrlParams.jsValueSetterUpdate) {
					' . $value_filters_script . '
				} else {
					' . $filters_script . '
				}';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher nach dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Der Wert wird neu gesetzt um das Label ordentlich anzu-
	 * zeigen. Ausserdem werden gesetzten Filter nach dem Laden wieder entfernt,
	 * da sich die Werte durch Live-Referenzen aendern koennen (werden vor dem
	 * naechsten Laden wieder hinzugefuegt).
	 * 
	 * @return string
	 */
	function build_js_on_load_live_reference() {
		$widget = $this->get_widget();
		
		// Add initial value
		if ($widget->get_value_expression() && $widget->get_value_expression()->is_reference()){
			//widget has a live reference value
			$link = $widget->get_value_expression()->get_widget_link();
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($widget->get_value_text()){
				$initial_value_script= 'm.setSelection([{"' . $widget->get_text_column()->get_data_column_name() . '": "' . $widget->get_value_text() . '", "' . $widget->get_value_column()->get_data_column_name() . '": ' . $linked_element->build_js_value_getter($link->get_column_id()) . '}]);';
			} else {
				$initial_value_script = 'm.setValue([' . $linked_element->build_js_value_getter($link->get_column_id()) . '])';
			}
		} elseif ($widget->get_value()) {
			//widget has a static value
			if ($widget->get_value_text()){
				$initial_value_script = 'm.setSelection([{"' . $widget->get_text_column()->get_data_column_name() . '": "' . $widget->get_value_text() . '", "' . $widget->get_value_column()->get_data_column_name() . '": "' . $widget->get_value() . '"}]);';
			} else {
				$initial_value_script = 'm.setValue([' . $widget->get_value() . '])';
			}
		}
		
		$output = '
				var dataUrlParams = m.getDataUrlParams();
				
				for (key in dataUrlParams) {
					if (key.substring(0, 4) == "fltr") {
						delete dataUrlParams[key];
					}
				}
				
				if (dataUrlParams.jsValueSetterUpdate) {
					var value = m.getValue();
					m.clear();
					m.setValue(value);
					
					delete dataUrlParams.jsValueSetterUpdate;
				}
				
				if (dataUrlParams.initialLoad) {
					' . $initial_value_script . '
					delete dataUrlParams.initialLoad;
					dataUrlParams.jsValueSetterUpdate = true;
					m.setData("' . $this->get_ajax_url() . '");
				}';
		
		return $output;
	}
	
}
?>