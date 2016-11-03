<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteDialog extends ltePanel {
	
	function generate_js(){
		$output = '';
		if (!$this->get_widget()->get_lazy_loading()){
			$output .= $this->build_js_for_widgets();
		}
		$output .= $this->generate_buttons_js();
		return $output;
	}
	
	public function generate_html(){
		/* @var $widget \exface\Core\Widgets\Dialog */
		$widget = $this->get_widget();
		
		$output = <<<HTML
<div class="modal" id="{$this->get_id()}">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{$widget->get_caption()}</h4>
			</div>
			<div class="modal-body">
				{$this->build_html_for_widgets()}
			</div>
			<div class="modal-footer">
				{$this->generate_buttons_html()}
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
HTML;
		return $output;
	}
}
?>