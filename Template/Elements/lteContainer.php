<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteContainer extends lteAbstractElement {
	
	function generate_html(){
		return '
				<div id="' . $this->get_id() . '" class="' . $this->get_width_classes() . ' exf_grid">
					' . $this->children_generate_html() . '
					<div class="col-xs-1" id="' . $this->get_id() . '_sizer" style=""></div>
				</div>';
	}
	
	function children_generate_html(){
		foreach ($this->get_widget()->get_children() as $subw){
			$output .= $this->get_template()->generate_html($subw) . "\n";
		};
		return $output;
	}
	
	function generate_js(){
		$output = "
				$('#" . $this->get_id() . "').masonry({columnWidth: '#" . $this->get_id() . "_sizer', itemSelector: '#" . $this->get_id() . " > .exf_grid_item'});
				$('#" . $this->get_id() . "').children('.exf_grid_item').on('resize', function(event){ $('#" . $this->get_id() . "').masonry('layout'); });
				";
		
		return $output . $this->children_generate_js();
	}
	
	public function children_generate_js(){
		foreach ($this->get_widget()->get_children() as $subw){
			$output .= $this->get_template()->generate_js($subw) . "\n";
		};
		return $output;
	}
	
	public function generate_widgets_html(){
		foreach ($this->get_widget()->get_widgets() as $subw){
			$output .= $this->get_template()->generate_html($subw) . "\n";
		};
		return $output;
	}
	
	public function generate_widgets_js(){
		foreach ($this->get_widget()->get_widgets() as $subw){
			$output .= $this->get_template()->generate_js($subw) . "\n";
		};
		return $output;
	}
}
?>