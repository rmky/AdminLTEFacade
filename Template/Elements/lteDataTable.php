<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Dashboard;
use exface\Core\Widgets\Tab;

/**
 * 
 * @method DataTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteDataTable extends lteAbstractElement {
	private $on_load_success = '';
	private $row_details_expand_icon = 'fa-plus-square-o';
	private $row_details_collapse_icon = 'fa-minus-square-o';
	private $editable = false;
	private $editors = array();
	
	function generate_html(){
		$widget = $this->get_widget();
		$thead = '';
		$tfoot = '';
		
		// Column headers
		/* @var $col \exface\Core\Widgets\DataColumn */
		foreach ($widget->get_columns() as $col) {
			$thead .= '<th>' . $col->get_caption() . '</th>';
			if ($widget->has_footer()){
				$tfoot .= '<th class="text-right"></th>';
			}
		}
		
		// Extra column for the multiselect-checkbox
		if ($widget->get_multi_select()){
			$checkbox_header = '<th onclick="javascript: if(!$(this).parent().hasClass(\'selected\')) {' . $this->get_id() . '_table.rows().select(); $(\'#' . $this->get_id() . '_wrapper\').find(\'th.select-checkbox\').parent().addClass(\'selected\');} else{' . $this->get_id() . '_table.rows().deselect(); $(\'#' . $this->get_id() . '_wrapper\').find(\'th.select-checkbox\').parent().removeClass(\'selected\');}"></th>';
			$thead = $checkbox_header . $thead;
			if ($tfoot){
				$tfoot = $checkbox_header . $tfoot;
			}
		}
		
		// Extra column for expand-button if rows have details
		if ($widget->has_row_details()){
			$thead = '<th></th>' . $thead;
			if ($tfoot){
				$tfoot = '<th></th>' . $tfoot;
			}
		}
		
		if ($tfoot) $tfoot = '<tfoot>' . $tfoot . '</tfoot>';
		
		// Add promoted filters above the panel. Other filters will be displayed in a popup via JS
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fltr){
				if ($fltr->get_visibility() !== EXF_WIDGET_VISIBILITY_PROMOTED) continue;
				$filters_html .= $this->get_template()->generate_html($fltr);
			}
		}
		
		// add buttons
		/* @var $more_buttons_menu \exface\Core\Widgets\MenuButton */
		$more_buttons_menu = null;
		if ($widget->has_buttons()){
			foreach ($widget->get_buttons() as $button){
				// Make pomoted and regular buttons visible right in the bottom toolbar
				// Hidden buttons also go here, because it does not make sense to put them into the menu
				if ($button->get_visibility() !== EXF_WIDGET_VISIBILITY_OPTIONAL || $button->is_hidden()){
					$button_html .= $this->get_template()->generate_html($button);
				} 
				// Put all visible buttons into "more actions" menu
				// TODO do not create the more actions menu if all buttons are promoted!
				if ($button->get_visibility() == EXF_WIDGET_VISIBILITY_OPTIONAL && !$button->is_hidden()){
					if (!$more_buttons_menu){
						$more_buttons_menu = $widget->get_page()->create_widget('MenuButton', $widget);
						$more_buttons_menu->set_icon_name('more');
						$more_buttons_menu->set_caption('');
					}
					$more_buttons_menu->add_button($button);
				}
			}
		}

		if ($more_buttons_menu){
			$button_html .= $this->get_template()->get_element($more_buttons_menu)->generate_html();
		}
		$footer_style = $widget->get_hide_toolbar_bottom() ? 'display: none;' : '';
		$bottom_toolbar = $this->build_html_bottom_toolbar($button_html);
		$top_toolbar = $this->build_html_top_toolbar();
		
		// output the html code
		// TODO replace "stripe" class by a custom css class
		$output = <<<HTML
	<div class="box-header">
		{$top_toolbar}
	</div><!-- /.box-header -->
	<div class="box-body no-padding">
		<table id="{$this->get_id()}" class="table table-striped table-hover" cellspacing="0" width="100%">
			<thead>
				{$thead}
			</thead>
			{$tfoot}
		</table>
	</div>
	<div class="box-footer clearfix" style="padding-top: 0px; {$footer_style}">
		<div class="row">
			{$bottom_toolbar}
		</div>
	</div>
	{$this->build_html_table_customizer()}
