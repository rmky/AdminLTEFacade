<?php
namespace exface\AdminLteTemplate\Templates\Elements;

/**
 *
 * @author PATRIOT
 *        
 */
class lteImageSlider extends lteDataCards
{

    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\ImageGallery */
        $widget = $this->getWidget();
        $top_toolbar = $this->buildHtmlHeader();
        
        // output the html code
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
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

    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 12) . 'px';
    }

    function buildJs()
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
        if ($default_sorters) {
            $default_sorters = substr($default_sorters, 0, - 2);
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
    data.action = '{$widget->getLazyLoadingActionAlias()}';
	data.resource = "{$widget->getPage()->getAliasWithNamespace()}";
	data.element = "{$widget->getId()}";
	data.object = "{$widget->getMetaObject()->getId()}";
	data.data = {$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter()};
    
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: data,
       method: 'POST',
       success: function(json){
			try {
				var data = json;
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

    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        $includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLteTemplate/Templates/js/jssor/skin.css">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jssor/js/jssor.slider.min.js"></script>';
        return $includes;
    }
}
?>