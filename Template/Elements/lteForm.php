<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteForm extends ltePanel {
	
	function generate_html(){
		$output = '';
		if ($this->get_widget()->get_caption()){
			$output = '<div class="ftitle">' . $this->get_widget()->get_caption() . '</div>';
		}
		
		$output .= '<form class="form" id="' . $this->get_widget()->get_id() . '">';
		$output .= $this->build_html_for_widgets();	
		$output .= '<div class="col-xs-12" id="' . $this->get_id() . '_sizer" style=""></div>';
		$output .= '</form>';
		
		return $output;
	}
	
	function generate_js(){
		// FIXME had to override the generate_js() method of lteContainer here, because masonry broke the form for some reason. But masonry
		// layouts are important for forms, so this needs to be fixed. Remove this method from lteForm when done.
		return $this->build_js_for_children();
	}
	
	public function get_method() {
		return $this->method;
	}
	
	public function set_method($value) {
		$this->method = $value;
	}
	
	function build_html_buttons(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_html($btn);
		}
	
		return $output;
	}
	
	function build_js_buttons(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_js($btn);
		}
	
		return $output;
	}
}
?>