<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Tab;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryDataTablesTrait;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryDataTableTrait;

/**
 *
 * @method DataTable getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteDataTable extends lteAbstractElement
{
    
    use JqueryDataTableTrait;
    use JqueryDataTablesTrait;

    private $on_load_success = '';

    private $editable = false;

    private $editors = array();

    protected function init()
    {
        parent::init();
        $this->setRowDetailsExpandIcon('fa-plus-square-o');
        $this->setRowDetailsCollapseIcon('fa-minus-square-o');
    }

    function generateHtml()
    {
        $widget = $this->getWidget();
        $thead = '';
        $tfoot = '';
        
        // Column headers
        /* @var $col \exface\Core\Widgets\DataColumn */
        foreach ($widget->getColumns() as $col) {
            $thead .= '<th>' . $col->getCaption() . '</th>';
            if ($widget->hasFooter()) {
                $tfoot .= '<th class="text-right"></th>';
            }
        }
        
        // Extra column for the multiselect-checkbox
        if ($widget->getMultiSelect()) {
            $checkbox_header = '<th onclick="javascript: if(!$(this).parent().hasClass(\'selected\')) {' . $this->getId() . '_table.rows().select(); $(\'#' . $this->getId() . '_wrapper\').find(\'th.select-checkbox\').parent().addClass(\'selected\');} else{' . $this->getId() . '_table.rows().deselect(); $(\'#' . $this->getId() . '_wrapper\').find(\'th.select-checkbox\').parent().removeClass(\'selected\');}"></th>';
            $thead = $checkbox_header . $thead;
            if ($tfoot) {
                $tfoot = $checkbox_header . $tfoot;
            }
        }
        
        // Extra column for expand-button if rows have details
        if ($widget->hasRowDetails()) {
            $thead = '<th></th>' . $thead;
            if ($tfoot) {
                $tfoot = '<th></th>' . $tfoot;
            }
        }
        
        if ($tfoot)
            $tfoot = '<tfoot>' . $tfoot . '</tfoot>';
            
            // Add promoted filters above the panel. Other filters will be displayed in a popup via JS
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fltr) {
                if ($fltr->getVisibility() !== EXF_WIDGET_VISIBILITY_PROMOTED)
                    continue;
                $filters_html .= $this->getTemplate()->generateHtml($fltr);
            }
        }
        
        // add buttons
        /* @var $more_buttons_menu \exface\Core\Widgets\MenuButton */
        $more_buttons_menu = null;
        if ($widget->hasButtons()) {
            foreach ($widget->getButtons() as $button) {
                // Make pomoted and regular buttons visible right in the bottom toolbar
                // Hidden buttons also go here, because it does not make sense to put them into the menu
                if ($button->getVisibility() !== EXF_WIDGET_VISIBILITY_OPTIONAL || $button->isHidden()) {
                    $button_html .= $this->getTemplate()->generateHtml($button);
                }
                // Put all visible buttons into "more actions" menu
                // TODO do not create the more actions menu if all buttons are promoted!
                if ($button->getVisibility() == EXF_WIDGET_VISIBILITY_OPTIONAL && ! $button->isHidden()) {
                    if (! $more_buttons_menu) {
                        $more_buttons_menu = $widget->getPage()->createWidget('MenuButton', $widget);
                        $more_buttons_menu->setIconName('more');
                        $more_buttons_menu->setCaption('');
                    }
                    $more_buttons_menu->addButton($button);
                }
            }
        }
        
        if ($more_buttons_menu) {
            $button_html .= $this->getTemplate()->getElement($more_buttons_menu)->generateHtml();
        }
        $footer_style = $widget->getHideToolbarBottom() ? 'display: none;' : '';
        $bottom_toolbar = $this->buildHtmlBottomToolbar($button_html);
        $top_toolbar = $this->buildHtmlTopToolbar();
        
        // output the html code
        // TODO replace "stripe" class by a custom css class
        $output = <<<HTML
	<div class="box-header">
		{$top_toolbar}
	</div><!-- /.box-header -->
	<div class="box-body no-padding">
		<table id="{$this->getId()}" class="table table-striped table-hover" cellspacing="0" width="100%">
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
	{$this->buildHtmlTableCustomizer()}
