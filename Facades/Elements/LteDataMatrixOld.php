<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Factories\DataSheetFactory;

class LteDataMatrixOld extends lteDataTable
{

    private $label_values = array();

    /**
     *
     * @see \exface\AdminLTEFacade\Facades\Elements\LteAbstractElement::getWidget()
     * @return \exface\Core\Widgets\DataMatrix
     */
    public function getWidget()
    {
        return parent::getWidget();
    }

    function buildJs()
    {
        $output = '';
        return $output;
    }

    function buildHtml()
    {
        $rows_html = $this->buildJsDataSource();
        $headers = array();
        foreach ($this->getWidget()->getColumns() as $col) {
            if ($col->getId() == $this->getWidget()->getDataColumnId()) {
                $headers[1] .= '<th colspan="' . sizeof($this->label_values) . '">' . $this->getWidget()->getDataColumn()->getCaption() . '</th>';
                $headers[2] .= '<th>' . implode('</th><th>', $this->label_values) . '</th>';
            } elseif ($col->getId() == $this->getWidget()->getLabelColumnId()) {
                // Skip the label column
            } else {
                $headers[1] .= '<th rowspan="2">' . $col->getCaption() . '</th>';
            }
        }
        $headers_html = '<tr>' . implode('</tr><tr>', $headers) . '</tr>';
        $output = '<table id="' . $this->getId() . '" class="table table-bordered">
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
     *
     * @see \exface\Facades\jeasyui\Widgets\grid::buildJsDataSource()
     */
    public function buildJsDataSource($js_filters = '')
    {
        /* @var $widget \exface\Core\Widgets\DataMatrix */
        $widget = $this->getWidget();
        $visible_columns = array();
        $output = '';
        $result = array();
        
        // create data sheet to fetch data
        $ds = DataSheetFactory::createFromObject($this->getMetaObject());
        // add columns
        foreach ($widget->getColumns() as $col) {
            $ds->getColumns()->addFromExpression($col->getAttributeAlias(), $col->getDataColumnName(), $col->isHidden());
            if (! $col->isHidden())
                $visible_columns[] = $col->getDataColumnName();
        }
        // add the filters
        foreach ($widget->getFilters() as $fw) {
            if (! is_null($fw->getValue())) {
                $ds->getFilters()->addConditionFromString($fw->getAttributeAlias(), $fw->getValue());
            }
        }
        // add the sorters
        foreach ($widget->getSorters() as $sort) {
            $ds->getSorters()->addFromString($sort->getProperty('attribute_alias'), $sort->getProperty('direction'));
        }
        
        // get the data
        $ds->dataRead();
        $label_col = $widget->getLabelColumn();
        $data_col = $widget->getDataColumn();
        foreach ($ds->getRows() as $nr => $row) {
            $new_row_id = null;
            $new_row = array();
            $new_col_val = null;
            $new_col_id = null;
            foreach ($row as $fld => $val) {
                
                if ($fld === $label_col->getDataColumnName()) {
                    $new_col_id = $val;
                    // TODO we probably need a special parameter for sorting labels!
                    if (! in_array($val, $this->label_values))
                        $this->label_values[] = $val;
                } elseif ($fld === $data_col->getDataColumnName()) {
                    $new_col_val = $val;
                } elseif (in_array($fld, $visible_columns)) {
                    $new_row_id .= $val;
                    $new_row[$fld] = $val;
                }
            }
            if (! is_array($result[$new_row_id])) {
                $result[$new_row_id] = $new_row;
            }
            $result[$new_row_id][$new_col_id] = $new_col_val;
        }
        
        foreach ($result as $row) {
            $output .= '<tr>';
            foreach ($row as $fld => $val) {
                $output .= '<td class="' . $this->buildCssColumnClass($widget->getColumnByDataColumnName($fld) ? $widget->getColumnByDataColumnName($fld) : $widget->getDataColumn()) . '">' . $val . '</td>';
            }
            $output = substr($output, 0, - 1);
            $output .= '</tr>';
        }
        return $output;
    }
}
?>