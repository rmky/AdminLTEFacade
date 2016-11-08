<?php
namespace exface\AdminLteTemplate\Template\Elements;
/**
 * TODO Make propertygrid compatible to the new template. Right now unusable!!!
 * @author Andrej Kabachnik
 *
 */
class ltePropertyTable extends lteData {
	
	function draw (){
		$widget = $this->widget;
		$mm_object = $this->mm_object;
		$mode = $this->mode;
		
		switch ($mode){
			case 'html' :
				$output = '<table id="' . $widget->get_id() . '"></table>';
				if (is_array($widget->widget_options->filters)){
	
					foreach ($widget->widget_options->filters as $fltr){
						$fltr_html .= '<div class="exf_input"><label>' . $fltr->get_caption() . '</label><input id="fltr_' . $fltr->attribute_alias . '" class="easyui-validatebox"></div>';
	
					}
	
					$output .= '<div id="' . $toolbar . '">
    							' . $fltr_html . '
							    <a style="float:right" href="#" class="easyui-linkbutton" iconCls="icon-search" onclick="' . $widget->get_id() . '_doSearch()">Search</a>
							</div>';
				}
				break;
	
			case 'js' :
				$cols = $widget->get_columns();
				if (is_array($cols)){
					$header_rows = array();
	
					if (!is_array($cols[0])){
						$cols = array($cols);
					}
	
					foreach ($cols as $rownr => $row){
						$columns = array();
						foreach ($row as $col){
							$columns[] = $this->render_grid_column($col, $mm_object, 'js');
							if ($col->footer) $widget->widget_options->showFooter = 'true';
						}
						$header_rows[] = '[' . implode(',', $columns) . ']';
	
					}
					$header_rows = ', columns: [ ' . implode(',', $header_rows) . ' ]';
				}
	
				$output = '$("#' . $widget->get_id() . '").propertygrid({';
				$output .= $this->render_grid_head($widget->get_page_id(), $widget, $mm_object, 'js');
				if ($toolbar) $output .= ', toolbar: "#' . $toolbar . '"';
	
				//$output .= $header_rows;
				$output .= '});';
	
				$output = "$(function(){ \n" . $output . "\n });";
	
				break;
	
			default: $output = false; break;
		}
	
		return $output;
	}
}
?>