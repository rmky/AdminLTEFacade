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
		$other_options = (!$widget->get_multi_select() ? ',maxSelection: 1' : '') . ($widget->is_disabled() ? ',disabled: true' : '');
		
		// Set initial value
		if ($widget->get_value()){
			if ($widget->get_value_text()){
				// If we have value and text, we simply populate the widget programmatically
				$initial_value_script = " ms.setSelection([{'" . $widget->get_text_column()->get_data_column_name() . "': '" . $widget->get_value_text() . "', '" . $widget->get_value_column()->get_data_column_name() . "': '" . $widget->get_value() . "'}]);";
			} else {
				// If we only have a value and no text, add a special filter on the value column to just select this one value from the sere
				// This filter will get removed after the first data set is received from the server. This ensures, that the selected value
				// is always within the first server request. It also makes the first request much faster as only one row needs to be selected.
				$initial_value_filter = ', fltr00_' . $widget->get_value_column()->get_data_column_name() . ': "' . $widget->get_value() . '"';
			}
		}
		
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
			{$initial_value_filter}
        },
        queryParam: 'q',
        resultsField: 'data',
        valueField: '{$widget->get_value_column()->get_data_column_name()}',
        displayField: '{$widget->get_text_column()->get_data_column_name()}',
        allowFreeEntries: false
        ,firstLoad: true
        {$other_options}
    });
	
	{$initial_value_script}
	
	$(ms).on("selectionchange", function(e,m){
		{$this->build_js_on_selectionchange_live_reference()}
		$("#{$this->get_id()}").val(m.getValue()).trigger("change");
		{$this->get_on_change_script()}
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
	 * Erzeugung einer JavaScript-Funktion zum Auslesen des Wertes (z.B. bei Live-Referenz).
	 * Die zurueckgegebenen Werte sind per MagicSuggest valueField definiert. Sind mehrere
	 * Werte ausgewaehlt wird eine Komma-separierte Liste dieser Werte zurueckgegeben, ist
	 * eine spezifische Spalte ausgewaehlt wird statt dem valueField der Wert dieser Spalte
	 * zurueckgegeben.
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
	 * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes (z.B. bei Live-Referenz).
	 * Die Werte werden gesetzt, indem ein Filter gesetzt wird und per setData der Inhalt
	 * des MagicSuggest neu geladen wird. Die eigentliche Auswahl der Werte geschieht erst
	 * nach dem Laden (on load, siehe build_js_on_load_live_reference()).
	 * 
	 * {@inheritDoc}
	 * @see \exface\AdminLteTemplate\Template\Elements\lteInput::build_js_value_setter()
	 */
	function build_js_value_setter($value){
		$widget = $this->get_widget();
		
		$output = '
				var ' . $this->get_id() . '_ms = $("#' . $this->get_id() . '_ms").magicSuggest();
				' . $this->get_id() . '_ms.getDataUrlParams().fltr00_' . $widget->get_value_column()->get_data_column_name() . ' = ' . $value . ';
				' . $this->get_id() . '_ms.getDataUrlParams().liveReferenceUpdate = true;
				' . $this->get_id() . '_ms.setData("' . $this->get_ajax_url() . '");';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher bei Aenderung der Auswahl des MagicSuggest
	 * ausgefuehrt wird. Ist kein Wert mehr definiert werden gesetzte Filter geloescht,
	 * d.h. danach sind wieder alle Werte in zugaenglich.
	 * 
	 * @return string
	 */
	function build_js_on_selectionchange_live_reference() {
		$widget = $this->get_widget();
		
		$output = '
				if (m.getValue().length == 0) {
					delete m.getDataUrlParams().fltr00_' . $widget->get_value_column()->get_data_column_name() . ';
				}';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher nach dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Nach einem Live-Reference-Update werden auch die ausgewaehlten
	 * Werte angepasst. Die Werte werden aus dem gesetzten Filter gelesen. Es wird
	 * erwartet, dass ein oder mehrere numerische Werte (Oids) uebergeben werden
	 * (mehrere als Komma-separierte Liste). Ist multiselect deaktiviert erfolgt eine
	 * Selektion nur wenn genau ein Wert vorhanden ist. Sind mehr Werte vorhanden muss
	 * der Benutzer den passenden Wert manuell aus dem Dropdown selektieren (welcher
	 * durch den Filter eingeschraenkt ist). 
	 * 
	 * @return string
	 */
	function build_js_on_load_live_reference() {
		$widget = $this->get_widget();
		
		$output = '
				if (m.getDataUrlParams().liveReferenceUpdate) {
					var value = m.getDataUrlParams().fltr00_' . $this->get_widget()->get_value_column()->get_data_column_name() . ', valueArray;
					if (value) {
						valueArray = $.map(value.split(","), $.trim);
					} else {
						valueArray = [];
						delete m.getDataUrlParams().fltr00_' . $widget->get_value_column()->get_data_column_name() . ';
					}
					m.clear();';
		
		if ($this->get_widget()->get_multi_select()) {
			$output .= '
					m.setValue(valueArray);';
		} else {
			$output .= '
					if (valueArray.length == 1) {
						m.setValue(valueArray);
					}';
		}
		
		$output .= '
					
					delete m.getDataUrlParams().liveReferenceUpdate;
				}';
		
		return $output;
	}
	
}
?>