HTML;
		
		return $this->build_html_wrapper($output);
	}
	
	protected function build_html_wrapper($html){
		$result = $html;
		if (!$this->get_widget()->get_parent() || $this->get_widget()->get_parent() instanceof Dashboard){
			$result = '<div class="box">' . $result . '</div>';
		}
		return '<div class="' . $this->get_width_classes() .' exf_grid_item">' . $result . '</div>';
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\DataTable */
		$widget = $this->get_widget();
		$columns = array();
		$column_number_offset = 0;
		$filters_html = '';
		$filters_js = '';
		$filters_ajax = "d.q = $('#" . $this->get_id() . "_quickSearch').val();\n";
		$buttons_js = '';
		$context_menu_js = '';
		$footer_callback = '';
		$default_sorters = '';
		
		
		// Multiselect-Checkbox
		if ($widget->get_multi_select()){
			$columns[] = '
					{
						"className": "select-checkbox",
						"width": "10px",
						"orderable": false,
						"data": null,
						"targets": 0,
						"defaultContent": ""
					}
					';
			$column_number_offset++;
		}
		
		// Expand-Button for row details
		if ($widget->has_row_details()){
			$columns[] = '
					{
						"class": "details-control text-center",
						"width": "10px",
						"orderable": false,
						"data": null,
						"defaultContent": \'<i class="fa ' . $this->row_details_expand_icon . '"></i>\'
					}
					';
			$column_number_offset++;
		}
		
		foreach ($widget->get_sorters() as $sorter){
			$column_exists = false;
			foreach ($widget->get_columns() as $nr => $col){
				if ($col->get_attribute_alias() == $sorter->attribute_alias){
					$column_exists = true;
					$default_sorters .= '[ ' . ($nr+$column_number_offset) . ', "' . $sorter->direction . '" ], ';
				}
			}
			if (!$column_exists){
				// TODO add a hidden column
			}
		}
		// Remove tailing comma
		if ($default_sorters) $default_sorters = substr($default_sorters, 0, -2);
		
		// columns
		foreach ($widget->get_columns() as $nr => $col){
			$columns[] = $this->build_js_column_def($col);
			$nr = $nr + $column_number_offset;
			if ($col->get_footer()){
				$footer_callback .= <<<JS
	            // Total over all pages
	            if (api.ajax.json().footer[0]['{$col->get_data_column_name()}']){
		            total = api.ajax.json().footer[0]['{$col->get_data_column_name()}'];
		            // Update footer
		            $( api.column( {$nr} ).footer() ).html( total );
	           	}
JS;
			}
		}
		$columns = implode(', ', $columns);
		if ($footer_callback){
			$footer_callback = '
				, "footerCallback": function ( row, data, start, end, display ) {
					var api = this.api(), data;
	 
		            // Remove the formatting to get integer data for summation
		            var intVal = function ( i ) {
		                return typeof i === \'string\' ?
		                    i.replace(/[\$,]/g, \'\')*1 :
		                    typeof i === \'number\' ?
		                        i : 0;
		            };
					' . $footer_callback . '
				}';
		}
		
		// Filters defined in the UXON description
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fnr => $fltr){
				// Skip promoted filters, as they are displayed next to quick search
				if ($fltr->get_visibility() == EXF_WIDGET_VISIBILITY_PROMOTED) continue;
				$fltr_element = $this->get_template()->get_element($fltr);
				$filters_js .= $this->get_template()->generate_js($fltr);
				$filters_ajax .= 'd.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = ' . $fltr_element->build_js_value_getter() . ";\n";
				$filters_ajax .= 'if(d.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ') filtersOn = true;' . "\n";
				
				// Here we generate some JS make the filter visible by default, once it gets used.
				// This code will be called when the table's config page gets closed.
				if (!$fltr->is_hidden()){
					$filters_js_promoted .= "
							if (" . $fltr_element->build_js_value_getter() . " && $('#" . $fltr_element->get_id() . "').parents('#{$this->get_id()}_popup_config').length > 0){
								var fltr = $('#" . $fltr_element->get_id() . "').parents('.exf_input');
								var ui_block = $('<div class=\"col-xs-12 col-sm-6 col-md-4 col-lg-3\"></div>').appendTo('#{$this->get_id()}_filters_container');
								fltr.detach().appendTo(ui_block).trigger('resize');
								$('#{$this->get_id()}_filters_container').show();
							}
					";
					/*$filters_js_promoted .= "
							if (" . $fltr_element->build_js_value_getter() . "){
								var fltr = $('#" . $fltr_element->get_id() . "').parents('.exf_input');
								var ui_block = $('<div></div>');
								if ($('#{$this->get_id()}_filters_container').children('div').length % 2 == 0){
									ui_block.addClass('ui-block-a');
								} else {
									ui_block.addClass('ui-block-b');
								}
								ui_block.appendTo('#{$this->get_id()}_filters_container');
								fltr.detach().appendTo(ui_block);
								fltr.addClass('ui-field-contain');
							}
							";*/
				}
			}
		}
		
		// buttons
		if ($widget->has_buttons()){
			foreach ($widget->get_buttons() as $button){
				/* @var $btn_element \exface\AdminLteTemplate\lteButton */
				$btn_element = $this->get_template()->get_element($button);
				$buttons_js .= $btn_element->generate_js();
				if (!$button->is_hidden() && (!$button->get_action() || $button->get_action()->get_input_rows_min() === 1)){
					$icon = ($button->get_icon_name() ? '<i class=\'' . $btn_element->build_css_icon_class($button->get_icon_name()) . '\'></i> ' : '');
					$context_menu_js .= '{text: "' . $icon . $button->get_caption() . '", action: function(e){e.preventDefault(); ' . $btn_element->build_js_click_function_name() . '();}}, ';
				}
			}
			$context_menu_js = $context_menu_js ? substr($context_menu_js, 0, -2) : $context_menu_js;
		}
		
		// Click actions
		// Single click. Currently only supports one double click action - the first one in the list of buttons
		if ($leftclick_button = $widget->get_buttons_bound_to_mouse_action(EXF_MOUSE_ACTION_LEFT_CLICK)[0]){
			$leftclick_script = $this->get_template()->get_element($leftclick_button)->build_js_click_function_name() .  '()';
		}
		// Double click. Currently only supports one double click action - the first one in the list of buttons
		if ($dblclick_button = $widget->get_buttons_bound_to_mouse_action(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]){
			$dblclick_script = $this->get_template()->get_element($dblclick_button)->build_js_click_function_name() .  '()';
		}
		
		// Double click. Currently only supports one double click action - the first one in the list of buttons
		if ($rightclick_button = $widget->get_buttons_bound_to_mouse_action(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]){
			$rightclick_script = $this->get_template()->get_element($rightclick_button)->build_js_click_function_name() .  '()';
		}
		
		// Selection
		if ($this->get_widget()->get_multi_select()){
			$select_options = 'style: "multi"';
			if ($this->get_widget()->get_multi_select_all_selected()){
				$initial_row_selection = $this->get_id() . '_table.rows().select(); $(\'#' . $this->get_id() . '_wrapper\').find(\'th.select-checkbox\').parent().addClass(\'selected\');'; 
			}
		} else {
			$select_options = 'style: "single"';
		}
		
		// configure pagination
		if ($widget->get_paginate()){
			$paging_options = '"pageLength": ' . $widget->get_paginate_default_page_size() . ','; 
		} else {
			$paging_options = '"paging": false,';
		}
		
		$output = <<<JS
