<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteDataMatrix extends lteDataTable {
	private $label_values = array();
	
	/**
	 * @see \exface\AdminLteTemplate\Template\Elements\jqmAbstractElement::get_widget()
	 * @return \exface\Core\Widgets\DataMatrix
	 */
	public function get_widget(){
		return parent::get_widget();
	}
	
	function generate_js(){
		$output = '';		
		return $output;
	}
	
	function generate_html(){
		$rows_html = $this->render_data_source();
		$headers = array();
		foreach ($this->get_widget()->get_columns() as $col){
			if ($col->get_id() == $this->get_widget()->get_data_column_id()){
				$headers[1] .= '<th colspan="' . sizeof($this->label_values) . '">' . $this->get_widget()->get_data_column()->get_caption() . '</th>';
				$headers[2] .= '<th>' . implode('</th><th>', $this->label_values) . '</th>';
				
			} elseif ($col->get_id() == $this->get_widget()->get_label_column_id()) {
				// Skip the label column
			} else {
				$headers[1] .= '<th rowspan="2">' . $col->get_caption() . '</th>';
			}
		}
		$headers_html = '<tr>' . implode('</tr><tr>', $headers) . '</tr>';
		$output = '<table id="' . $this->get_id() . '" class="table table-bordered">
					<thead>
						<tr>
							' . $headers_html . '
						</tr>
					</thead>
					<tbody>
						' . $rows_html . '
					</tbody>
				</table>';	
		return $output;
	}
	
	/**
	 * This special data source renderer fetches data according to the filters an reorganizes the rows and column to fit the matrix.
	 * It basically transposes the data column (data_column_id) using values of the label column (label_column_id) as new column headers.
	 * The other columns remain untouched.
	 * @see \exface\Templates\jeasyui\Widgets\grid::render_data_source()
	 */
	public function render_data_source(){
		global $exface;
		/* @var $widget \exface\Core\Widgets\DataMatrix */
		$widget = $this->get_widget();
		$visible_columns = array();
		$output = '';
		$result = array();
				
		// create data sheet to fetch data
		$ds = $exface->data()->create_data_sheet($this->get_meta_object());
		// add columns
		foreach ($widget->get_columns() as $col){
			$ds->get_columns()->add_from_expression($col->get_attribute_alias(), $col->get_data_column_name(), $col->is_hidden());
			if (!$col->is_hidden()) $visible_columns[] = $col->get_data_column_name();
		}
		// add the filters
		foreach ($widget->get_filters() as $fw){
			if (!is_null($fw->get_value())){
				$ds->add_filter_from_string($fw->get_attribute_alias(), $fw->get_value());
			}
		}
		// add the sorters
		foreach ($widget->get_sorters() as $sort){
			$ds->get_sorters()->add_from_string($sort->attribute_alias, $sort->direction);
		}
		
		// get the data
		$ds->data_read();
		$label_col = $widget->get_label_column();
		$data_col = $widget->get_data_column();
		foreach ($ds->get_rows() as $nr => $row){
			$new_row_id = null;
			$new_row = array();
			$new_col_val = null;
			$new_col_id = null;
			foreach ($row as $fld => $val){
				
				if ($fld === $label_col->get_data_column_name()){
					$new_col_id = $val;
					// TODO we probably need a special parameter for sorting labels!
					if (!in_array($val, $this->label_values)) $this->label_values[] = $val;
				} elseif ($fld === $data_col->get_data_column_name()){
					$new_col_val = $val; 
				} elseif (in_array($fld, $visible_columns)) {
					$new_row_id .= $val;
					$new_row[$fld] = $val;
				}
			}
			if (!is_array($result[$new_row_id])){
				$result[$new_row_id] = $new_row;
			}
			$result[$new_row_id][$new_col_id] = $new_col_val;
		}
		
		foreach ($result as $row){
			$output .= '<tr>';
			foreach ($row as $fld => $val){
				$output .= '<td class="' . $this->get_css_column_class($widget->get_column($fld) ? $widget->get_column($fld) : $widget->get_data_column()) . '">' . $val .'</td>';
			}
			$output = substr($output, 0, -1);
			$output .= '</tr>';
		}
		return $output;
	}
}
?>