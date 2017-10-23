<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 *
 * @author PATRIOT
 *        
 */
class lteImageSlider extends lteDataCards
{

    function generateHtml()
    {
        /* @var $widget \exface\Core\Widgets\ImageGallery */
        $widget = $this->getWidget();
        $top_toolbar = $this->buildHtmlHeader();
        
        // output the html code
        $output = <<<HTML

<div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
	<div class="box" >
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body">
			<div id="{$this->getId()}" style="position: relative; margin: 0 auto; top: 0px; left: 0px; width: 960px; height: calc({$this->getHeight()} - 43px); overflow: hidden; visibility: hidden;">
		        <div data-u="slides" class="slides" style="cursor: default; position: relative; top: 0px; left: 240px; width: 720px; height: 400px; overflow: hidden;">
		            <!-- Slides -->
		        </div>
		        <!-- Thumbnail Navigator -->
		        <div data-u="thumbnavigator" class="jssor-nav" style="position:absolute;left:0px;top:0px;width:240px;height:400px;" data-autocenter="2">
		            <!-- Thumbnail Item Skin Begin -->
		            <div data-u="slides" style="cursor: default;">
		                <div data-u="prototype" class="p">
		                    <div class="w">
		                        <div data-u="thumbnailtemplate" class="t"></div>
		                    </div>
		                    <div class="c"></div>
		                </div>
		            </div>
		            <!-- Thumbnail Item Skin End -->
		        </div>
		        <!-- Arrow Navigator -->
		        <span data-u="arrowleft" class="jssora05l" style="top:158px;left:248px;width:40px;height:40px;" data-autocenter="2"></span>
		        <span data-u="arrowright" class="jssora05r" style="top:158px;right:8px;width:40px;height:40px;" data-autocenter="2"></span>
		    </div>
		</div>
	</div>
	{$this->buildHtmlTableCustomizer()}
</div>
					
<script type="text/x-handlebars-template" id="{$this->getId()}_tpl">
{ {#data}}
    <div data-p="150.00" style="display: none;">
		<div data-u="image" class="img-wrap" >
			<img src="{ {{$widget->getImageUrlColumnId()}}}"/>
		</div>
		<div data-u="thumb" class="thumb-wrap">
			<img src="{ {{$widget->getImageUrlColumnId()}}}" />
		</div>
	</div>
{ {/data}}
</script>
	
HTML;
        
        return $output;
    }

    public function getHeightDefault()
    {
        return 12;
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\DataCards */
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
        if ($default_sorters)
            $default_sorters = substr($default_sorters, 0, - 2);
            
            // Filters defined in the UXON description
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fnr => $fltr) {
                // Skip promoted filters, as they are displayed next to quick search
                if ($fltr->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED)
                    continue;
                $fltr_element = $this->getTemplate()->getElement($fltr);
                $filters_js .= $this->getTemplate()->generateJs($fltr);
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
                }
            }
        }
        
        $output = <<<JS

$(document).ready(function() {
	
	{$this->buildJsFunctionPrefix()}load();
	
	{$this->buildJsPagination()}
	
	{$this->buildJsQuicksearch()}
	
	{$this->buildJsRowSelection()}
	
});

function {$this->buildJsFunctionPrefix()}startSlider(){
            
var options = {
  \$AutoPlay: true,
  \$SlideshowOptions: {
    \$Class: \$JssorSlideshowRunner$,
    \$TransitionsOrder: 1
  },
  \$ArrowNavigatorOptions: {
    \$Class: \$JssorArrowNavigator$
  },
  \$ThumbnailNavigatorOptions: {
    \$Class: \$JssorThumbnailNavigator$,
    \$Rows: 1,
    \$Cols: 3,
    \$SpacingX: 14,
    \$SpacingY: 12,
    \$Orientation: 2,
    \$Align: 156
  }
};

var {$this->getId()}slider = new \$JssorSlider$("{$this->getId()}", options);

//responsive code begin
//you can remove responsive code if you don't want the slider scales while window resizing
function ScaleSlider() {
    var refSize = {$this->getId()}slider.\$Elmt.parentNode.clientWidth;
    if (refSize) {
        //refSize = Math.min(refSize, 960);
        //refSize = Math.max(refSize, 300);
        {$this->getId()}slider.\$ScaleWidth(refSize);
    }
    else {
        window.setTimeout(ScaleSlider, 30);
    }
}
ScaleSlider();
$(window).bind("load", ScaleSlider);
$(window).bind("resize", ScaleSlider);
$(window).bind("orientationchange", ScaleSlider);
//responsive code end

}

function {$this->buildJsFunctionPrefix()}load(){
	if ($('#{$this->getId()}').data('loading')) return;
	{$this->buildJsBusyIconShow()}
	$('#{$this->getId()}').data('loading', 1);
	var data = {};
    data.action = '{$widget->getLazyLoadingAction()}';
	data.resource = "{$widget->getPage()->getAliasWithNamespace()}";
	data.element = "{$widget->getId()}";
	data.object = "{$this->getWidget()->getMetaObject()->getId()}";
	{$filters_ajax}
    
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: data,
       method: 'POST',
       success: function(json){
			try {
				var data = $.parseJSON(json);
				if (data.data.length > 0) {
					var template = Handlebars.compile($('#{$this->getId()}_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
			        var elements = $(template(data));
			        $('#{$this->getId()} .slides').append(elements);
			        {$this->buildJsFunctionPrefix()}startSlider();
		        }
		        {$this->buildJsBusyIconHide()}
		        $('#{$this->getId()}').data('loading', 0);
			} catch (err) {
				{$this->buildJsBusyIconHide()}
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
		   {$this->buildJsBusyIconHide()}
		   {$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
		}
	});
}

{$filters_js}

JS;
        
        return $output;
    }

    public function buildJsRefresh($keep_pagination_position = false)
    {
        return $this->buildJsFunctionPrefix() . "load();";
    }

    public function generateHeaders()
    {
        $includes = parent::generateHeaders();
        $includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLteTemplate/Template/js/jssor/skin.css">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jssor/js/jssor.slider.min.js"></script>';
        return $includes;
    }
}
?>