var {$this->get_id()}_table;
$.fn.dataTable.ext.errMode = 'throw';

{$this->build_js_function_prefix()}Init();

function {$this->build_js_function_prefix()}Init(){
	
	if ({$this->get_id()}_table && $.fn.DataTable.isDataTable( '#{$this->get_id()}' )) {
		{$this->get_id()}_table.columns.adjust();
		return;
	}	
	
	$('#{$this->get_id()}_popup_columns input').click(function(){
		setColumnVisibility(this.name, (this.checked ? true : false) );
	});
	
	{$this->get_id()}_table = $('#{$this->get_id()}').DataTable( {
		"dom": 't',
		"deferRender": true,
		"processing": true,
		"select": { {$select_options} },
		{$paging_options}
		"scrollX": true,
		"scrollXollapse": true,
		{$this->build_js_data_source($filters_ajax)}
		"language": {
            "zeroRecords": "{$widget->get_empty_text()}"
        },
		"columns": [{$columns}],
		"order": [ {$default_sorters} ],
		"drawCallback": function(settings, json) {
			$('#{$this->get_id()} tbody tr').on('contextmenu', function(e){
				{$this->get_id()}_table.row($(e.target).closest('tr')).select();
			});
			$('#{$this->get_id()}').closest('.exf_grid_item').trigger('resize');
			context.attach('#{$this->get_id()} tbody tr', [{$context_menu_js}]);
			if({$this->get_id()}_table){
				{$this->get_id()}_drawPagination();
				{$this->get_id()}_table.columns.adjust();
			}
			{$this->build_js_disable_text_selection()}
			{$this->build_js_busy_icon_hide()}
		}
		{$footer_callback}
	} );
	
	$('#{$this->get_id()} tbody').on( 'click', 'tr', function () {
		{$leftclick_script}
    } );
    
    $('#{$this->get_id()} tbody').on( 'dblclick', 'tr', function(e){
		{$dblclick_script}
	});
	
	$('#{$this->get_id()} tbody').on( 'rightclick', 'tr', function(e){
		{$rightclick_script}
	});
	
	{$initial_row_selection}
	
	{$this->build_js_pagination()}
	
	{$this->build_js_quicksearch()}
	
	{$this->build_js_row_details()}
	
	{$this->build_js_fixes()}
	
	$('#{$this->get_id()}_popup_columnList').sortable();
	
	context.init({preventDoubleContext: false});
}
	