HTML;
        
        return $this->buildHtmlWrapper($output);
    }

    protected function buildHtmlWrapper($html)
    {
        $result = $html;
        if (! $this->getWidget()->getParent() || $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) {
            $result = <<<HTML
<div class="box">{$result}</div>
HTML;
        }
        $result = <<<HTML
<div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">{$result}</div>
HTML;
        return $result;
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\DataTable */
        $widget = $this->getWidget();
        $columns = array();
        $column_number_offset = 0;
        $filters_html = '';
        $filters_js = '';
        $filters_ajax = "d.q = $('#" . $this->getId() . "_quickSearch').val();\n";
        $buttons_js = '';
        $context_menu_js = '';
        $footer_callback = '';
        $default_sorters = '';
        
        // Multiselect-Checkbox
        if ($widget->getMultiSelect()) {
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
            $column_number_offset ++;
        }
        
        // Expand-Button for row details
        if ($widget->hasRowDetails()) {
            $columns[] = '
					{
						"class": "details-control text-center",
						"width": "10px",
						"orderable": false,
						"data": null,
						"defaultContent": \'<i class="fa ' . $this->row_details_expand_icon . '"></i>\'
					}
					';
            $column_number_offset ++;
        }
        
        foreach ($widget->getSorters() as $sorter) {
            $column_exists = false;
            foreach ($widget->getColumns() as $nr => $col) {
                if ($col->getAttributeAlias() == $sorter->attribute_alias) {
                    $column_exists = true;
                    $default_sorters .= '[ ' . ($nr + $column_number_offset) . ', "' . $sorter->direction . '" ], ';
                }
            }
            if (! $column_exists) {
                // TODO add a hidden column
            }
        }
        // Remove tailing comma
        if ($default_sorters)
            $default_sorters = substr($default_sorters, 0, - 2);
            
            // columns
        foreach ($widget->getColumns() as $nr => $col) {
            $columns[] = $this->buildJsColumnDef($col);
            $nr = $nr + $column_number_offset;
            if ($col->getFooter()) {
                $footer_callback .= <<<JS
	            // Total over all pages
	            if (api.ajax.json().footer[0]['{$col->getDataColumnName()}']){
		            total = api.ajax.json().footer[0]['{$col->getDataColumnName()}'];
		            // Update footer
		            $( api.column( {$nr} ).footer() ).html( total );
	           	}
JS;
            }
        }
        $columns = implode(', ', $columns);
        if ($footer_callback) {
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
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fnr => $fltr) {
                // Skip promoted filters, as they are displayed next to quick search
                if ($fltr->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED)
                    continue;
                $fltr_element = $this->getTemplate()->getElement($fltr);
                $filters_js .= $this->getTemplate()->generateJs($fltr);
                $filters_ajax .= 'd["fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . '"] = ' . $fltr_element->buildJsValueGetter() . ";\n";
                $filters_ajax .= 'if(d["fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . '"]) {filtersOn = true; d["fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . '"] = "' . $fltr->getComparator() . '"+d["fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . '"];}' . "\n";
                
                // Here we generate some JS make the filter visible by default, once it gets used.
                // This code will be called when the table's config page gets closed.
                if (! $fltr->isHidden()) {
                    $filters_js_promoted .= "
							if (" . $fltr_element->buildJsValueGetter() . " && $('#" . $fltr_element->getId() . "').parents('#{$this->getId()}_popup_config').length > 0){
								var fltr = $('#" . $fltr_element->getId() . "').parents('.exf_input');
								var ui_block = $('<div class=\"col-xs-12 col-sm-6 col-md-4 col-lg-3\"></div>').appendTo('#{$this->getId()}_filters_container');
								fltr.detach().appendTo(ui_block).trigger('resize');
								$('#{$this->getId()}_filters_container').show();
							}
					";
                    /*
                     * $filters_js_promoted .= "
                     * if (" . $fltr_element->buildJsValueGetter() . "){
                     * var fltr = $('#" . $fltr_element->getId() . "').parents('.exf_input');
                     * var ui_block = $('<div></div>');
                     * if ($('#{$this->getId()}_filters_container').children('div').length % 2 == 0){
                     * ui_block.addClass('ui-block-a');
                     * } else {
                     * ui_block.addClass('ui-block-b');
                     * }
                     * ui_block.appendTo('#{$this->getId()}_filters_container');
                     * fltr.detach().appendTo(ui_block);
                     * fltr.addClass('ui-field-contain');
                     * }
                     * ";
                     */
                }
            }
        }
        
        // buttons
        if ($widget->hasButtons()) {
            foreach ($widget->getButtons() as $button) {
                /* @var $btn_element \exface\AdminLteTemplate\lteButton */
                $btn_element = $this->getTemplate()->getElement($button);
                $buttons_js .= $btn_element->generateJs();
                if (! $button->isHidden() && (! $button->getAction() || $button->getAction()->getInputRowsMin() === 1)) {
                    $icon = ($button->getIconName() ? '<i class=\'' . $btn_element->buildCssIconClass($button->getIconName()) . '\'></i> ' : '');
                    $context_menu_js .= '{text: "' . $icon . $button->getCaption() . '", action: function(e){e.preventDefault(); ' . $btn_element->buildJsClickFunctionName() . '();}}, ';
                }
            }
            $context_menu_js = $context_menu_js ? substr($context_menu_js, 0, - 2) : $context_menu_js;
        }
        
        // Click actions
        // Single click. Currently only supports one double click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            $leftclick_script = $this->getTemplate()->getElement($leftclick_button)->buildJsClickFunctionName() . '()';
        }
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $dblclick_script = $this->getTemplate()->getElement($dblclick_button)->buildJsClickFunctionName() . '()';
        }
        
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $rightclick_script = $this->getTemplate()->getElement($rightclick_button)->buildJsClickFunctionName() . '()';
        }
        
        // Selection
        if ($this->getWidget()->getMultiSelect()) {
            $select_options = 'style: "multi"';
            if ($this->getWidget()->getMultiSelectAllSelected()) {
                $initial_row_selection = $this->getId() . '_table.rows().select(); $(\'#' . $this->getId() . '_wrapper\').find(\'th.select-checkbox\').parent().addClass(\'selected\');';
            }
        } else {
            $select_options = 'style: "single"';
        }
        
        // configure pagination
        if ($widget->getPaginate()) {
            $paging_options = '"pageLength": ' . (! is_null($widget->getPaginatePageSize()) ? $widget->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE')) . ',';
        } else {
            $paging_options = '"paging": false,';
        }
        
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $layouterScript = $this->getTemplate()->getElement($layoutWidget)->buildJsLayouter() . ';';
        }
        
        $output = <<<JS
