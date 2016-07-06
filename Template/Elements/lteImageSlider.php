<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 * 
 * @author PATRIOT
 *
 */
class lteImageSlider extends lteDataList {
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\ImageGallery */
		$widget = $this->get_widget();
		$top_toolbar = $this->generate_html_top_toolbar();
		
		// output the html code
		$output = <<<HTML

<div class="{$this->get_width_classes()} exf_grid_item">
	<div class="box" style="height: {$this->get_height()}">
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body no-padding">
			<div id="{$this->get_id()}" style="position: relative; margin: 0 auto; top: 0px; left: 0px; width: 960px; height: 400px; overflow: hidden; visibility: hidden;">
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
	{$this->generate_html_table_customizer()}
</div>
					
<script type="text/x-handlebars-template" id="{$this->get_id()}_tpl">
{ {#data}}
    <div data-p="150.00" style="display: none;">
		<div data-u="image" class="img-wrap" >
			<img src="{ {{$widget->get_image_url_column_id()}}}"/>
		</div>
		<div data-u="thumb" class="thumb-wrap">
			<img src="{ {{$widget->get_image_url_column_id()}}}" />
		</div>
	</div>
{ {/data}}
</script>
	
HTML;
		
		return $output;
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
				$filters_js .= $this->get_template()->generate_js($fltr);
				$filters_ajax .= 'data.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = ' . $fltr_element->get_js_value_getter() . ";\n";
				
				// Here we generate some JS make the filter visible by default, once it gets used.
				// This code will be called when the table's config page gets closed.
				if (!$fltr->is_hidden()){
					$filters_js_promoted .= "
							if (" . $fltr_element->get_js_value_getter() . " && $('#" . $fltr_element->get_id() . "').parents('#{$this->get_id()}_popup_config').length > 0){
								var fltr = $('#" . $fltr_element->get_id() . "').parents('.exf_input');
								var ui_block = $('<div class=\"col-xs-12 col-sm-6 col-md-4 col-lg-3\"></div>').appendTo('#{$this->get_id()}_filters_container');
								fltr.detach().appendTo(ui_block).trigger('resize');
								$('#{$this->get_id()}_filters_container').show();
							}
					";
				}
			}
		}
		
		// buttons
		if ($widget->has_buttons()){
			foreach ($widget->get_buttons() as $button){
				$buttons_js .= $this->get_template()->generate_js($button);
			}
		}
		
		$output = <<<JS

$(document).ready(function() {
	
	{$this->get_function_prefix()}load();
	
	{$this->get_js_pagination()}
	
	{$this->get_js_quicksearch()}
	
	{$this->get_js_row_selection()}
	
});

function {$this->get_function_prefix()}startSlider(){
            
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

var {$this->get_id()}slider = new \$JssorSlider$("{$this->get_id()}", options);

//responsive code begin
//you can remove responsive code if you don't want the slider scales while window resizing
function ScaleSlider() {
    var refSize = {$this->get_id()}slider.\$Elmt.parentNode.clientWidth;
    if (refSize) {
        //refSize = Math.min(refSize, 960);
        //refSize = Math.max(refSize, 300);
        {$this->get_id()}slider.\$ScaleWidth(refSize);
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

function {$this->get_function_prefix()}load(){
	if ($('#{$this->get_id()}').data('loading')) return;
	{$this->get_js_busy_icon_show()}
	$('#{$this->get_id()}').data('loading', 1);
	var data = {};
    data.action = '{$widget->get_lazy_loading_action()}';
	data.resource = "{$this->get_page_id()}";
	data.element = "{$widget->get_id()}";
	data.object = "{$this->get_widget()->get_meta_object()->get_id()}";
	{$filters_ajax}
    
    $.post("{$this->get_ajax_url()}", data, function(json){
		var data = $.parseJSON(json);
		if (data.data.length > 0) {
			var template = Handlebars.compile($('#{$this->get_id()}_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
	        var elements = $(template(data));
	        $('#{$this->get_id()} .slides').append(elements);
	        {$this->get_function_prefix()}startSlider();
        }
        {$this->get_js_busy_icon_hide()}
        $('#{$this->get_id()}').data('loading', 0);
	});
}

{$filters_js}

JS;
		
		return $output;
	}
	
	public function get_js_refresh($keep_pagination_position = false){
		return $this->get_function_prefix() . "load();";
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		$includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLteTemplate/Template/js/jssor/skin.css">';
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jssor/js/jssor.slider.mini.js"></script>';
		return $includes;
	}
}
?>