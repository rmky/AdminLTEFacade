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
		$output = '	<div class="fitem exf_input" title="' . $this->build_hint_text() . '">
						<label for="' . $this->get_id() . '">' . $this->get_widget()->get_caption() . '</label>
						<input type="hidden"
								id="' . $this->get_id() . '" 
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $this->escape_string($this->get_value_with_defaults()) . '" />
						<input class="form-control"
								id="' . $this->get_id() . '_ms"
								' . ($this->get_widget()->get_value() ? "value='[\"" . $this->escape_string($this->get_value_with_defaults()) . "\"]' " : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . '/>
					</div>';
		return $output;
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\ComboTable */
		$widget = $this->get_widget();
		
		// Add other options
		$other_options = (!$widget->get_multi_select() ? ', maxSelection: 1' : '');
		
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
    
    $(ms).on('selectionchange', function(e,m){
	  $('#{$this->get_id()}').val(ms.getValue()).trigger('input');
	});
	  		
	$(ms).on('load', function(e,ms){
		delete ms.getDataUrlParams()["fltr00_{$widget->get_value_column()->get_data_column_name()}"];
  	});
});		
JS;
		
		$output .= $this->build_js_required();
		
		return $output;
	}
	
	function generate_headers(){
		$headers = parent::generate_headers();
		$headers[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
		$headers[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
		return $headers;
	}
	
}
?>