function setColumnVisibility(name, visible){
	{$this->get_id()}_table.column(name+':name').visible(visible);
	$('#columnToggle_'+name).attr("checked", visible);
	try {
		$('#columnToggle_'+name).checkboxradio('refresh');
	} catch (ex) {}
}

function {$this->get_id()}_drawPagination(){
	var pages = {$this->get_id()}_table.page.info();
	if (pages.page == 0) {
		$('#{$this->get_id()}_prevPage').attr('disabled', 'disabled');
	} else {
		$('#{$this->get_id()}_prevPage').attr('disabled', false);
	}
	if (pages.page == pages.pages-1 || pages.end == pages.recordsDisplay) {
		$('#{$this->get_id()}_nextPage').attr('disabled', 'disabled');
	} else {
		$('#{$this->get_id()}_nextPage').attr('disabled', false);	
	}
	$('#{$this->get_id()}_pageInfo').html(pages.page*pages.length+1 + ' - ' + (pages.recordsDisplay < (pages.page+1)*pages.length || pages.end == pages.recordsDisplay ? pages.recordsDisplay : (pages.page+1)*pages.length) + ' / ' + pages.recordsDisplay);
	
}

function {$this->get_id()}_refreshPromotedFilters(){
	{$filters_js_promoted}
}

$('#{$this->get_id()}_popup_config').on('hidden.bs.modal', function(e) {
	{$this->get_id()}_refreshPromotedFilters();
});


{$filters_js}

{$buttons_js}

