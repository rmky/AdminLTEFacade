<?php namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\DataColumn;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * 
 * @author PATRIOT
 *
 */
class lteDataList extends lteDataTable {
	
	function init(){
		parent::init();
		// Make sure, the DataTable has a UID column. This method will create the column if it does not exist yet.
		// It is important to call the method within init(), because at this point, the processing of the UXON is definitely
		// finished while the creation of the template element has not started yet!
		// FIXME Move this 
		$this->get_widget()->get_uid_column();
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\DataList */
		$widget = $this->get_widget();
		$column_templates = '';
		
		// Add promoted filters above the panel. Other filters will be displayed in a popup via JS
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fltr){
				if ($fltr->get_visibility() !== EXF_WIDGET_VISIBILITY_PROMOTED) continue;
				$filters_html .= $this->get_template()->generate_html($fltr);
			}
		}
		
		// Add buttons
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
				if (!$button->is_hidden()){
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
		
		foreach ($widget->get_columns() as $column){
			$column_templates .= $this->generate_column_template($column) . "\n";
		}
		
		$footer_style = $widget->get_hide_toolbar_bottom() ? 'display: none;' : '';
		$bottom_toolbar = $widget->get_hide_toolbar_bottom() ? '' : $this->build_html_bottom_toolbar($button_html);
		$top_toolbar = $this->build_html_top_toolbar();
		
		// output the html code
		// TODO replace "stripe" class by a custom css class
		$output = <<<HTML

<div class="{$this->get_width_classes()} exf_grid_item">
	<div class="box">
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body no-padding">
			<div id="{$this->get_id()}" class="exf-datalist masonry">
				<div class="placeholder dataTables_empty">{$widget->get_empty_text()}</div>
				<div class="col-xs-1" id="{$this->get_id()}_sizer"></div>
			</div>
		</div>
		<div class="box-footer clearfix" style="padding-top: 0px; {$footer_style}">
			<div class="row">
				{$bottom_toolbar}
			</div>
		</div>
	</div>
	{$this->build_html_table_customizer()}
</div>

<script type="text/x-handlebars-template" id="{$this->get_id()}_tpl">
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
	
	function generate_column_template(DataColumn $column){
		$tpl = '';
		if ($column->get_data_type()->is(EXF_DATA_TYPE_HTML)){
			$tpl = '{ { {' . $column->get_data_column_name() . '}}}';
		} elseif($column->get_data_type()->is(EXF_DATA_TYPE_IMAGE_URL)){ 
			$tpl = '<img style="margin: 0 auto 5px auto;" class="img-responsive" src="{ {' . $column->get_data_column_name() . '}}" />';
		} else {
			$tpl = '{ {' . $column->get_data_column_name() . '}}';
			
			switch ($column->get_size()){
				case EXF_TEXT_SIZE_BIG: $tpl = '<big>' . $tpl . '</big>'; break;
				case EXF_TEXT_SIZE_SMALL: $tpl = '<small>' . $tpl . '</small>'; break;
			}
			
			switch ($column->get_style()){
				case EXF_TEXT_STYLE_BOLD: $tpl = '<strong>' . $tpl . '</strong>'; break;
				case EXF_TEXT_STYLE_UNDERLINE: $tpl = '<ins>' . $tpl . '</ins>'; break;
				case EXF_TEXT_STYLE_STRIKETHROUGH: $tpl = '<del>' . $tpl . '</del>'; break;
			}
			
			$style = '';
			switch ($column->get_align()){
				case 'left': $style .= 'float: left;'; break;
				case 'right': $style .= 'float: right;'; break;
				case 'center': $style .= 'text-align: center;'; break;
			}
			
			$tpl = '<div data-field="' . $column->get_data_column_name() . '" class="datalist-value"' . ($style ? ' style="' . $style . '"' : '') . '>' . $tpl . '</div>';
		}
		
		if ($column->is_hidden()){
			$tpl = '<div class="hidden">' . $tpl . '</div>';
		}
		
		return $tpl;
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\DataList */
		$widget = $this->get_widget();
		$columns = array();
		$column_number_offset = 0;
		$filters_html = '';
		$filters_js = '';
		$filters_ajax = "data.q = $('#" . $this->get_id() . "_quickSearch').val();\n";
		$buttons_js = '';
		$footer_callback = '';
		$default_sorters = '';
		
		// sorters
		foreach ($widget->get_sorters() as $sorter){
			$column_exists = false;
			foreach ($widget->get_columns() as $nr => $col){
				if ($col->get_attribute_alias() == $sorter->attribute_alias){
					$column_exists = true;
					$default_sorters .= '[ ' . $nr . ', "' . $sorter->direction . '" ], ';
				}
			}
			if (!$column_exists){
				// TODO add a hidden column
			}
		}
		// Remove tailing comma
		if ($default_sorters) $default_sorters = substr($default_sorters, 0, -2);
		
		// Filters defined in the UXON description
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fnr => $fltr){
				// Skip promoted filters, as they are displayed next to quick search
				if ($fltr->get_visibility() == EXF_WIDGET_VISIBILITY_PROMOTED) continue;
				$fltr_element = $this->get_template()->get_element($fltr);
				$filters_js .= $this->get_template()->generate_js($fltr, $this->get_id().'_popup_config');
				$filters_ajax .= 'data.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = ' . $fltr_element->build_js_value_getter() . ";\n";
				
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
				$buttons_js .= $this->get_template()->generate_js($button);
			}
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
		if ($leftclick_button = $widget->get_buttons_bound_to_mouse_action(EXF_MOUSE_ACTION_LEFT_CLICK)[0]){
			$leftclick_script = $this->get_template()->get_element($leftclick_button)->build_js_click_function_name() .  '()';
		}
		
		// configure pagination
		if ($widget->get_paginate()){
			$paging_options = '"pageLength": ' . $widget->get_paginate_default_page_size() . ','; 
		} else {
			$paging_options = '"paging": false,';
		}
		
		$output = <<<JS
		
var {$this->get_id()}_pages = {
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
	$('#{$this->get_id()}').masonry({
		columnWidth: '#{$this->get_id()}_sizer', 
		itemSelector: '.exf_grid_item'
	});
	
	{$this->build_js_function_prefix()}load();
	
	{$this->build_js_pagination()}
	
	{$this->build_js_quicksearch()}
	
	{$this->build_js_row_selection()}
	
	$('#{$this->get_id()}').on('resize', $('#{$this->get_id()}').masonry('layout'));
	
	$(document).on('click', '#{$this->get_id()} .box', function(e){
		$('#{$this->get_id()} .box').removeClass('box-primary').removeClass('selected');
		$(this).addClass('box-primary').addClass('selected');
		{$leftclick_script}
	});
	
	$(document).on('dblclick', '#{$this->get_id()} .box', function(e){
		{$dblclick_script}
	});
	
	$(document).on('click', '#{$this->get_id()} .box', function(e){
		{$leftclick_script}
	});
	
});

