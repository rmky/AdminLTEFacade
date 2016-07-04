<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteForm extends ltePanel {
	
	function generate_html(){
		$output = '';
		if ($this->get_widget()->get_caption()){
			$output = '<div class="ftitle">' . $this->get_widget()->get_caption() . '</div>';
		}
		
		$output .= '<form class="form" id="' . $this->get_widget()->get_id() . '">';
		$output .= $this->generate_widgets_html();					
		$output .= '</form>';
		
		return $output;
	}
	
	public function get_method() {
		return $this->method;
	}
	
	public function set_method($value) {
		$this->method = $value;
	}
}
?>