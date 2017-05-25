<?php

namespace exface\AdminLteTemplate\Template;

use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;

class AdminLteTemplate extends AbstractAjaxTemplate
{

    protected $request_columns = array();

    public function init()
    {
        parent::init();
        $this->setClassPrefix('lte');
        $this->setClassNamespace(__NAMESPACE__);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate::processRequest($page_id=NULL, $widget_id=NULL, $action_alias=NULL, $disable_error_handling=false)
     */
    public function processRequest($page_id = NULL, $widget_id = NULL, $action_alias = NULL, $disable_error_handling = false)
    {
        $this->request_columns = $this->getWorkbench()->getRequestParams()['columns'];
        $this->getWorkbench()->removeRequestParam('columns');
        $this->getWorkbench()->removeRequestParam('search');
        $this->getWorkbench()->removeRequestParam('draw');
        $this->getWorkbench()->removeRequestParam('_');
        return parent::processRequest($page_id, $widget_id, $action_alias, $disable_error_handling);
    }

    public function getRequestPagingOffset()
    {
        if (! $this->request_paging_offset) {
            $this->request_paging_offset = $this->getWorkbench()->getRequestParams()['start'];
            $this->getWorkbench()->removeRequestParam('start');
        }
        return $this->request_paging_offset;
    }

    public function getRequestPagingRows()
    {
        if (! $this->request_paging_rows) {
            $this->request_paging_rows = $this->getWorkbench()->getRequestParams()['length'];
            $this->getWorkbench()->removeRequestParam('length');
        }
        return $this->request_paging_rows;
    }

    public function getRequestSortingDirection()
    {
        if (! $this->request_sorting_direction) {
            $this->getRequestSortingSortBy();
        }
        return $this->request_sorting_direction;
    }

    public function getRequestSortingSortBy()
    {
        if (! $this->request_sorting_sort_by) {
            $sorters = ! is_null($this->getWorkbench()->getRequestParams()['order']) ? $this->getWorkbench()->getRequestParams()['order'] : array();
            $this->getWorkbench()->removeRequestParam('order');
            
            foreach ($sorters as $sorter) {
                if (! is_null($sorter['column'])) { // sonst wird nicht nach der 0. Spalte sortiert (0 == false)
                    if ($sort_attr = $this->request_columns[$sorter['column']]['data']) {
                        $this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sort_attr;
                        $this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
                    }
                } elseif ($sorter['attribute_alias']) {
                    $this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sorter['attribute_alias'];
                    $this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
                }
            }
        }
        return $this->request_sorting_sort_by;
    }
}
?>