function {$this->build_js_function_prefix()}getSelection(){
	var data = [];
	var row = {};
	$('#{$this->get_id()} .box.selected .datalist-value').each(function(index, element){
		row[$(element).data('field')] = $(element).text();
	});
	data.push(row);
	return data;
}

function {$this->build_js_function_prefix()}load(keep_page_pos, replace_data){
	if ($('#{$this->get_id()}').data('loading')) return;
	{$this->build_js_busy_icon_show()}
	$('#{$this->get_id()}').data('loading', 1);
	if (replace_data !== false){
		var currentItems = $('#{$this->get_id()}').children();
		$('#{$this->get_id()}').masonry('remove', currentItems).masonry('layout');
	}
	var data = {};
    data.action = '{$widget->get_lazy_loading_action()}';
	data.resource = "{$this->get_page_id()}";
	data.element = "{$widget->get_id()}";
	data.object = "{$this->get_widget()->get_meta_object()->get_id()}";
	{$filters_ajax}
	if ({$this->get_id()}_pages.length) {
		data.start = {$this->get_id()}_pages.page * {$this->get_id()}_pages.length;
		data.length = {$this->get_id()}_pages.length;
	}
	
	if (!keep_page_pos){
		{$this->get_id()}_pages.page = 0;
	}
    
	$.ajax({
       url: "{$this->get_ajax_url()}",
       data: data,
       method: 'POST',
       success: function(json){
			try {
				var data = $.parseJSON(json);
			} catch (err) {
				{$this->build_js_busy_icon_hide()}
			}
			if (data.data.length > 0) {
				var template = Handlebars.compile($('#{$this->get_id()}_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
				var elements = $(template(data));
		        $('#{$this->get_id()}')
		           .hide()
		           .append(elements)
		           .imagesLoaded( function(){ 
		              $('#{$this->get_id()} .placeholder').hide();
		              $('#{$this->get_id()}').show().masonry('appended', elements);
		              $('#{$this->get_id()}').closest('.exf_grid_item').trigger('resize');
		              {$this->build_js_busy_icon_hide()}
		              $('#{$this->get_id()}').data('loading', 0);
		         });
			} else {
				$('#{$this->get_id()} .placeholder').show();
				$('#{$this->get_id()}').data('loading', 0);
				{$this->build_js_busy_icon_hide()}
			}
			if (data.recordsFiltered){
				if (!{$this->get_id()}_pages.length){
					{$this->get_id()}_pages.length = data.data.length;
				}
				{$this->get_id()}_pages = $.extend({$this->get_id()}_pages, {
					recordsDisplay: parseInt(data.recordsFiltered),
					end: ({$this->get_id()}_pages.page * {$this->get_id()}_pages.length) + data.data.length,
					pages: Math.ceil(data.recordsFiltered/{$this->get_id()}_pages.length)
				});
				{$this->get_id()}_drawPagination();
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
		   {$this->build_js_busy_icon_hide()}
		   {$this->build_js_show_error('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
		}
	});
	
}

function {$this->get_id()}_drawPagination(){
	var pages = {$this->get_id()}_pages;
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
	
	public function build_js_refresh($keep_pagination_position = false){
		return $this->build_js_function_prefix() . "load(" . ($keep_pagination_position ? 1 : 0) .");";
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		$includes[] = '<script type="text/javascript" src="exface/vendor/components/handlebars.js/handlebars.min.js"></script>';
		return $includes;
	}
	
	public function build_js_data_getter(ActionInterface $action = null){
		if (is_null($action)){
			// TODO
		} else {
			$rows = $this->build_js_function_prefix() . "getSelection()";
		}
		return "{oId: '" . $this->get_widget()->get_meta_object_id() . "', rows: " . $rows . "}";
	}
	
	/**
	 * Renders javascript event handlers for tapping on rows. A single tap (or click) selects a row, while a longtap opens the
	 * context menu for the row if one is defined. The long tap also selects the row.
	 */
	protected function build_js_row_selection(){
		$output = '';
		if ($this->get_widget()->get_multi_select()){
			$output .= "
				$('#{$this->get_id()} tbody').on( 'click', 'tr', function (event) {
					if (event.which !== 1) return;
						$(this).toggleClass('selected bg-aqua');
					} );
				";
		} else {
			// Select a row on tap. Make sure no other row is selected
			$output .= "
				$('#{$this->get_id()} tbody').on( 'click', 'tr', function (event) {
					if(!(!event.detail || event.detail==1)) return;
				 	if ($(this).hasClass('unselectable')) return;
					
					if ( $(this).hasClass('selected bg-aqua') ) {
						$(this).removeClass('selected bg-aqua');
					} else {
						{$this->get_id()}_table.$('tr.selected').removeClass('selected bg-aqua');
						$(this).addClass('selected bg-aqua');
					}
				} );
			";
		}
		return $output;
	}
	
	protected function build_js_pagination(){
		$output = <<<JS
	$('#{$this->get_id()}_prevPage').on('click', function(){{$this->get_id()}_pages.previous(); {$this->build_js_refresh(true)}});
	$('#{$this->get_id()}_nextPage').on('click', function(){{$this->get_id()}_pages.next(); {$this->build_js_refresh(true)}});
	
	$('#{$this->get_id()}_pageInfo').on('click', function(){
		$('#{$this->get_id()}_pageInput').val({$this->get_id()}_table.page()+1);
	});
	
	$('#{$this->get_id()}_pageControls').on('hidden.bs.dropdown', function(){
		{$this->get_id()}_table.page(parseInt($('#{$this->get_id()}_pageSlider').val())-1).draw(false);
	});
JS;
			return $output;
	}
}
?>