var {$this->getId()}_table;
if ($.fn.dataTable != undefined){
	$.fn.dataTable.ext.errMode = 'throw';
}

{$this->buildJsFunctionPrefix()}Init();

function {$this->buildJsFunctionPrefix()}Init(){
	
	if ({$this->getId()}_table && $.fn.DataTable.isDataTable( '#{$this->getId()}' )) {
		{$this->getId()}_table.columns.adjust();
		return;
	}	
	
	$('#{$this->getId()}_popup_columns input').click(function(){
		setColumnVisibility(this.name, (this.checked ? true : false) );
	});
	
	{$this->getId()}_table = $('#{$this->getId()}').DataTable( {
		"dom": 't',
		"deferRender": true,
		"processing": true,
		"select": { {$select_options} },
		{$paging_options}
		"scrollX": true,
		"scrollXollapse": true,
		{$this->buildJsDataSource($filters_ajax)}
		"language": {
            "zeroRecords": "{$widget->getEmptyText()}"
        },
		"columns": [{$columns}],
		"order": [ {$default_sorters} ],
		"drawCallback": function(settings, json) {
			$('#{$this->getId()} tbody tr').on('contextmenu', function(e){
				{$this->getId()}_table.row($(e.target).closest('tr')).select();
			});
			{$layouterScript}
			context.attach('#{$this->getId()} tbody tr', [{$context_menu_js}]);
			if({$this->getId()}_table){
				{$this->getId()}_drawPagination();
				{$this->getId()}_table.columns.adjust();
			}
			{$this->buildJsDisableTextSelection()}
			{$this->buildJsBusyIconHide()}
		}
		{$footer_callback}
	} );
	
	$('#{$this->getId()} tbody').on( 'click', 'tr', function () {
		{$leftclick_script}
    } );
    
    $('#{$this->getId()} tbody').on( 'dblclick', 'tr', function(e){
		{$dblclick_script}
	});
	
	$('#{$this->getId()} tbody').on( 'rightclick', 'tr', function(e){
		{$rightclick_script}
	});
	
	{$initial_row_selection}
	
	{$this->buildJsPagination()}
	
	{$this->buildJsQuicksearch()}
	
	{$this->buildJsRowDetails()}
	
	{$this->buildJsFixes()}
	
	$('#{$this->getId()}_popup_columnList').sortable();
	
	context.init({preventDoubleContext: false});
}
	
