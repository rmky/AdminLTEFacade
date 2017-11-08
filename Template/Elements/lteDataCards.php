<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\DataColumn;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarsTrait;
use exface\Core\DataTypes\HtmlDataType;
use exface\Core\DataTypes\ImageUrlDataType;

/**
 *
 * @author PATRIOT
 *        
 */
class lteDataCards extends lteDataList
{
    use JqueryToolbarsTrait;
    
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
        /* @var $widget \exface\Core\Widgets\DataCards */
        $widget = $this->getWidget();
        $column_templates = '';
        
        foreach ($widget->getColumns() as $column) {
            $column_templates .= $this->generateColumnTemplate($column) . "\n";
        }
        
        $footer_style = $widget->getHideFooter() ? 'display: none;' : '';
        $bottom_toolbar = $widget->getHideFooter() ? '' : $this->buildHtmlFooter($this->buildHtmlToolbars());
        $top_toolbar = $this->buildHtmlHeader();
        
        // output the html code
        // TODO replace "stripe" class by a custom css class
        $output = <<<HTML

<div class="box">
	<div class="box-header">
		{$top_toolbar}
	</div><!-- /.box-header -->
	<div class="box-body no-padding">
		<div id="{$this->getId()}" class="exf-datacards masonry">
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


<script type="text/x-handlebars-template" id="{$this->getId()}_tpl">
{ {#data}}
    <div class="exf-grid-item col-lg-3 col-md-4 col-sm-5 col-xs-12">
    	<div class="box box-default box-solid">
        	<div class="box-body" style="overflow: hidden;">
				{$column_templates}
			</div>
        </div>
    </div>
{ {/data}}
</script>
	
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    function generateColumnTemplate(DataColumn $column)
    {
        $tpl = '';
        if ($column->getDataType() instanceof HtmlDataType) {
            $tpl = '{ { {' . $column->getDataColumnName() . '}}}';
        } elseif ($column->getDataType() instanceof ImageUrlDataType) {
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
            if ($column->getAlign()){
                switch ($this->buildCssTextAlignValue($column->getAlign())) {
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
            }
            
            $tpl = '<div data-field="' . $column->getDataColumnName() . '" class="exf-data-value"' . ($style ? ' style="' . $style . '"' : '') . '>' . $tpl . '</div>';
        }
        
        if ($column->isHidden()) {
            $tpl = '<div class="hidden">' . $tpl . '</div>';
        }
        
        return $tpl;
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\DataCards */
        $widget = $this->getWidget();
        $filters_js = '';
        $filters_ajax = "data.q = $('#" . $this->getId() . "_quickSearch').val();\n";
        $default_sorters = '';
        
        // sorters
        foreach ($widget->getSorters() as $sorter) {
            $column_exists = false;
            foreach ($widget->getColumns() as $nr => $col) {
                if ($col->getAttributeAlias() == $sorter->getProperty('attribute_alias')) {
                    $column_exists = true;
                    $default_sorters .= '[ ' . $nr . ', "' . $sorter->getProperty('direction') . '" ], ';
                }
            }
            if (! $column_exists) {
                // TODO add a hidden column
            }
        }
        // Remove tailing comma
        if ($default_sorters){
            $default_sorters = substr($default_sorters, 0, - 2);
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
		itemSelector: '.exf-grid-item'
	});
	
	{$this->buildJsFunctionPrefix()}load();
	
	{$this->buildJsPagination()}
	
	{$this->buildJsQuicksearch()}
	
	{$this->buildJsRowSelection()}
	
	$('#{$this->getId()}').on('resize', $('#{$this->getId()}').masonry('layout'));

    {$this->buildJsClickFunctions('#'.$this->getId().' .box', 'box-primary')}
	
});

{$this->buildJsDataGetterFunction('#'.$this->getId().' .box.selected .exf-data-value')}

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
	data.resource = "{$widget->getPage()->getAliasWithNamespace()}";
	data.element = "{$widget->getId()}";
	data.object = "{$this->getWidget()->getMetaObject()->getId()}";
    data.q = $('#{$this->getId()}_quickSearch').val();			
	data.data = {$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter()}
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
		              $('#{$this->getId()}').closest('.exf-grid-item').trigger('resize');
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

{$this->buildJsButtons()}

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