<?php
namespace exface\AdminLteTemplate\Template\Elements;
class ltePanel extends lteContainer {
	
	function generate_html(){
		// Does the box need a header?
		$header = '';
		if ($this->get_widget()->get_caption()){
			$header .= '<h3 class="box-title">' . $this->get_widget()->get_caption() . '</h3>';
		}
		if ($header){
			$header = '<div class="box-header">' . $header . '</div>';
		}
		
		// Does the box need a footer (for buttons)?
		if ($buttons_html = $this->generate_buttons_html()){
			$footer = '	<div class="box-footer clearfix">' . $buttons_html . '</div>';
		}
		
		$output = <<<HTML
<div class="box">
	{$header}
	<div class="box-body"> 
		<div class="row" id="{$this->get_id()}">
			{$this->generate_widgets_html()}
			<div class="col-xs-1" id="{$this->get_id()}_sizer" style=""></div>
		</div>
	</div>
	{$footer}
</div>
HTML;
		return $output;
	}
	
	function generate_buttons_html(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_html($btn);
		}
		
		return $output;
	}
	
	function generate_buttons_js(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_js($btn);
		}
	
		return $output;
	}
}
?>