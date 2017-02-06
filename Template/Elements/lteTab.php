<?php namespace exface\AdminLteTemplate\Template\Elements;

class lteTab extends ltePanel {

	function generate_html(){
		$output = '
	<div id="' . $this->get_id() . '" class="nav-tabs-custom">
		<ul class="nav nav-tabs">
			' . $this->generate_html_header() . '
		</ul>
		<div class="tab-content">
			' . $this->generate_html_content() . '
		</div>
	</div>';
		
		return $output;
	}
	
	function generate_html_header() {
		//der erste Tab ist aktiv
		$active_class = $this->get_widget() === $this->get_widget()->get_parent()->get_children()[0] ? ' active' : '';
		
		$output = '
	<li class="' . $active_class . '"><a href="#' . $this->get_id() . '" data-toggle="tab">' . $this->get_widget()->get_caption() . '</a></li>';
		return $output;
	}
	
	function generate_html_content() {
		//der erste Tab ist aktiv
		$active_class = $this->get_widget() === $this->get_widget()->get_parent()->get_children()[0] ? ' active' : '';
		
		$output =
	'<div class="tab-pane' . $active_class . '" id="' . $this->get_id() . '">
		<div class="tab-pane-content-wrapper">
			' . $this->build_html_for_children() . '
		</div>
	</div>';
		return $output;
	}
}
?>
