<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteTextHeading extends lteText {
	
	function generate_html(){
		$output = '';
		$output .= '<h' . $this->get_widget()->get_heading_level() . ' id="' . $this->get_id() . '">' . $this->get_widget()->get_text() . '</h' . $this->get_widget()->get_heading_level() . '>';
		return $this->generate_html_wrapper($output);
	}
	
}
?>