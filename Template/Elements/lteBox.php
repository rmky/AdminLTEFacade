<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\ActionInterface;

class lteBox extends lteForm {
	
	public function generate_html(){
		$output = <<<HTML
<div class="{$this->get_width_classes()} exf_grid_item" style="width:100%">
	{$this->build_html_box()}
</div>
HTML;
		return $output;
	}
	
	protected function build_html_box(){
		// Does the box need a header?
		$header = '';
		if ($this->get_widget()->get_caption()){
			$header .= '<h3 class="box-title">' . $this->get_widget()->get_caption() . '</h3>';
		}
		if ($header){
			$header = '<div class="box-header">' . $header . '</div>';
		}
	
		// Does the box need a footer (for buttons)?
		if ($buttons_html = $this->build_html_buttons()){
			$footer = '	<div class="box-footer clearfix">' . $buttons_html . '</div>';
		}
	
		$output = <<<HTML
<div class="box">
	{$header}
	<div class="box-body">
		<div class="row" id="{$this->get_id()}">
			{$this->build_html_for_widgets()}
			<div class="col-xs-1" id="{$this->get_id()}_sizer" style=""></div>
		</div>
	</div>
	{$footer}
</div>
HTML;
		return $output;
	}
	
	public function build_js_data_getter(ActionInterface $action = null, $custom_body_js = null){
		return parent::build_js_data_getter($action, "data.rows = " . $this->build_js_function_prefix() . "getData();");
	}
	
	public function generate_js(){
		$output = parent::generate_js();
		/* @var $widget \exface\Core\Widgets\Box */
		$widget = $this->get_widget();
		
		$data_getter_function = $this->build_js_function_prefix() . 'getData';
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