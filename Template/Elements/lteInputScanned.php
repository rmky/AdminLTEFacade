<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputScanned extends lteAbstractElement {
	protected $element_type = 'scaninputbox';
	
	public function generate_html() {		
		$output = '	<div class="fitem exf_input">
						<label>' . $this->get_widget()->get_caption() . '</label>
						<input class="easyui-validatebox"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . addslashes($this->get_widget()->get_value()) . '"
								id="' . $this->get_id() . '"
								' . ($this->get_widget()->is_required() ? 'required="true" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled" ' : '') . 
								($this->get_widget()->get_blur_after_scan() ? 'onkeydown="function(e){if (e.which==' . $this->get_widget()->get_end_scan_character() . ') {this.blur(); return false;}}"' : '') .
								'/>
					</div>';
		return $output;
	}
	
	function generate_js(){
		
	}
	
	function build_js_inline_editor_init(){
		if (!$this->get_widget()->get_blur_after_scan()) return '';
		$output = "
					$.extend($.fn.datagrid.defaults.editors, {
					    scaninputbox: {
					        init: function(container, options){
					            var input = $('<input type=\"text\">').appendTo(container);
								input.addClass('easyui-validatebox');
								input.on('keydown', function(e){if (e.which==" . $this->get_widget()->get_end_scan_character() . ") {this.blur(); return false;}});
					            return input;
					        },
					        destroy: function(target){
					            $(target).remove();
					        },
					        getValue: function(target){
					            return $(target).val();
					        },
					        setValue: function(target, value){
					            $(target).val(value);
					        },
					        resize: function(target, width){
					            $(target).width(width);
					        }
					    }
					});";
		return $output;
	}
}