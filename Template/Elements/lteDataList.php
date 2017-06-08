<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\DataColumn;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 *
 * @author PATRIOT
 *        
 */
class lteDataList extends lteDataTable
{

    function init()
    {
        parent::init();
        // Make sure, the DataTable has a UID column. This method will create the column if it does not exist yet.
        // It is important to call the method within init(), because at this point, the processing of the UXON is definitely
        // finished while the creation of the template element has not started yet!
        // FIXME Move this
        $this->getWidget()->getUidColumn();
    }

    function generateHtml()
    {
        /* @var $widget \exface\Core\Widgets\DataList */
        $widget = $this->getWidget();
        $column_templates = '';
        
        // Add promoted filters above the panel. Other filters will be displayed in a popup via JS
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fltr) {
                if ($fltr->getVisibility() !== EXF_WIDGET_VISIBILITY_PROMOTED)
                    continue;
                $filters_html .= $this->getTemplate()->generateHtml($fltr);
            }
        }
        
        // Add buttons
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
                if (! $button->isHidden()) {
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
        
        foreach ($widget->getColumns() as $column) {
            $column_templates .= $this->generateColumnTemplate($column) . "\n";
        }
        
        $footer_style = $widget->getHideToolbarBottom() ? 'display: none;' : '';
        $bottom_toolbar = $widget->getHideToolbarBottom() ? '' : $this->buildHtmlBottomToolbar($button_html);
        $top_toolbar = $this->buildHtmlTopToolbar();
        
        // output the html code
        // TODO replace "stripe" class by a custom css class
        $output = <<<HTML

<div class="{$this->getWidthClasses()} exf_grid_item">
	<div class="box">
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body no-padding">
			<div id="{$this->getId()}" class="exf-datalist masonry">
				<div class="placeholder dataTables_empty">{$widget->getEmptyText()}</div>
				<div class="col-xs-1" id="{$this->getId()}_sizer"></div>
			</div>
		</div>
		<div class="box-footer clearfix" style="padding-top: 0px; {$footer_style}">
			<div class="row">
				{$bottom_toolbar}
			</div>
		</div>
	</div>
	{$this->buildHtmlTableCustomizer()}
</div>

<script type="text/x-handlebars-template" id="{$this->getId()}_tpl">
{ {#data}}
    <div class="exf_grid_item col-lg-3 col-md-4 col-sm-5 col-xs-12">
    	<div class="box box-default box-solid">
        	<div class="box-body" style="overflow: hidden;">
				{$column_templates}
			</div>
        </div>
    </div>
{ {/data}}
</script>
	
HTML;
        
        return $output;
    }

    function generateColumnTemplate(DataColumn $column)
    {
        $tpl = '';
        if ($column->getDataType()->is(EXF_DATA_TYPE_HTML)) {
            $tpl = '{ { {' . $column->getDataColumnName() . '}}}';
        } elseif ($column->getDataType()->is(EXF_DATA_TYPE_IMAGE_URL)) {
            $tpl = '<img style="margin: 0 auto 5px auto;" class="img-responsive" src="{ {' . $column->getDataColumnName() . '}}" />';
        } else {
            $tpl = '{ {' . $column->getDataColumnName() . '}}';
            
            switch ($column->getSize()) {
                case EXF_TEXT_SIZE_BIG:
                    $tpl = '<big>' . $tpl . '</big>';
                    break;
                case EXF_TEXT_SIZE_SMALL:
                    $tpl = '<small>' . $tpl . '</small>';
                    break;
            }
            
            switch ($column->getStyle()) {
                case EXF_TEXT_STYLE_BOLD:
                    $tpl = '<strong>' . $tpl . '</strong>';
                    break;
                case EXF_TEXT_STYLE_UNDERLINE:
                    $tpl = '<ins>' . $tpl . '</ins>';
                    break;
                case EXF_TEXT_STYLE_STRIKETHROUGH:
                    $tpl = '<del>' . $tpl . '</del>';
                    break;
            }
            
            $style = '';
            switch ($column->getAlign()) {
                case 'left':
                    $style .= 'float: left;';
                    break;
                case 'right':
                    $style .= 'float: right;';
                    break;
                case 'center':
                    $style .= 'text-align: center;';
                    break;
            }
            
            $tpl = '<div data-field="' . $column->getDataColumnName() . '" class="datalist-value"' . ($style ? ' style="' . $style . '"' : '') . '>' . $tpl . '</div>';
        }
        
        if ($column->isHidden()) {
            $tpl = '<div class="hidden">' . $tpl . '</div>';
        }
        
        return $tpl;
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\DataList */
        $widget = $this->getWidget();
        $columns = array();
        $column_number_offset = 0;
        $filters_html = '';
        $filters_js = '';
        $filters_ajax = "data.q = $('#" . $this->getId() . "_quickSearch').val();\n";
        $buttons_js = '';
        $footer_callback = '';
        $default_sorters = '';
        
        // sorters
        foreach ($widget->getSorters() as $sorter) {
            $column_exists = false;
            foreach ($widget->getColumns() as $nr => $col) {
                if ($col->getAttributeAlias() == $sorter->attribute_alias) {
                    $column_exists = true;
                    $default_sorters .= '[ ' . $nr . ', "' . $sorter->direction . '" ], ';
                }
            }
            if (! $column_exists) {
                // TODO add a hidden column
            }
        }
        // Remove tailing comma
        if ($default_sorters)
            $default_sorters = substr($default_sorters, 0, - 2);
        
        // Filters defined in the UXON description
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fnr => $fltr) {
                // Skip promoted filters, as they are displayed next to quick search
                if ($fltr->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED)
                    continue;
                $fltr_element = $this->getTemplate()->getElement($fltr);
                $filters_js .= $this->getTemplate()->generateJs($fltr, $this->getId() . '_popup_config');
                $filters_ajax .= 'data.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = ' . $fltr_element->buildJsValueGetter() . ";\n";
                
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
                $buttons_js .= $this->getTemplate()->generateJs($button);
            }
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
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            $leftclick_script = $this->getTemplate()->getElement($leftclick_button)->buildJsClickFunctionName() . '()';
        }
        
        // configure pagination
        if ($widget->getPaginate()) {
            $paging_options = '"pageLength": ' . (!is_null($widget->getPaginatePageSize()) ? $widget->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.DATALIST.PAGE_SIZE')) . ',';
        } else {
            $paging_options = '"paging": false,';
        }
        
        $output = <<<JS
		
var {$this->getId()}_pages = {
	page: 0, 
	pages: 1, 
	end: 0,
	previous: function(){
		if (this.page == 0) return;
		this.page--;
	},
	next: function(){
		if (this.page == this.pages) return;
		this.page++;
	}
	
};
$(document).ready(function() {
	$('#{$this->getId()}').masonry({
		columnWidth: '#{$this->getId()}_sizer', 
		itemSelector: '.exf_grid_item'
	});
	
	{$this->buildJsFunctionPrefix()}load();
	
	{$this->buildJsPagination()}
	
	{$this->buildJsQuicksearch()}
	
	{$this->buildJsRowSelection()}
	
	$('#{$this->getId()}').on('resize', $('#{$this->getId()}').masonry('layout'));
	
	$(document).on('click', '#{$this->getId()} .box', function(e){
		$('#{$this->getId()} .box').removeClass('box-primary').removeClass('selected');
		$(this).addClass('box-primary').addClass('selected');
		{$leftclick_script}
	});
	
	$(document).on('dblclick', '#{$this->getId()} .box', function(e){
		{$dblclick_script}
	});
	
	$(document).on('click', '#{$this->getId()} .box', function(e){
		{$leftclick_script}
	});
	
});

function {$this->buildJsFunctionPrefix()}getSelection(){
	var data = [];
	var row = {};
	$('#{$this->getId()} .box.selected .datalist-value').each(function(index, element){
		row[$(element).data('field')] = $(element).text();
	});
	data.push(row);
	return data;
}

function {$this->buildJsFunctionPrefix()}load(keep_page_pos, replace_data){
	if ($('#{$this->getId()}').data('loading')) return;
	{$this->buildJsBusyIconShow()}
	$('#{$this->getId()}').data('loading', 1);
	if (replace_data !== false){
		var currentItems = $('#{$this->getId()}').children();
		$('#{$this->getId()}').masonry('remove', currentItems).masonry('layout');
	}
	var data = {};
    data.action = '{$widget->getLazyLoadingAction()}';
	data.resource = "{$this->getPageId()}";
	data.element = "{$widget->getId()}";
	data.object = "{$this->getWidget()->getMetaObject()->getId()}";
	{$filters_ajax}
	if ({$this->getId()}_pages.length) {
		data.start = {$this->getId()}_pages.page * {$this->getId()}_pages.length;
		data.length = {$this->getId()}_pages.length;
	}
	
	if (!keep_page_pos){
		{$this->getId()}_pages.page = 0;
	}
    
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: data,
       method: 'POST',
       success: function(json){
			try {
				var data = $.parseJSON(json);
			} catch (err) {
				{$this->buildJsBusyIconHide()}
			}
			if (data.data.length > 0) {
				var template = Handlebars.compile($('#{$this->getId()}_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
				var elements = $(template(data));
		        $('#{$this->getId()}')
		           .hide()
		           .append(elements)
		           .imagesLoaded( function(){ 
		              $('#{$this->getId()} .placeholder').hide();
		              $('#{$this->getId()}').show().masonry('appended', elements);
		              $('#{$this->getId()}').closest('.exf_grid_item').trigger('resize');
		              {$this->buildJsBusyIconHide()}
		              $('#{$this->getId()}').data('loading', 0);
		         });
			} else {
				$('#{$this->getId()} .placeholder').show();
				$('#{$this->getId()}').data('loading', 0);
				{$this->buildJsBusyIconHide()}
			}
			if (data.recordsFiltered){
				if (!{$this->getId()}_pages.length){
					{$this->getId()}_pages.length = data.data.length;
				}
				{$this->getId()}_pages = $.extend({$this->getId()}_pages, {
					recordsDisplay: parseInt(data.recordsFiltered),
					end: ({$this->getId()}_pages.page * {$this->getId()}_pages.length) + data.data.length,
					pages: Math.ceil(data.recordsFiltered/{$this->getId()}_pages.length)
				});
				{$this->getId()}_drawPagination();
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
		   {$this->buildJsBusyIconHide()}
		   {$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
		}
	});
	
}

function {$this->getId()}_drawPagination(){
	var pages = {$this->getId()}_pages;
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

JS;
        
        return $output;
    }

    public function buildJsRefresh($keep_pagination_position = false)
    {
        return $this->buildJsFunctionPrefix() . "load(" . ($keep_pagination_position ? 1 : 0) . ");";
    }

    public function generateHeaders()
    {
        $includes = parent::generateHeaders();
        $includes[] = '<script type="text/javascript" src="exface/vendor/components/handlebars.js/handlebars.min.js"></script>';
        return $includes;
    }

    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if (is_null($action)) {
            // TODO
        } else {
            $rows = $this->buildJsFunctionPrefix() . "getSelection()";
        }
        return "{oId: '" . $this->getWidget()->getMetaObjectId() . "', rows: " . $rows . "}";
    }

    /**
     * Renders javascript event handlers for tapping on rows.
     * A single tap (or click) selects a row, while a longtap opens the
     * context menu for the row if one is defined. The long tap also selects the row.
     */
    protected function buildJsRowSelection()
    {
        $output = '';
        if ($this->getWidget()->getMultiSelect()) {
            $output .= "
				$('#{$this->getId()} tbody').on( 'click', 'tr', function (event) {
					if (event.which !== 1) return;
						$(this).toggleClass('selected bg-aqua');
					} );
				";
        } else {
            // Select a row on tap. Make sure no other row is selected
            $output .= "
				$('#{$this->getId()} tbody').on( 'click', 'tr', function (event) {
					if(!(!event.detail || event.detail==1)) return;
				 	if ($(this).hasClass('unselectable')) return;
					
					if ( $(this).hasClass('selected bg-aqua') ) {
						$(this).removeClass('selected bg-aqua');
					} else {
						{$this->getId()}_table.$('tr.selected').removeClass('selected bg-aqua');
						$(this).addClass('selected bg-aqua');
					}
				} );
			";
        }
        return $output;
    }

    protected function buildJsPagination()
    {
        $output = <<<JS
	$('#{$this->getId()}_prevPage').on('click', function(){{$this->getId()}_pages.previous(); {$this->buildJsRefresh(true)}});
	$('#{$this->getId()}_nextPage').on('click', function(){{$this->getId()}_pages.next(); {$this->buildJsRefresh(true)}});
	
	$('#{$this->getId()}_pageInfo').on('click', function(){
		$('#{$this->getId()}_pageInput').val({$this->getId()}_table.page()+1);
	});
	
	$('#{$this->getId()}_pageControls').on('hidden.bs.dropdown', function(){
		{$this->getId()}_table.page(parseInt($('#{$this->getId()}_pageSlider').val())-1).draw(false);
	});
JS;
        return $output;
    }
}
?>