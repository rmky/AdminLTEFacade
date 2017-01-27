<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Dialog;

/**
 * @method Dialog get_widget()
 * 
 * @author aka
 *
 */
class lteDialog extends lteForm {
	
	function generate_js(){
		$output = '';
		if (!$this->get_widget()->get_lazy_loading()){
			$output .= $this->build_js_for_widgets();
		}
		$output .= $this->build_js_buttons();
		return $output;
	}
	
	public function generate_html(){
		
		$output = <<<HTML
<div class="modal" id="{$this->get_id()}">
	<div class="modal-dialog" style="width:{$this->get_width()};">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{$this->get_widget()->get_caption()}</h4>
			</div>
			<div class="modal-body">
				<div class="modal-body-content-wrapper">
					{$this->build_html_for_widgets()}
				</div>
			</div>
			<div class="modal-footer">
				{$this->build_html_buttons()}
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
HTML;
		return $output;
	}
	
	function get_width(){
		if ($this->get_widget()->get_width()->is_undefined()){
			$this->get_widget()->set_width((2 * $this->get_width_relative_unit() + 35) . 'px');
		}
		return parent::get_width();
	}
}
?>