JS;
		
		return $output;
	}
	
	protected function build_js_data_source($js_filters = ''){
		$widget = $this->get_widget();
		
		$ajax_data = <<<JS
			function ( d ) {
				{$this->build_js_busy_icon_show()}
				var filtersOn = false;
				d.action = '{$widget->get_lazy_loading_action()}';
				d.resource = "{$this->get_page_id()}";
				d.element = "{$widget->get_id()}";
				d.object = "{$this->get_widget()->get_meta_object()->get_id()}";
				{$js_filters}
				
				if (filtersOn){
					$('#{$this->get_id()}_quickSearch_form .btn-advanced-filtering').removeClass('btn-default').addClass('btn-info');
					//$('#{$this->get_id()}_quickSearch_form .filter-labels').append('<span class="label label-info">Primary</span>');
				} else {
					$('#{$this->get_id()}_quickSearch_form .btn-advanced-filtering').removeClass('btn-info').addClass('btn-default');
					//$('#{$this->get_id()}_quickSearch_form .filter-labels').empty();
				}
			}
JS;
		
		$result = '';
		if ($this->get_widget()->get_lazy_loading()){
			$result = <<<JS
		"serverSide": true,
		"ajax": {
			"url": "{$this->get_ajax_url()}",
			"type": "POST",
			"data": {$ajax_data},
			"error": function(jqXHR, textStatus, errorThrown ){
				{$this->build_js_busy_icon_hide()}
				{$this->build_js_show_error('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
			}
		}
JS;
		} else {
			// Data embedded in the code of the DataGrid
			if ($widget->get_prefill_data() && $widget->get_prefill_data()->get_meta_object()->is($widget->get_meta_object())){
				$data = $widget->prepare_data_sheet_to_read($widget->get_prefill_data());
			} else {
				$data = $widget->prepare_data_sheet_to_read();
			}
			if (!$data->is_fresh()){
				$data->data_read();
			}
			$result = <<<JS
			"ajax": function (data, callback, settings) {
				callback(
						{$this->get_template()->encode_data($this->prepare_data($data))}
						);
				}
JS;
		}
				
		return $result . ',';
	}
	
	public function build_js_column_def (\exface\Core\Widgets\DataColumn $col){
		$editor = $this->editors[$col->get_id()];
	
		$output = '{
							name: "' . $col->get_data_column_name() . '"'
							. ($col->get_attribute_alias() ? ', data: "' . $col->get_data_column_name() . '"' : '')
							//. ($col->get_colspan() ? ', colspan: "' . intval($col->get_colspan()) . '"' : '')
							//. ($col->get_rowspan() ? ', rowspan: "' . intval($col->get_rowspan()) . '"' : '')
							. ($col->is_hidden() ? ', visible: false' :  '')
							//. ($editor ? ', editor: {type: "' . $editor->get_element_type() . '"' . ($editor->build_js_init_options() ? ', options: {' . $editor->build_js_init_options() . '}' : '') . '}' : '')
							. ', className: "' . $this->get_css_column_class($col) . '"'
							. ', orderable: ' . ($col->get_sortable() ? 'true' : 'false')
							. '}';
	
		return $output;
	}
	
	/**
	 * Returns a list of CSS classes to be used for the specified column: e.g. alignment, etc.
	 * @param \exface\Core\Widgets\DataColumn $col
	 * @return string
	 */
	public function get_css_column_class(\exface\Core\Widgets\DataColumn $col){
		$classes = '';
		switch ($col->get_align()){
			case EXF_ALIGN_LEFT : $classes .= 'text-left';
			case EXF_ALIGN_CENTER : $classes .= 'text-center';
			case EXF_ALIGN_RIGHT : $classes .= 'text-right';
		}
		return $classes;
	}
	
	public function build_js_edit_mode_enabler(){
		return '
					var rows = $(this).' . $this->get_element_type() . '("getRows");
					for (var i=0; i<rows.length; i++){
						$(this).' . $this->get_element_type() . '("beginEdit", i);
					}
				';
	}
	
	public function add_on_load_success($script){
		$this->on_load_success .= $script;
	}
	
	public function get_on_load_success(){
		return $this->on_load_success;
	}
	
	public function build_js_value_getter($row=null, $column=null){
		$output = $this->get_id()."_table";
		if (is_null($row)){
			$output .= ".rows('.selected').data()";
		} else {
			// TODO
		}
		if (is_null($column)){
			$column = $this->get_widget()->get_meta_object()->get_uid_alias();
		} else {
			// TODO
		}
		return $output . "['" . $column . "']";
	}
	
	public function build_js_data_getter(ActionInterface $action = null){		
		if (is_null($action)){
			$rows = $this->get_id() . "_table.rows().data()";
		} elseif ($this->is_editable() && $action->implements_interface('iModifyData')){
			// TODO
		} else {
			$rows = "Array.prototype.slice.call(" . $this->get_id() . "_table.rows({selected: true}).data())";
		}
		return "{oId: '" . $this->get_widget()->get_meta_object_id() . "', rows: " . $rows . "}";
	}
	
	public function build_js_refresh($keep_pagination_position = false){
		if (!$this->get_widget()->get_lazy_loading()){
			return "{$this->get_id()}_table.search($('#" . $this->get_id() . "_quickSearch').val(), false, true).draw();";
		} else {
			return $this->get_id() . "_table.draw(" . ($keep_pagination_position ? "false" : "true") . ");";
		}
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		// DataTables
		$includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/almasaeed2010/adminlte/plugins/datatables/dataTables.bootstrap.css">';
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/datatables.net/js/jquery.dataTables.min.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/DataTables.exface.helpers.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/datatables.net-select/js/dataTables.select.min.js"></script>';
		$includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/bower-asset/datatables.net-select-bs/css/select.bootstrap.min.css">';
		
		// Sortable plugin for column sorting in the table configuration popup
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jquery-sortable/source/js/jquery-sortable-min.js"></script>';
		
		// Right-click menu with context.js
		$includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLteTemplate/Template/js/context.js/context.bootstrap.css">';
		$includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/context.js/context.js"></script>';
		//$includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/jquery.contextmenu.js"></script>';
		
		return $includes;
	}
	
	protected function build_html_top_toolbar(){
		$table_caption = $this->get_widget()->get_caption() ? $this->get_widget()->get_caption() : $this->get_meta_object()->get_name();
		
		$quick_search_fields = $this->get_widget()->get_meta_object()->get_label_attribute() ? $this->get_widget()->get_meta_object()->get_label_attribute()->get_name() : '';
		foreach ($this->get_widget()->get_quick_search_filters() as $qfltr){
			$quick_search_fields .= ($quick_search_fields ? ', ' : '') . $qfltr->get_caption();
		}
		if ($quick_search_fields) $quick_search_fields = ': ' . $quick_search_fields;
		
		if (!$this->get_widget()->get_lazy_loading()){
			$filter_button_disabled = ' disabled';
		}
		
		if ($this->get_widget()->get_hide_toolbar_top()){
			$output = <<<HTML
	<h3 class="box-title">$table_caption</h3>
	<div class="box-tools pull-right">
		<button type="button" class="btn btn-box-tool" onclick="{$this->build_js_refresh(false)} return false;"><i class="fa fa-refresh"></i></button>
	</div>
HTML;
		} else {
			$output = <<<HTML
	<form id="{$this->get_id()}_quickSearch_form">

		<div class="row">
			<div class="col-xs-12 col-md-6">
				<h3 class="box-title" style="line-height: 34px;">$table_caption</h3>
			</div>
			<div class="col-xs-12 col-md-6">
				<div class="input-group">
					<span class="input-group-btn">
						<button type="button" class="btn btn-default btn-advanced-filtering" data-toggle="modal"{$filter_button_disabled} data-target="#{$this->get_id()}_popup_config"><i class="fa fa-filter"></i></button>
					</span>
					<input id="{$this->get_id()}_quickSearch" type="text" class="form-control" placeholder="Quick search{$quick_search_fields}" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" onclick="{$this->build_js_refresh(false)} return false;"><i class="fa fa-search"></i></button>
					</span>
				</div>
			</div>
		</div>
		<div id="{$this->get_id()}_filters_container" style="display: none;">
		</div>
	</form>
HTML;
		}
		return $output;
	}
	
	protected function build_html_bottom_toolbar($buttons_html){
		$output = <<<HTML
			<div class="col-xs-12 col-sm-6" style="padding-top: 10px;">{$buttons_html}</div>
			<div class="col-xs-12 col-sm-6 text-right" style="padding-top: 10px;">
				<form class="form-inline">
					<div class="btn-group dropup" role="group" id="#{$this->get_id()}_pageControls">
						<button type="button" href="#" id="{$this->get_id()}_prevPage" class="btn btn-default"><i class="fa fa-caret-left"></i></button>
						<button type="button" href="#" id="{$this->get_id()}_pageInfo" class="btn btn-default" data-toggle="dropdown">0 - 0 / 0</buton>
						<button type="button" href="#" id="{$this->get_id()}_nextPage" class="btn btn-default"><i class="fa fa-caret-right"></i></button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="{$this->get_id()}_pageInfo" style="width: 307px;">
					  		<li class="box-body">
				  				<button href="#" type="button" id="{$this->get_id()}_firstPage" class="btn btn-default" onclick="$('#{$this->get_id()}_pageInput').val(1);"><i class="fa fa-fast-backward"></i></button>	
					  			<div class="input-group">
									<input id="{$this->get_id()}_pageInput" type="number" class="form-control" value="1" />
									<span class="input-group-btn">
										<button href="#" type="button" class="btn btn-default"><i class="fa fa-calculator"></i></button>
									</span>
								</div>
								<button href="#" type="button" id="{$this->get_id()}_lastPage" class="btn btn-default" onclick="$('#{$this->get_id()}_pageInput').val(Math.floor({$this->get_id()}_table.page.info().recordsDisplay / {$this->get_id()}_table.page.info().length));"><i class="fa fa-fast-forward"></i></button>	
							</li>
					  	</ul>
					</div>
					<button type="button" data-target="#" class="btn btn-default" onclick="{$this->build_js_refresh(true)} return false;"><i class="fa fa-refresh"></i></button>
					<button type="button" data-target="#{$this->get_id()}_popup_config" data-toggle="modal" class="btn btn-default"><i class="fa fa-gear"></i></button>
				</form>
			</div>
HTML;
		return $output;
	}
	
	protected function build_js_pagination(){
		$output = <<<JS
	$('#{$this->get_id()}_prevPage').on('click', function(){{$this->get_id()}_table.page('previous'); {$this->build_js_refresh(true)}});
	$('#{$this->get_id()}_nextPage').on('click', function(){{$this->get_id()}_table.page('next'); {$this->build_js_refresh(true)}});
	
	$('#{$this->get_id()}_pageInfo').on('click', function(){
		$('#{$this->get_id()}_pageInput').val({$this->get_id()}_table.page()+1);
	});
	
	$('#{$this->get_id()}_pageControls').on('hidden.bs.dropdown', function(){
		{$this->get_id()}_table.page(parseInt($('#{$this->get_id()}_pageSlider').val())-1).draw(false);
	});
JS;
		return $output;
	}
	
	protected function build_js_quicksearch(){
		$output = <<<JS
	$('#{$this->get_id()}_quickSearch_form').on('submit', function(event) {
		{$this->build_js_refresh(false)}	
		event.preventDefault();
		return false;
	});
				
	$('#{$this->get_id()}_quickSearch').on('change', function(event) {
		{$this->build_js_refresh(false)}	
	});
JS;
		return $output;
	}
	
	/**
	 * Generates JS fixes for various template-specific issues.
	 * 
	 * @return string
	 */
	protected function build_js_fixes(){
		// If the table is in a tab, recalculate column width once the tab is opened
		if ($this->get_widget()->get_parent() instanceof Tab){
			$js = <<<JS
$('a[href="#' + $('#{$this->get_id()}').parents('.tab-pane').first().attr('id') + '"]').on('shown.bs.tab', function (e) {
	{$this->get_id()}_table.columns.adjust();
})		
JS;
		}
		// If the table is in a dialog, recalculate column width once the tab is opened
		elseif ($this->get_widget()->get_parent() instanceof Dialog){
			$js = <<<JS
$('a[href="#' + $('#{$this->get_id()}').parents('.modal').first().attr('id') + '"]').on('shown.bs.modal', function (e) {
	{$this->get_id()}_table.columns.adjust();
})
JS;
		}
		return $js;
	}
	
	protected function build_js_row_details(){
		$output = '';
		/* @var $widget \exface\Core\Widgets\DataTable */
		$widget = $this->get_widget();
		if ($widget->has_row_details()){
			$output = <<<JS
	// Add event listener for opening and closing details
	$('#{$this->get_id()} tbody').on('click', 'td.details-control', function () {
		var tr = $(this).closest('tr');
		var row = {$this->get_id()}_table.row( tr );
		
		if ( row.child.isShown() ) {
			// This row is already open - close it
			row.child.hide();
			tr.removeClass('shown');
			tr.find('.{$this->row_details_collapse_icon}').removeClass('{$this->row_details_collapse_icon}').addClass('{$this->row_details_expand_icon}');
			$('#detail'+row.data().id).remove();
			{$this->get_id()}_table.columns.adjust();
		}
		else {
			// Open this row
			row.child('<div id="detail'+row.data().{$widget->get_meta_object()->get_uid_alias()}+'"></div>').show();
			$.ajax({
				url: '{$this->get_ajax_url()}&action={$widget->get_row_details_action()}&resource={$this->get_page_id()}&element={$widget->get_row_details_container()->get_id()}&prefill={"meta_object_id":"{$widget->get_meta_object_id()}","rows":[{"{$widget->get_meta_object()->get_uid_alias()}":' + row.data().{$widget->get_meta_object()->get_uid_alias()} + '}]}'+'&exfrid='+row.data().{$widget->get_meta_object()->get_uid_alias()}, 
				dataType: "html",
				success: function(data){
					$('#detail'+row.data().{$widget->get_meta_object()->get_uid_alias()}).append(data);
					{$this->get_id()}_table.columns.adjust();
				},
				error: function(jqXHR, textStatus, errorThrown ){
					{$this->build_js_show_error('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
				}	
			});
			tr.next().addClass('detailRow unselectable');
			tr.addClass('shown');
			tr.find('.{$this->row_details_expand_icon}').removeClass('{$this->row_details_expand_icon}').addClass('{$this->row_details_collapse_icon}');
		}
	} );
JS;
		}
		return $output;
	}
	
	/**
	 * Generates JS to disable text selection on the rows of the table. If not done so, every time you longtap a row, something gets selected along
	 * with the context menu being displayed. It look awful. 
	 * @return string
	 */
	protected function build_js_disable_text_selection(){
		return "$('#{$this->get_id()} tbody tr td').attr('unselectable', 'on').css('user-select', 'none').on('selectstart', false);";
	}
	
	protected function build_html_table_customizer(){
		$filters_html = '';
		$columns_html = '';
		$sorting_html = '';
		/* @var $widget \exface\Core\Widgets\DataTable */
		$widget = $this->get_widget();
		
		foreach ($widget->get_filters() as $fltr){
			$filters_html .= $this->get_template()->generate_html($fltr);
		}
		
		foreach ($widget->get_columns() as $nr => $col){
			if (!$col->is_hidden()){
				$columns_html .= '<li><i class="fa fa-arrows-v pull-right handle"></i><div class="checkbox"><label><input type="checkbox" name="' . $col->get_id() . '" id="' . $widget->get_id() . '_cToggle_' . $col->get_id() . '" checked="true">' . $col->get_caption() . '</label></div></li>';
			}
		}
		$columns_html = '<ol id="' . $this->get_id() . '_popup_columnList" class="sortable">' . $columns_html . '</ol>';
		
		$output = <<<HTML

<div class="modal" id="{$this->get_id()}_popup_config">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}</h4>
			</div>
			<div class="modal-body">
				<div class="modal-body-content-wrapper">
					<div role="tabpanel">
	
						<!-- Nav tabs -->
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="active"><a href="#{$this->get_id()}_popup_filters" aria-controls="{$this->get_id()}_popup_filters" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.FILTERS')}</a></li>
							<li role="presentation"><a href="#{$this->get_id()}_popup_columns" aria-controls="{$this->get_id()}_popup_columns" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.COLUMNS')}</a></li>
							<li role="presentation"><a href="#{$this->get_id()}_popup_sorting" aria-controls="{$this->get_id()}_popup_sorting" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.SORTING')}</a></li>
						</ul>
										
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane active row" id="{$this->get_id()}_popup_filters">{$filters_html}</div>
							<div role="tabpanel" class="tab-pane" id="{$this->get_id()}_popup_columns">{$columns_html}</div>
							<div role="tabpanel" class="tab-pane" id="{$this->get_id()}_popup_sorting">{$sorting_html}</div>
						</div>
						
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left">Cancel</button>
				<button type="button" href="#" data-dismiss="modal" class="btn btn-primary" onclick="{$this->build_js_refresh(false)}">OK</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
		
HTML;
		return $output;
	}
	
	public function is_editable() {
		return $this->editable;
	}
	
	public function set_editable($value) {
		$this->editable = $value;
	}
	
	public function get_editors(){
		return $this->editors;
	}
}
?>