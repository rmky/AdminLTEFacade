<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteText extends lteAbstractElement {
	
	function init(){
	
	}
	
	function generate_html(){
		$output = '';
		$widget = $this->get_widget();
		$html = $widget->get_text();
		
		switch ($widget->get_size()){
			case EXF_TEXT_SIZE_BIG: $html = '<big>' . $html . '</big>'; break;
			case EXF_TEXT_SIZE_SMALL: $html = '<small>' . $html . '</small>'; break;
		}
			
		switch ($widget->get_style()){
			case EXF_TEXT_STYLE_BOLD: $html = '<strong>' . $html . '</strong>'; break;
			case EXF_TEXT_STYLE_UNDERLINE: $html = '<ins>' . $html . '</ins>'; break;
			case EXF_TEXT_STYLE_STRIKETHROUGH: $html = '<del>' . $html . '</del>'; break;
		}
			
		$style = '';
		switch ($widget->get_align()){
			case 'left': $style .= 'text-align: left;'; break;
			case 'right': $style .= 'text-align: right;'; break;
			case 'center': $style .= 'text-align: center;'; break;
		}
		
		if ($this->get_widget()->get_caption() && !$this->get_widget()->get_hide_caption()){
			$output .= '<label for="' . $this->get_id() . '" class="exf-text-label">' . $this->get_widget()->get_caption() . '</label>';
		}
		
		if (!trim($html) && !$this->get_widget()->get_empty_text()) {
			$html = $this->translate('WIDGET.TEXT.EMPTY_TEXT');
		}
		
		$output .= '<p id="' . $this->get_id() . '" class="exf-text-content" style="' . $style . '">' . $html . '</p>';
		return $this->build_html_wrapper($output);
	}
	
	public function build_html_wrapper($inner_html){
		$output = '
					<div class="exf_grid_item ' . $this->get_width_classes() . '" title="' . $this->build_hint_text() . '">
							' . $inner_html . '
					</div>';
		return $output;
	}
	
	function generate_js(){
		return '';
	}
	
}
?>