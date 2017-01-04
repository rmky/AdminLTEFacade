<?php namespace exface\AdminLteTemplate\Template\Elements;

class lteInputGroup extends ltePanel {
	
	public function generate_html(){
		$children_html = $this->build_html_for_children();
		
		$output = '
				<fieldset class="exface_inputgroup">
					<legend>'.$this->get_widget()->get_caption().'</legend>
					'.$children_html.'
				</fieldset>';
		return $output;
	}
	
	public function generate_js() {
		
	}
}
?>
