<?php namespace exface\AdminLteTemplate\Template\Elements;

class lteTabs extends lteContainer {

	function generate_html(){
		$header_html = '';
		$content_html = '';
		foreach ($this->get_widget()->get_children() as $tab) {
			$header_html .= $this->get_template()->get_element($tab)->generate_html_header();;
			$content_html .= $this->get_template()->get_element($tab)->generate_html_content();
		}
		
		$output = '
	<div id="' . $this->get_id() . '" class="nav-tabs-custom">
		<ul class="nav nav-tabs">
			' . $header_html . '
		</ul>
		<div class="tab-content">
			' . $content_html . '
		</div>
	</div>';
	
		return $output;
	}
}
?>
