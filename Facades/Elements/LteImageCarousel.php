<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\DataCarousel;

/**
 *
 * @author Andrej Kabachnik
 * 
 * @method DataCarousel getWidget()
 *        
 */
class LteImageCarousel extends lteSplitVertical
{

    function buildHtml()
    {
        $widget = $this->getWidget();
        $galleryElement = $this->getFacade()->getElement($widget->getDataWidget());
        $top_toolbar = $galleryElement->buildHtmlHeader();
        
        // output the html code
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} {$this->buildCssWidthClasses()}">
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
		                        <div data-u="thumbnailfacade" class="t"></div>
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
	{$galleryElement->buildHtmlTableCustomizer()}
</div>
					
<script type="text/x-handlebars-facade" id="{$this->getId()}_tpl">
{ {#data}}
    <div data-p="150.00" style="display: none;">
		<div data-u="image" class="img-wrap" >
			<img src="{ {{$widget->getImageUrlColumn()->getDataColumnName()}}}"/>
		</div>
		<div data-u="thumb" class="thumb-wrap">
			<img src="{ {{$widget->getImageUrlColumn()->getDataColumnName()}}}" />
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
        $widget = $this->getWidget();
        $galleryWidget = $widget->getDataWidget();
        $galleryElement = $this->getFacade()->getElement($galleryWidget);
        
        $output = <<<JS

$(document).ready(function() {
	
	{$this->buildJsFunctionPrefix()}load();
	
	{$galleryElement->buildJsPagination()}
	
	{$galleryElement->buildJsQuicksearch()}
	
	{$galleryElement->buildJsRowSelection()}
	
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
    data.action = '{$galleryWidget->getLazyLoadingActionAlias()}';
	data.resource = "{$galleryWidget->getPage()->getAliasWithNamespace()}";
	data.element = "{$galleryWidget->getId()}";
	data.object = "{$galleryWidget->getMetaObject()->getId()}";
	data.data = {$this->getFacade()->getElement($galleryWidget->getConfiguratorWidget())->buildJsDataGetter()};
    
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: data,
       method: 'POST',
       success: function(json){
			try {
				var data = json;
				if (data.data.length > 0) {
					var facade = Handlebars.compile($('#{$this->getId()}_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
			        var elements = $(facade(data));
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
        $includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLTEFacade/Facades/js/jssor/skin.css">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jssor/js/jssor.slider.min.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/components/handlebars.js/handlebars.min.js"></script>';
        return $includes;
    }
}
?>