<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteBox extends ltePanel {
	
	public function generate_html(){
		$panel = parent::generate_html();
		$output = <<<HTML
<div class="{$this->get_width_classes()} exf_grid_item">
	{$panel}
</div>
HTML;
		return $output;
	}
	
	public function build_js_data_getter(){

		return $this->get_function_prefix() . 'getData()';
	}
	
	public function generate_js(){
		$output = parent::generate_js();
		/* @var $widget \exface\Core\Widgets\Box */
		$widget = $this->get_widget();
		
		$data_getter_function = $this->get_function_prefix() . 'getData';
		if ($widget->get_prefill_data()){
			$prefill_uid = $widget->get_prefill_data()->get_cell_value($widget->get_meta_object()->get_uid_alias(), 0);
		}
		
		$data_getters = '';
		foreach ($widget->get_widgets() as $w){
			if ($getter = $this->get_template()->get_element($w)->build_js_data_getter()){
				$data_getters .= "result['" . $w->get_attribute_alias() . "'] = " . $getter . ";\n";
			}
		}
		
		$output .= <<<JS

function {$data_getter_function}(){
	var result = {};
	result.UID = '{$prefill_uid}';
	{$data_getters}
	return new Array(result);
}
		
JS;
		return $output;
	}
}
?>