<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\CommonLogic\Model\DataTypes\AbstractDataType;
class lteEditMatrix extends lteDataMatrix {
	protected $element_type = 'datagrid';
	private $label_values = array();
	
	function generate_headers(){
		return array (
				'<script src="exface/vendor/exface/AdminLteTemplate/Template/js/handsontable/dist/jquery.handsontable.full.js"></script>',
				'<link rel="stylesheet" media="screen" href="exface/vendor/exface/AdminLteTemplate/Template/js/handsontable/dist/jquery.handsontable.full.css">'
				);
	}
	
	function generate_html(){
		$widget = $this->get_widget();
		$output = '';
		
		// add filters
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fltr){
				$fltr_html .= $this->get_template()->generate_html($fltr);
			}
		}
		
		// add buttons
		if ($widget->has_buttons()){
			foreach ($widget->get_buttons() as $button){
				$button_html .= $this->get_template()->generate_html($button);
			}
		}
		
		// create a container for the toolbar
		if ($widget->has_filters() || $widget->has_buttons()){
			$output .= '<div id="' . $this->get_toolbar_id() . '" class="datagrid-toolbar">';
			if ($fltr_html){
				$output .= $fltr_html . '
							    <a style="float:right;margin:2px;" href="#" class="easyui-linkbutton" iconCls="icon-search" onclick="' . $this->get_function_prefix() . 'doSearch()">Search</a>';
			}
			if ($button_html) {
				$output .= '<div>' . $button_html . '</div>';
			}
			$output .= '</div>';
		}
		// now the table itself
		$output .= '<div id="' . $this->get_id() . '"></div>';
		return $output;
	}
	
	function generate_js(){
		$output = '			
			$("#' . $this->get_id() . '").handsontable({
              ' . $this->render_grid_head() . '
            });
				';
		/*
		$widget = $this->get_widget();
		$output = '';
		
		if ($this->is_editable()){
			foreach ($this->get_editors() as $editor){
				$output .= $editor->generate_js_inline_editor_init();
			}
		}
		
		// get the standard params for grids
		$grid_head = $this->render_grid_head();
		
		// instantiate the data grid
		$output .= '$("#' . $this->get_id() . '").' . $this->get_element_type() . '({' . $grid_head . '});';
		*/
		return $output;
	}
	
	public function render_grid_head() {
		$widget = $this->get_widget();
		
		$output = $this->render_data_source()
				. ', columnSorting: true'
				. ', manualColumnResize: true'
				. ', manualColumnMove: true'
				. ($widget->get_width() ? ', width: ' . $widget->get_width() : '')
				. ($widget->get_caption() ? ', title: "' . $widget->get_caption() . '"' : '')
				. ', ' . $this->render_column_headers()
		;
		return $output;
	}
	
	/**
	 * This special column renderer for the matrix replaces the column specified by label_column_id with a set of new columns for
	 * every unique value in the column specified by data_column_id. The new columns retain most properties of the replaced label column.
	 * @see \exface\AdminLteTemplate\Template\Elements\grid::render_column_headers()
	 */
	public function render_column_headers(array $cols = null){
		$widget = $this->get_widget();
		$output = '';
		if (!$cols){
			$cols = $this->get_widget()->get_columns();
		}
		
		$headers = array();
		$columns = array();
		foreach ($cols as $col){
			if ($col->get_id() == $widget->get_label_column_id()){
				foreach ($this->label_values as $val){
					$headers[] = $val;
				}
			} elseif ($col->get_id() == $widget->get_data_column_id()){
				foreach ($this->label_values as $val){
					$columns[] = '{data: "' . $this->clean_id($val) . '", ' . $this->render_data_type($col->get_data_type()) . '}';
				}
			} else {
				$headers[] = $col->get_caption();
				$columns[] = '{data: "' . $col->get_id() . '", readOnly: true}';
			}
		}
		
		$output = '
				  colHeaders: ["' . implode('","', $headers) . '"]
				, columns: [' . implode(',', $columns) . ']
				';
		
		return $output;
	}
	
	/**
	 * This special data source renderer fetches data according to the filters an reorganizes the rows and column to fit the matrix.
	 * It basically transposes the data column (data_column_id) using values of the label column (label_column_id) as new column headers.
	 * The other columns remain untouched.
	 * @see \exface\AdminLteTemplate\Template\Elements\grid::render_data_source()
	 */
	public function render_data_source(){
		global $exface;
		$widget = $this->get_widget();
		$visible_columns = array();
		$output = '';
		$result = array();
	
		// create data sheet to fetch data
		$ds = $exface->data()->create_data_sheet($this->get_meta_object());
		// add columns
		foreach ($widget->get_columns() as $col){
			$ds->get_columns()->add_from_expression($col->get_attribute_alias(), $col->get_id(), $col->is_hidden());
			if (!$col->is_hidden()) $visible_columns[] = $col->get_id();
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
		$label_col = $widget->get_label_column_id();
		$data_col = $widget->get_data_column_id();
		$labels = array_unique($ds->get_column_data($label_col));
		foreach ($ds->get_rows() as $nr => $row){
			$new_row_id = null;
			$new_row = array();
			$new_col_val = null;
			$new_col_id = null;
			foreach ($row as $fld => $val){
				if ($fld === $label_col){
					$new_col_id = $val;
					// TODO we probably need a special parameter for sorting labels!
					if (!in_array($val, $this->label_values)) $this->label_values[] = $val;
				} elseif ($fld === $data_col){
					$new_col_val = $val;
				} elseif (in_array($fld, $visible_columns)) {
					$new_row_id .= $val;
					$new_row[$fld] = $val;
				}
			}
			
			if (is_array($result[$new_row_id])){
				$result[$new_row_id][$new_col_id] = $new_col_val;
			} else {
				$result[$new_row_id] = $new_row;
				// make sure the new row has cells for all label columns
				foreach ($labels as $label){
					if ($label){
						$result[$new_row_id][$label] = '';
					}
				}
			}
		}
	
		$output = "data: [";
		foreach ($result as $row){
			$output .= "{";
			foreach ($row as $fld => $val){
				$output .= '"' . $this->clean_id($fld) . '": "' . str_replace('"', '\"', $val) .'",';
			}
			$output = substr($output, 0, -1);
			$output .= '},';
		}
		$output = substr($output, 0, -1);
		$output .= ']';
		return $output;
	}
	
	public function render_data_type(AbstractDataType $data_type){
		if ($data_type->is(EXF_DATA_TYPE_BOOLEAN)) {
			return 'type: "checkbox"';
		} elseif ($data_type->is(EXF_DATA_TYPE_DATE)){
			return 'type: "date"';
		} elseif ($data_type->is(EXF_DATA_TYPE_NUMBER)){
			return 'type: "numeric"';
		} elseif ($data_type->is(EXF_DATA_TYPE_PRICE)){ 
			return 'type: "numeric"';
		} else {
			return 'type: "text"';
		}
	}
}
?>