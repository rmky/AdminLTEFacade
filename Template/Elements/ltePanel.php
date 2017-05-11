<?php
namespace exface\AdminLteTemplate\Template\Elements;
class ltePanel extends lteContainer {
	
	function generate_html(){
		return '
				<div id="' . $this->get_id() . '" class="' . $this->get_width_classes() . ' exf_grid masonry">
					' . $this->build_html_for_children() . '
					<div class="col-xs-1" id="' . $this->get_id() . '_sizer" style=""></div>
				</div>';
	}
	
	function generate_js(){
		$output = "
				$('#" . $this->get_id() . "').masonry({columnWidth: '#" . $this->get_id() . "_sizer', itemSelector: '#" . $this->get_id() . " > .exf_grid_item'});
				$('#" . $this->get_id() . "').children('.exf_grid_item').on('resize', function(event){ $('#" . $this->get_id() . "').masonry('layout'); });
				";
	
		return $output . $this->build_js_for_children();
	}

}
?>