function setColumnVisibility(name, visible){
	{$this->getId()}_table.column(name+':name').visible(visible);
	$('#columnToggle_'+name).attr("checked", visible);
	try {
		$('#columnToggle_'+name).checkboxradio('refresh');
	} catch (ex) {}
}

function {$this->getId()}_drawPagination(){
	var pages = {$this->getId()}_table.page.info();
	if (pages.page == 0) {
		$('#{$this->getId()}_prevPage').attr('disabled', 'disabled');
	} else {
		$('#{$this->getId()}_prevPage').attr('disabled', false);
	}
	if (pages.page == pages.pages-1 || pages.end == pages.recordsDisplay) {
		$('#{$this->getId()}_nextPage').attr('disabled', 'disabled');
	} else {
		$('#{$this->getId()}_nextPage').attr('disabled', false);	
	}
	$('#{$this->getId()}_pageInfo').html(pages.page*pages.length+1 + ' - ' + (pages.recordsDisplay < (pages.page+1)*pages.length || pages.end == pages.recordsDisplay ? pages.recordsDisplay : (pages.page+1)*pages.length) + ' / ' + pages.recordsDisplay);
	
}

function {$this->getId()}_refreshPromotedFilters(){
	{$filters_js_promoted}
}

$('#{$this->getId()}_popup_config').on('hidden.bs.modal', function(e) {
	{$this->getId()}_refreshPromotedFilters();
});


{$filters_js}

{$buttons_js}

// Layout-Funktion hinzufuegen
{$this->buildJsLayouterFunction()}

