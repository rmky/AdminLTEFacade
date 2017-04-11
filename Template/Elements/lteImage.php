<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Image;

/**
 * @method Image get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteImage extends lteText {
	
	function generate_html(){
		$style = '';
		if (!$this->get_widget()->get_width()->is_undefined()){
			$width = ' width="' . $this->get_width() . '"';
		}
		if (!$this->get_widget()->get_height()->is_undefined()){
			$height = ' height="' . $this->get_height() . '"';
		}
		
		switch ($this->get_widget()->get_align()){
			case EXF_ALIGN_CENTER: $style .= 'margin-left: auto; margin-right: auto;'; break;
			case EXF_ALIGN_RIGHT: $style .= 'float: right';
		}
		
		$output = '<img src="' . $this->get_widget()->get_uri() . '"' . $width . $height . ' class="img-responsive" style="' . $style  . '" />';
		return $output;
	}
	
	function generate_js(){
		return '';
	}
}
?>