<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputNumber extends lteInput {
	
	protected function init(){
		parent::init();
		$this->set_element_type('number');
	}
}