JS;
        
        return $output;
    }

    protected function buildJsDataSource($js_filters = '')
    {
        $widget = $this->getWidget();
        
        $ajax_data = <<<JS
			function ( d ) {
				{$this->buildJsBusyIconShow()}
				var filtersOn = false;
				d.action = '{$widget->getLazyLoadingAction()}';
				d.resource = "{$this->getPageId()}";
				d.element = "{$widget->getId()}";
				d.object = "{$this->getWidget()->getMetaObject()->getId()}";
				{$js_filters}
				
				if (filtersOn){
					$('#{$this->getId()}_quickSearch_form .btn-advanced-filtering').removeClass('btn-default').addClass('btn-info');
					//$('#{$this->getId()}_quickSearch_form .filter-labels').append('<span class="label label-info">Primary</span>');
				} else {
					$('#{$this->getId()}_quickSearch_form .btn-advanced-filtering').removeClass('btn-info').addClass('btn-default');
					//$('#{$this->getId()}_quickSearch_form .filter-labels').empty();
				}
			}
JS;
        
        $result = '';
        if ($this->getWidget()->getLazyLoading()) {
            $result = <<<JS
		"serverSide": true,
		"ajax": {
			"url": "{$this->getAjaxUrl()}",
			"type": "POST",
			"data": {$ajax_data},
			"error": function(jqXHR, textStatus, errorThrown ){
				{$this->buildJsBusyIconHide()}
				{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
			}
		}
JS;
        } else {
            // Data embedded in the code of the DataGrid
            if ($widget->getValuesDataSheet() && ! $widget->getValuesDataSheet()->isEmpty()) {
                $data = $widget->getValuesDataSheet();
            }
            
            $data = $widget->prepareDataSheetToRead($data ? $data : null);
            
            if (! $data->isFresh()) {
                $data->dataRead();
            }
            $result = <<<JS
			"ajax": function (data, callback, settings) {
				callback(
						{$this->getTemplate()->encodeData($this->prepareData($data))}
						);
				}
JS;
        }
        
        return $result . ',';
    }

    public function buildJsColumnDef(\exface\Core\Widgets\DataColumn $col)
    {
        $output = '{
							name: "' . $col->getDataColumnName() . '"
                            ' . ($col->getAttributeAlias() ? ', data: "' . $col->getDataColumnName() . '"' : '') . '
                            ' . ($col->isHidden() ? ', visible: false' : '') . '
                            ' . ($col->getWidth()->isTemplateSpecific() ? ', width: "' . $col->getWidth()->getValue() . '"' : '') . '
                            , className: "' . $this->getCssColumnClass($col) . '"' . '
                            , orderable: ' . ($col->getSortable() ? 'true' : 'false') . '
                    }';
        
        return $output;
    }

    /**
     * Returns a list of CSS classes to be used for the specified column: e.g.
     * alignment, etc.
     *
     * @param \exface\Core\Widgets\DataColumn $col            
     * @return string
     */
    public function getCssColumnClass(\exface\Core\Widgets\DataColumn $col)
    {
        $classes = '';
        switch ($col->getAlign()) {
            case EXF_ALIGN_LEFT:
                $classes .= 'text-left';
                break;
            case EXF_ALIGN_CENTER:
                $classes .= 'text-center';
                break;
            case EXF_ALIGN_RIGHT:
                $classes .= 'text-right';
                break;
        }
        return $classes;
    }

    public function buildJsEditModeEnabler()
    {
        return '
					var rows = $(this).' . $this->getElementType() . '("getRows");
					for (var i=0; i<rows.length; i++){
						$(this).' . $this->getElementType() . '("beginEdit", i);
					}
				';
    }

    public function addOnLoadSuccess($script)
    {
        $this->on_load_success .= $script;
    }

    public function getOnLoadSuccess()
    {
        return $this->on_load_success;
    }

    public function buildJsValueGetter($row = null, $column = null)
    {
        $output = $this->getId() . "_table";
        if (is_null($row)) {
            $output .= ".rows('.selected').data()";
        } else {
            // TODO
        }
        if (is_null($column)) {
            $column = $this->getWidget()->getMetaObject()->getUidAlias();
        } else {
            // TODO
        }
        return $output . "[0]['" . $column . "']";
    }

    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if (is_null($action)) {
            $rows = $this->getId() . "_table.rows().data()";
        } elseif ($this->isEditable() && $action->implementsInterface('iModifyData')) {
            // TODO
        } else {
            $rows = "Array.prototype.slice.call(" . $this->getId() . "_table.rows({selected: true}).data())";
        }
        return "{oId: '" . $this->getWidget()->getMetaObjectId() . "', rows: " . $rows . "}";
    }

    public function buildJsRefresh($keep_pagination_position = false)
    {
        if (! $this->getWidget()->getLazyLoading()) {
            return "{$this->getId()}_table.search($('#" . $this->getId() . "_quickSearch').val(), false, true).draw();";
        } else {
            return $this->getId() . "_table.draw(" . ($keep_pagination_position ? "false" : "true") . ");";
        }
    }

    public function generateHeaders()
    {
        $includes = parent::generateHeaders();
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
        // $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/jquery.contextmenu.js"></script>';
        
        return $includes;
    }

    protected function buildHtmlTopToolbar()
    {
        $table_caption = $this->getWidget()->getCaption() ? $this->getWidget()->getCaption() : $this->getMetaObject()->getName();
        
        $quick_search_fields = $this->getWidget()->getMetaObject()->getLabelAttribute() ? $this->getWidget()->getMetaObject()->getLabelAttribute()->getName() : '';
        foreach ($this->getWidget()->getQuickSearchFilters() as $qfltr) {
            $quick_search_fields .= ($quick_search_fields ? ', ' : '') . $qfltr->getCaption();
        }
        if ($quick_search_fields)
            $quick_search_fields = ': ' . $quick_search_fields;
        
        if (! $this->getWidget()->getLazyLoading()) {
            $filter_button_disabled = ' disabled';
        }
        
        if ($this->getWidget()->getHideToolbarTop()) {
            $output = <<<HTML
	<h3 class="box-title">$table_caption</h3>
	<div class="box-tools pull-right">
        <button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#{$this->getId()}_popup_config" title="{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}"><i class="fa fa-filter"></i></button>
		<button type="button" class="btn btn-box-tool" onclick="{$this->buildJsRefresh(false)} return false;"  title="{$this->translate('WIDGET.REFRESH')}"><i class="fa fa-refresh"></i></button>
	</div>
HTML;
        } else {
            $output = <<<HTML
	<form id="{$this->getId()}_quickSearch_form">

		<div class="row">
			<div class="col-xs-12 col-md-6">
				<h3 class="box-title" style="line-height: 34px;">$table_caption</h3>
			</div>
			<div class="col-xs-12 col-md-6">
				<div class="input-group">
					<span class="input-group-btn">
						<button type="button" class="btn btn-default btn-advanced-filtering" data-toggle="modal"{$filter_button_disabled} data-target="#{$this->getId()}_popup_config"><i class="fa fa-filter"></i></button>
					</span>
					<input id="{$this->getId()}_quickSearch" type="text" class="form-control" placeholder="Quick search{$quick_search_fields}" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" onclick="{$this->buildJsRefresh(false)} return false;"><i class="fa fa-search"></i></button>
					</span>
				</div>
			</div>
		</div>
		<div id="{$this->getId()}_filters_container" style="display: none;">
		</div>
	</form>
HTML;
        }
        return $output;
    }

    protected function buildHtmlBottomToolbar($buttons_html)
    {
        $output = <<<HTML
			<div class="col-xs-12 col-sm-6" style="padding-top: 10px;">{$buttons_html}</div>
			<div class="col-xs-12 col-sm-6 text-right" style="padding-top: 10px;">
				<form class="form-inline">
					<div class="btn-group dropup" role="group" id="#{$this->getId()}_pageControls">
						<button type="button" href="#" id="{$this->getId()}_prevPage" class="btn btn-default"><i class="fa fa-caret-left"></i></button>
						<button type="button" href="#" id="{$this->getId()}_pageInfo" class="btn btn-default" data-toggle="dropdown">0 - 0 / 0</buton>
						<button type="button" href="#" id="{$this->getId()}_nextPage" class="btn btn-default"><i class="fa fa-caret-right"></i></button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="{$this->getId()}_pageInfo" style="width: 307px;">
					  		<li class="box-body">
				  				<button href="#" type="button" id="{$this->getId()}_firstPage" class="btn btn-default" onclick="$('#{$this->getId()}_pageInput').val(1);"><i class="fa fa-fast-backward"></i></button>	
					  			<div class="input-group">
									<input id="{$this->getId()}_pageInput" type="number" class="form-control" value="1" />
									<span class="input-group-btn">
										<button href="#" type="button" class="btn btn-default"><i class="fa fa-calculator"></i></button>
									</span>
								</div>
								<button href="#" type="button" id="{$this->getId()}_lastPage" class="btn btn-default" onclick="$('#{$this->getId()}_pageInput').val(Math.floor({$this->getId()}_table.page.info().recordsDisplay / {$this->getId()}_table.page.info().length));"><i class="fa fa-fast-forward"></i></button>	
							</li>
					  	</ul>
					</div>
					<button type="button" data-target="#" class="btn btn-default" onclick="{$this->buildJsRefresh(true)} return false;" title="{$this->translate('WIDGET.REFRESH')}"><i class="fa fa-refresh"></i></button>
					<button type="button" data-target="#{$this->getId()}_popup_config" data-toggle="modal" class="btn btn-default" title="{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}"><i class="fa fa-gear"></i></button>
				</form>
			</div>
HTML;
        return $output;
    }

    protected function buildJsPagination()
    {
        $output = <<<JS
	$('#{$this->getId()}_prevPage').on('click', function(){{$this->getId()}_table.page('previous'); {$this->buildJsRefresh(true)}});
	$('#{$this->getId()}_nextPage').on('click', function(){{$this->getId()}_table.page('next'); {$this->buildJsRefresh(true)}});
	
	$('#{$this->getId()}_pageInfo').on('click', function(){
		$('#{$this->getId()}_pageInput').val({$this->getId()}_table.page()+1);
	});
	
	$('#{$this->getId()}_pageControls').on('hidden.bs.dropdown', function(){
		{$this->getId()}_table.page(parseInt($('#{$this->getId()}_pageSlider').val())-1).draw(false);
	});
JS;
        return $output;
    }

    protected function buildJsQuicksearch()
    {
        $output = <<<JS
	$('#{$this->getId()}_quickSearch_form').on('submit', function(event) {
		{$this->buildJsRefresh(false)}	
		event.preventDefault();
		return false;
	});
				
	$('#{$this->getId()}_quickSearch').on('change', function(event) {
		{$this->buildJsRefresh(false)}	
	});
JS;
        return $output;
    }

    /**
     * Generates JS fixes for various template-specific issues.
     *
     * @return string
     */
    protected function buildJsFixes()
    {
        // If the table is in a tab, recalculate column width once the tab is opened
        if ($this->getWidget()->getParent() instanceof Tab) {
            $js = <<<JS
$('a[href="#' + $('#{$this->getId()}').parents('.tab-pane').first().attr('id') + '"]').on('shown.bs.tab', function (e) {
	{$this->getId()}_table.columns.adjust();
})		
JS;
        } // If the table is in a dialog, recalculate column width once the tab is opened
elseif ($this->getWidget()->getParent() instanceof Dialog) {
            $js = <<<JS
$('a[href="#' + $('#{$this->getId()}').parents('.modal').first().attr('id') + '"]').on('shown.bs.modal', function (e) {
	{$this->getId()}_table.columns.adjust();
})
JS;
        }
        return $js;
    }

    /**
     * Generates JS to disable text selection on the rows of the table.
     * If not done so, every time you longtap a row, something gets selected along
     * with the context menu being displayed. It look awful.
     *
     * @return string
     */
    protected function buildJsDisableTextSelection()
    {
        return "$('#{$this->getId()} tbody tr td').attr('unselectable', 'on').css('user-select', 'none').on('selectstart', false);";
    }

    protected function buildHtmlTableCustomizer()
    {
        $filters_html = '';
        $columns_html = '';
        $sorting_html = '';
        /* @var $widget \exface\Core\Widgets\DataTable */
        $widget = $this->getWidget();
        
        foreach ($widget->getFilters() as $fltr) {
            $filters_html .= $this->getTemplate()->generateHtml($fltr);
        }
        
        foreach ($widget->getColumns() as $nr => $col) {
            if (! $col->isHidden()) {
                $columns_html .= '<li><i class="fa fa-arrows-v pull-right handle"></i><div class="checkbox"><label><input type="checkbox" name="' . $col->getId() . '" id="' . $widget->getId() . '_cToggle_' . $col->getId() . '" checked="true">' . $col->getCaption() . '</label></div></li>';
            }
        }
        $columns_html = '<ol id="' . $this->getId() . '_popup_columnList" class="sortable">' . $columns_html . '</ol>';
        
        $output = <<<HTML

<div class="modal" id="{$this->getId()}_popup_config">
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
							<li role="presentation" class="active"><a href="#{$this->getId()}_popup_filters" aria-controls="{$this->getId()}_popup_filters" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.FILTERS')}</a></li>
							<li role="presentation"><a href="#{$this->getId()}_popup_columns" aria-controls="{$this->getId()}_popup_columns" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.COLUMNS')}</a></li>
							<li role="presentation"><a href="#{$this->getId()}_popup_sorting" aria-controls="{$this->getId()}_popup_sorting" role="tab" data-toggle="tab">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.SORTING')}</a></li>
						</ul>
										
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane active row" id="{$this->getId()}_popup_filters">{$filters_html}</div>
							<div role="tabpanel" class="tab-pane" id="{$this->getId()}_popup_columns">{$columns_html}</div>
							<div role="tabpanel" class="tab-pane" id="{$this->getId()}_popup_sorting">{$sorting_html}</div>
						</div>
						
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left">Cancel</button>
				<button type="button" href="#" data-dismiss="modal" class="btn btn-primary" onclick="{$this->buildJsRefresh(false)}">OK</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
		
HTML;
        return $output;
    }

    public function isEditable()
    {
        return $this->editable;
    }

    public function setEditable($value)
    {
        $this->editable = $value;
    }

    public function getEditors()
    {
        return $this->editors;
    }

    /**
     * Returns an inline JavaScript-Snippet that layouts the element.
     *
     * @return string
     */
    public function buildJsLayouter()
    {
        return $this->getId() . '_layouter()';
    }

    /**
     * Returns a JavaScript-Function that layouts the element, and which is called by the
     * snippet returned by buildJsLayouter().
     *
     * @return string
     */
    public function buildJsLayouterFunction()
    {
        $output = <<<JS
    
    function {$this->getId()}_layouter() {}
JS;
        
        return $output;
    }

    /**
     * Returns the default number of columns to layout this widget.
     * 
     * @return integer
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DATATABLE.COLUMNS_BY_DEFAULT");
    }

    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     * 
     * @return boolean
     */
    public function inheritsColumnNumber()
    {
        return false;
    }
}
?>