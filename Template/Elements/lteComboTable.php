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
		
		// Add initial value
		if ($link = $widget()->get_value_widget_link()){
			//widget has a live reference value
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($widget->get_value_text()){
				$initial_value_script = 'ms.setSelection([{"' . $widget->get_text_column()->get_data_column_name() . '": "' . preg_replace( "/\r|\n/", "", $widget->get_value_text()) . '", "' . $widget->get_value_column()->get_data_column_name() . '": ' . $linked_element->build_js_value_getter($link->get_column_id()) . '}]);';
			} else {
				$initial_value_script = $this->build_js_value_setter($linked_element->build_js_value_getter($link->get_column_id()));
				$initial_filter_script = ', fltr00_' . $widget->get_value_column()->get_data_column_name() . ': ' . $linked_element->build_js_value_getter($link->get_column_id());
			}
		} elseif ($widget->get_value()) {
			//widget has a static value
			if ($widget->get_value_text()){
				$initial_value_script = 'ms.setSelection([{"' . $widget->get_text_column()->get_data_column_name() . '": "' . preg_replace( "/\r|\n/", "", $widget->get_value_text()) . '", "' . $widget->get_value_column()->get_data_column_name() . '": "' . $widget->get_value() . '"}]);';
			} else {
				$initial_value_script = $this->build_js_value_setter($widget->get_value());
				$initial_filter_script = ', fltr00_' . $widget->get_value_column()->get_data_column_name() . ': ' . $widget->get_value();
			}
		}
		
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
			start: 0
			{$initial_filter_script}
		},
		queryParam: 'q',
		resultsField: 'data',
		valueField: '{$widget->get_value_column()->get_data_column_name()}',
		displayField: '{$widget->get_text_column()->get_data_column_name()}',
		allowFreeEntries: false
		{$other_options}
	});
	
	{$initial_value_script}
	
	$(ms).on("selectionchange", function(e,m){
		$("#{$this->get_id()}").val(m.getValue().join()).trigger("change");
		{$this->get_on_change_script()}
	});
	
	$(ms).on("beforeload", function(e,m){
		{$this->build_js_on_beforeload_live_reference()}
	});
	
	$(ms).on("load", function(e,m){
		{$this->build_js_on_load_live_reference()}
	});
	
	//notwendig fuer Eingabe mit BarcodeScanner
	var {$this->get_id()}_typingTimer;
	var {$this->get_id()}_input = $("#{$this->get_id()}_ms .ms-sel-ctn input");
	{$this->get_id()}_input.on("keyup", function() {
		clearTimeout({$this->get_id()}_typingTimer);
		if ({$this->get_id()}_input.val()) {
			{$this->get_id()}_typingTimer = setTimeout(function() {
				$("#{$this->get_id()}_ms").magicSuggest().expand();
			}, 400);
		}
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
	 * Ist MagicSuggest noch nicht erzeugt wird stattdessen der Wert aus dem verknuepften
	 * InputHidden zurueckgegeben.
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_value_getter()
	 */
	function build_js_value_getter($column = null, $row = null){
		if ($this->get_widget()->get_multi_select() || is_null($column) || $column === ''){
			$output = '(function() {
					var ' . $this->get_id() . '_ms = $("#' . $this->get_id() . '_ms");
					if (' . $this->get_id() . '_ms.data("magicSuggest")) {
						return ' . $this->get_id() . '_ms.magicSuggest().getValue().join();
					} else {
						return $("#' . $this->get_id() . '").val();
					}
				})()';
		} else {
			$output = '(function() {
					var ' . $this->get_id() . '_ms = $("#' . $this->get_id() . '_ms");
					if (' . $this->get_id() . '_ms.data("magicSuggest")) {
						var row = ' . $this->get_id() . '_ms.magicSuggest().getSelection();
						if (row.length > 0) { return row[0]["' . $column . '"]; } else { return ""; }
					} else {
						return $("#' . $this->get_id() . '").val();
					}
				})()';
		}
		
		return $output;
	}
	
	/**
	 * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes. Ist multiselect false
	 * wird der Wert nur gesetzt wenn genau ein Wert uebergeben wird. Anschliessend wird
	 * der Inhalt des MagicSuggest neu geladen (um ordentliche Label anzuzeigen falls
	 * auch ein entsprechender Filter gesetzt ist). Ist MagicSuggest noch nicht erzeugt
	 * wird stattdessen der Wert im verknuepften InputHidden gesetzt.
	 * 
	 * {@inheritDoc}
	 * @see \exface\AdminLteTemplate\Template\Elements\lteInput::build_js_value_setter()
	 */
	function build_js_value_setter($value){
		$widget = $this->get_widget();
		
		$output = '
				var ' . $this->get_id() . '_ms = $("#' . $this->get_id() . '_ms");
				var value = ' . $value . ', valueArray;
				if (' . $this->get_id() . '_ms.data("magicSuggest")) {
					if (value) {
						switch ($.type(value)) {
							case "number":
								valueArray = [value]; break;
							case "string":
								valueArray = $.map(value.split(","), $.trim); break;
							case "array":
								valueArray = value; break;
							default:
								valueArray = [];
						}
					} else {
						valueArray = [];
					}
					' . $this->get_id() . '_ms.magicSuggest().clear();';
		
		if ($this->get_widget()->get_multi_select()) {
			$output .= '
					' . $this->get_id() . '_ms.magicSuggest().setValue(valueArray);
					$("#' . $this->get_id() . '").val(value).trigger("change");';
		} else {
			$output .= '
					if (valueArray.length == 1) {
						' . $this->get_id() . '_ms.magicSuggest().setValue(valueArray);
						$("#' . $this->get_id() . '").val(value).trigger("change");
					}';
		}
		
		$output .= '
					' . $this->get_id() . '_ms.magicSuggest().getDataUrlParams().jsValueSetterUpdate = true;
					' . $this->get_id() . '_ms.magicSuggest().setData("' . $this->get_ajax_url() . '");
				} else {
					$("#' . $this->get_id() . '").val(value).trigger("change");
				}';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher vor dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Wurde programmatisch ein Wert gesetzt, wird als Filter
	 * nur dieser Wert hinzugefuegt, um das Label ordentlich anzuzeigen. Sonst werden
	 * die am Widget definierten Filter gesetzt. Die Filter werden nach dem Laden
	 * wieder entfernt, da sich die Werte durch Live-Referenzen aendern koennen.
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
				if ($link = $fltr->get_value_widget_link()){
					//filter is a live reference
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
	 * ausgefuehrt wird. Alle gesetzten Filter werden entfernt, da sich die Werte
	 * durch Live-Referenzen aendern koennen (werden vor dem naechsten Laden wieder
	 * hinzugefuegt). Wurde der Wert zuvor programmatisch gesetzt, wird er neu
	 * gesetzt um das Label ordentlich anzuzeigen. Nach der Erzeugung von MagicSuggest
	 * werden initiale Werte gesetzt und neu geladen.
	 * 
	 * @return string
	 */
	function build_js_on_load_live_reference() {
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
				}';
		
		return $output;
	}
	
}
?>