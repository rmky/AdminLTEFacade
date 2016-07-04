<?php namespace exface\AdminLteTemplate\Template;

use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;

class AdminLteTemplate extends AbstractAjaxTemplate {
	protected $request_columns = array();
	
	public function init(){
		$this->set_class_prefix('lte');
		$this->set_class_namespace(__NAMESPACE__);
	}
	
	public function process_request($resource_id=NULL, $widget_id=NULL, $action_alias=NULL){
		$this->request_columns = $this->exface()->get_request_params()['columns'];
		$this->exface()->remove_request_param('columns');
		$this->exface()->remove_request_param('search');
		$this->exface()->remove_request_param('draw');
		$this->exface()->remove_request_param('_');
		return parent::process_request($resource_id, $widget_id, $action_alias);
	}
	
	public function get_request_paging_offset(){
		if (!$this->request_paging_offset){
			$this->request_paging_offset = $this->exface()->get_request_params()['start'];
			$this->exface()->remove_request_param('start');
		}
		return $this->request_paging_offset;
	}
	
	public function get_request_paging_rows(){
		if (!$this->request_paging_rows){
			$this->request_paging_rows = $this->exface()->get_request_params()['length'];
			$this->exface()->remove_request_param('length');
		}
		return $this->request_paging_rows;
	}
	
	public function get_request_sorting_direction(){
		if (!$this->request_sorting_direction){
			$this->get_request_sorting_sort_by();
		}
		return $this->request_sorting_direction;
	}
	
	public function get_request_sorting_sort_by(){
		if (!$this->request_sorting_sort_by){
			$sorters = !is_null($this->exface()->get_request_params()['order']) ? $this->exface()->get_request_params()['order'] : array();
			$this->exface()->remove_request_param('order');

			foreach ($sorters as $sorter){
				if ($sorter['column']){
					if ($sort_attr = $this->request_columns[$sorter['column']]['data']){
						$this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sort_attr;
						$this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
					}
				} elseif ($sorter['attribute_alias']){
					$this->request_sorting_sort_by .= ($this->request_sorting_sort_by ? ',' : '') . $sorter['attribute_alias'];
					$this->request_sorting_direction .= ($this->request_sorting_direction ? ',' : '') . $sorter['dir'];
				}
			}
		}
		return $this->request_sorting_sort_by;
	}
}
?>