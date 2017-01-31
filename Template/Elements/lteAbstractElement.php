<?php namespace exface\AdminLteTemplate\Template\Elements;

use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;
use exface\AdminLteTemplate\Template\AdminLteTemplate;

abstract class lteAbstractElement extends AbstractJqueryElement {
	
	public function build_js_init_options(){
		return '';
	}
	
	public function build_js_inline_editor_init(){
		return '';
	}
	
	public function build_js_busy_icon_show(){
		return '$("#' . $this->get_id() . '").parents(".box").append($(\'<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>\'));';
	}
	
	public function build_js_busy_icon_hide(){
		return '$("#' . $this->get_id() . '").parents(".box").find(".overlay").remove();';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_show_error_message()
	 */
	public function build_js_show_error_message($message_body_js, $title = null){
		return '
			swal({html:true, title:' . ($title ? $title : '"Error"') . ', text:' . $message_body_js . '});
			$(document).trigger("exface.AdminLteTemplate.SweetAlert.Complete");';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_show_success_message()
	 */
	public function build_js_show_success_message($message_body_js, $title = null){
		// TODO use some library to show toast notificactions
		return '';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::get_template()
	 * @return AdminLteTemplate
	 */
	public function get_template(){
		return parent::get_template();
	}
	
	public function escape_string($string){
		return htmlentities($string, ENT_QUOTES);
	}
	
	/**
	 * Returns the css classes, that define the grid width for the element (e.g. col-xs-12, etc.)
	 * @return string
	 */
	public function get_width_classes(){
		if ($this->get_widget()->get_width()->is_relative()){
			switch ($this->get_widget()->get_width()->get_value()){
				case 1: $width = 'col-xs-12 col-md-4'; break;
				case 2: $width = 'col-xs-12 col-md-8'; break;
				case 3: case 'max': $width = 'col-xs-12';
			}
		}
		return $width;
	}
	
	public function prepare_data(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet){
		// apply the formatters
		foreach ($data_sheet->get_columns() as $name => $col){
			if ($formatter = $col->get_formatter()) {
				$expr = $formatter->to_string();
				$function = substr($expr, 1, strpos($expr, '(')-1);
				// FIXME the next three lines seem obsolete... Not sure though, since everything works fine right now
				$formatter_class_name = 'formatters\'' . $function;
				if (class_exists($class_name)){
					$formatter = new $class_name($y);
				}
				// See if the formatter returned more results, than there were rows. If so, it was also performed on
				// the total rows. In this case, we need to slice them off and pass to set_column_values() separately.
				// This only works, because evaluating an expression cannot change the number of data rows! This justifies
				// the assumption, that any values after count_rows() must be total values.
				$vals = $formatter->evaluate($data_sheet, $name);
				if ($data_sheet->count_rows() < count($vals)) {
					$totals = array_slice($vals, $data_sheet->count_rows());
					$vals = array_slice($vals, 0, $data_sheet->count_rows());
				}
				$data_sheet->set_column_values($name, $vals, $totals);
			}
		}
		
		$data = array();
		$data['data'] = $data_sheet->get_rows();
		$data['recordsFiltered'] = $data_sheet->count_rows_all();
		$data['recordsTotal'] = $data_sheet->count_rows_all();
		$data['footer'] = $data_sheet->get_totals_rows();
		return $data;
	} 
}
?>