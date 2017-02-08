<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteBox extends lteForm {
	
	public function generate_html(){
		$output = <<<HTML
<div class="{$this->get_width_classes()} exf_grid_item">
	{$this->build_html_box()}
</div>
HTML;
		return $output;
	}
	
	protected function build_html_box(){
		// Does the box need a header?
		$header = '';
		if ($this->get_widget()->get_caption()){
			$header .= '<h3 class="box-title">' . $this->get_widget()->get_caption() . '</h3>';
		}
		if ($header){
			$header = '<div class="box-header">' . $header . '</div>';
		}
	
		// Does the box need a footer (for buttons)?
		if ($buttons_html = $this->build_html_buttons()){
			$footer = '	<div class="box-footer clearfix">' . $buttons_html . '</div>';
		}
	
		$output = <<<HTML
<div class="box">
	{$header}
	<div class="box-body">
		<div class="row" id="{$this->get_id()}">
			{$this->build_html_for_widgets()}
			<div class="col-xs-1" id="{$this->get_id()}_sizer" style=""></div>
		</div>
	</div>
	{$footer}
</div>
HTML;
		return $output;
	}
}
?>