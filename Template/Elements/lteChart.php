<?php
namespace exface\AdminLteTemplate\Template\Elements;
use exface\Core\Widgets\ChartAxis;
use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\Chart;
use exface\Core\Exceptions\UiWidgetConfigException;
class lteChart extends lteDataTable {
	
	public function init(){
		parent::init();
		$this->set_height_default('9');
		// Connect to an external data widget if a data link is specified for this chart
		// TODO $this->register_live_reference_at_linked_element();
	}
	
	function generate_html(){
		$output = '';
		$toolbar = '';
		$widget = $this->get_widget();
		
		// Create the toolbar if the chart has it's own controls and is not bound to another data widget
		if (!$widget->get_data_widget_link()){
			// Add promoted filters above the panel. Other filters will be displayed in a popup via JS
			if ($widget->get_data()->has_filters()){
				foreach ($widget->get_data()->get_filters() as $fltr){
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
					if (!$button->is_hidden()){
						if (!$more_buttons_menu){
							$more_buttons_menu = $this->get_template()->get_workbench()->ui()->get_page_current()->create_widget('MenuButton', $this->get_widget());
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
			
			$bottom_toolbar = $this->build_html_bottom_toolbar($button_html);
			$top_toolbar = $widget->get_hide_toolbar_top() ? '' : $this->build_html_top_toolbar();
		}
		
		// Create the panel for the chart
		$output = <<<HTML
		
<div class="{$this->get_width_classes()} exf_grid_item">
	<div class="box">
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body">
			<div id="{$this->get_id()}" style="height: {$this->get_height()}; width: calc(100% + 8px)"></div>
		</div>
	</div>
	{$this->build_html_chart_customizer()}
</div>

HTML;
		
		return $output;
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\Chart */
		$widget = $this->get_widget();
		
		$output = '';
		$series_data = '';
		
		if ($this->is_pie_chart()){
			$this->get_widget()->set_hide_axes(true);
		}
		
		// Create the function to process fetched data
		$output .= '
			function ' . $this->get_function_prefix() . 'plot(ds){
				';
		
		// Transform the input data to a flot dataset
		foreach ($widget->get_series() as $series){
			$series_id = $this->generate_series_id($series->get_id());
			$output .= '
					var ' . $series_id . ' = [];';
			
			if ($series->get_chart_type() == ChartSeries::CHART_TYPE_PIE){
				$series_data = $series_id . '[i] = { label: ds.data[i]["' . $series->get_axis_x()->get_data_column()->get_data_column_name() . '"], data: ds.data[i]["' . $series->get_data_column()->get_data_column_name() . '"] }' ;
			} else {
				// Prepare the code to transform the ajax data to flot data. It will later run in a for loop.
				switch ($series->get_chart_type()){
					case ChartSeries::CHART_TYPE_BARS:
						$data_key = $series->get_data_column()->get_data_column_name();
						$data_value = $series->get_axis_y()->get_data_column()->get_data_column_name();
						break;
					default:
						$data_key = $series->get_axis_x()->get_data_column()->get_data_column_name();
						$data_value = $series->get_data_column()->get_data_column_name();
				}
				$series_data .= '
							' . $series_id . '[i] = [ (ds.data[i]["' . $data_key  . '"]' . ($series->get_axis_x()->get_axes_type() == 'time' ? '*1000' : '') . '), ds.data[i]["' . $data_value . '"] ];';
			}			
		}
		
		// Prepare other flot options
		$series_config = $this->generate_series_config();
		
		foreach ($widget->get_axes_x() as $axis){
			if (!$axis->is_hidden()){
				$axis_x_init .= ', ' . $this->generate_axis_options($axis);
			}
		}
		foreach ($widget->get_axes_y() as $axis){
			if (!$axis->is_hidden()){
				$axis_y_init .= ', ' . $this->generate_axis_options($axis);
			}
		}
		
		// Plot flot :)
		$output .= '
					for (var i=0; i < ds.data.length; i++){
						' . $series_data . '
					}
		
					$.plot("#' . $this->get_id() . '",
						' . $this->generate_series_data() . ',
						{
							grid:  { ' . $this->generate_grid_options() . ' }
							, crosshair: {mode: "xy"}
							' . ($axis_y_init ? ', yaxes: [ ' . substr($axis_y_init, 2) . ' ]' : '') . '
							' . ($axis_x_init ? ', xaxes: [ ' . substr($axis_x_init, 2) . ' ]' : '') . '
							' . ($series_config ? ', series: { ' . $series_config . ' }' : '') . '
							, legend: { ' . $this->generate_legend_options() . ' }
						}
					);
								
					$(".axisLabels").css("color", "black");
			}';
		
		// Create the load function to fetch the data via AJAX or from another widget
		$output .= $this->build_js_ajax_loader_function();
		$output .= $this->build_js_tooltip_init();

		return $output;
	}
	
	protected function generate_grid_options(){
		return '
											hoverable: true
											, borderColor: "#f3f3f3"
        									, borderWidth: 1
        									, tickColor: "#f3f3f3"';
	}
	
	protected function generate_legend_options(){
		$output = '';
		if ($this->is_pie_chart()){
			$output .= 'show: false';
		} else {
			$output .= 'position: "nw"';
		}
		return $output;
	}
	
	protected function is_pie_chart(){
		if ($this->get_widget()->get_series()[0]->get_chart_type() == ChartSeries::CHART_TYPE_PIE){
			return true;
		} else {
			return false;
		}
	}
	
	protected function generate_series_data(){
		$output = '';
		if ($this->is_pie_chart()){
			if (count($this->get_widget()->get_series()) > 1){
				throw new UiWidgetConfigException('The template "' . $this->get_template()->get_alias() . '" does not support pie charts with multiple series!');
			}
			
			$output = $this->generate_series_id($this->get_widget()->get_series()[0]->get_id());
		} else {
			foreach ($this->get_widget()->get_series() as $series){
				if ($series->get_chart_type() == ChartSeries::CHART_TYPE_PIE){
					throw new UiWidgetConfigException('The template "' . $this->get_template()->get_alias() . '" does not support pie charts with multiple series!');
				}
				$series_options = $this->generate_series_options($series);
				$output .= ',
								{
									data: ' . $this->generate_series_id($series->get_id()) . ($series->get_chart_type() == ChartSeries::CHART_TYPE_BARS ? '.reverse()' : '') . '
									, label: "' . $series->get_caption() . '"
									, yaxis:' . $series->get_axis_y()->get_number() . '
									, xaxis:' . $series->get_axis_x()->get_number() . '
									' . ($series_options ? ', ' . $series_options: '') . '
								}';
			}
			$output = '[' . substr($output, 2) . ']';
		}
		return $output;
	}
	
	/**
	 * Returns the definition of the function elementId_load(urlParams) which is used to fethc the data via AJAX
	 * if the chart is not bound to another data widget (in that case, the data should be provided by that widget).
	 * @return string
	 */
	protected function build_js_ajax_loader_function(){
		$widget = $this->get_widget();
		$output = '';
		if (!$widget->get_data_widget_link()){

			$url_params = '';
			$url_params .= '&resource=' . $this->get_page_id();
			$url_params .= '&element=' . $widget->get_data()->get_id();
			$url_params .= '&object=' . $widget->get_meta_object()->get_id();
			$url_params .= '&action=' . $widget->get_lazy_loading_action();
			
			$post_data = '';
				
			// send sort information
			if (count($widget->get_data()->get_sorters()) > 0){
				$post_data .= 'data.order = [];' . "\n";
				foreach ($widget->get_data()->get_sorters() as $sorter){
					$post_data .= 'data.order.push({attribute_alias: "' . $sorter->attribute_alias . '", dir: "' . $sorter->direction . '"});';
				}
			}
				
			// send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
			if ($widget->get_data()->get_paginate()){
				$post_data .= 'data.start = 0;';
				$post_data .= 'data.length = ' . $widget->get_data()->get_paginate_default_page_size() . ';';
			}
				
			// send preset filters
			if ($widget->get_data()->has_filters()){
				foreach ($widget->get_data()->get_filters() as $fnr => $fltr){
					if ($fltr->get_value()){
						$fltr_element = $this->get_template()->get_element($fltr);
						$post_data .= 'data.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->get_attribute_alias() . ' = ' . $fltr_element->build_js_value_getter() . ";\n";
					}
				}
			}
				
			// Loader function
			$output .= '
				function ' . $this->get_function_prefix() . 'load(urlParams){
					' . $this->build_js_busy_icon_show() . '
					if (!urlParams) urlParams = "";
					var data = {};
					' . $post_data . '
						$.post("' . $this->get_ajax_url() . $url_params . '"+urlParams, data, function(data){
							' . $this->get_function_prefix() . 'plot($.parseJSON(data));
							' . $this->build_js_busy_icon_hide() . '
					});
				}';
				
			// doSearch function with filters for the search button
			$fltrs = array();
			if ($widget->get_data()->has_filters()){
				foreach($widget->get_data()->get_filters() as $fnr => $fltr){
					$fltr_impl = $this->get_template()->get_element($fltr, $this->get_page_id());
					$output .= $fltr_impl->generate_js();
					$fltrs[] = "'&fltr" . str_pad($fnr, 2, 0, STR_PAD_LEFT) . "_" . urlencode($fltr->get_attribute_alias()) . "='+" . $fltr_impl->build_js_value_getter();
				}
				// build JS for the search function
				$output .= '
						function ' .$this->get_function_prefix() . 'doSearch(){
							' . $this->get_function_prefix() . "load(" . implode("+", $fltrs) . ');
						}';
			}
				
			// Call the data loader to populate the Chart initially
			$output .= $this->get_function_prefix() . 'load();';
		}
		
		return $output;
	}
	
	protected function build_js_tooltip_init(){
		// Create a tooltip generator function
		// TODO didn't work because I don't know, how to get the axes infomration from an instantiated plot		
		$output = '
		 $(\'<div class="tooltip-inner" id="' . $this->get_id() . '_tooltip"></div>\').css({
		      position: "absolute",
		      display: "none",
		      opacity: 0.8
		    }).appendTo("body");
		    $("#' . $this->get_id() . '").bind("plothover", function (event, pos, item) {
		      if (item) {
		        var x = new Date(item.datapoint[0]),
		            y = item.datapoint[1].toFixed(2);
		
		        $("#' . $this->get_id() . '_tooltip").html(x.toLocaleDateString() + "<br/>" + item.series.label + ": " + y)
		            .css({top: item.pageY + 5, left: item.pageX + 5})
		            .fadeIn(200);
		      } else {
		        $("#' . $this->get_id() . '_tooltip").hide();
		      }
		
		    });
				';
		return $output;
	}
	
	public function generate_series_id($string){
		return str_replace(array('.', '(', ')', '=', ',', ' '), '_', $string);
	}
	
	public function generate_series_options(ChartSeries $series){
		$options = '';
		switch ($series->get_chart_type()) {
			case ChartSeries::CHART_TYPE_LINE: 
			case ChartSeries::CHART_TYPE_AREA:
				$options = 'lines: 
								{
									show: true,
									' . ($series->get_chart_type() == ChartSeries::CHART_TYPE_AREA ? 'fill: true' : '') . '
								}'; 
				break;
			case ChartSeries::CHART_TYPE_BARS:  
			case ChartSeries::CHART_TYPE_COLUMNS: 
				$options = 'bars: 
								{
									show: true 
									, align: "center"
									' . (!$series->get_chart()->get_stack_series() && count($series->get_chart()->get_series_by_chart_type($series->get_chart_type())) > 1 ? ', barWidth: 0.2, order: ' . $series->get_series_number() : '') . '
									';
				if ($series->get_axis_x()->get_axes_type() == ChartAxis::AXIS_TYPE_TIME || $series->get_axis_y()->get_axes_type() == ChartAxis::AXIS_TYPE_TIME){
					$options .= '
									, barWidth: 24*60*60*1000';
				}
				if ($series->get_chart_type() == ChartSeries::CHART_TYPE_BARS){
					$options .= '
									, horizontal: true';
				}
				$options .= '
								}'; 
				break;
			case ChartSeries::CHART_TYPE_PIE: 
				$options = 'pie: {show: true}'; 
				break;
		}
		return $options;		
	}
	
	private function generate_axis_options(ChartAxis $axis){
		/* @var $widget \exface\Core\Widgets\Chart */
		$widget = $this->get_widget(); 
		$output = '{
								position: "' . strtolower($axis->get_position()) . '"' . ($axis->get_position() == ChartAxis::POSITION_RIGHT || $axis->get_position() == ChartAxis::POSITION_TOP ? ', alignTicksWithAxis: 1' : '')
								. (!$axis->get_hide_caption() ? ', axisLabel: "' . $axis->get_caption() . '"' : '')
								. (is_numeric($axis->get_min_value()) ? ', min: ' . $axis->get_min_value() : '')
								. (is_numeric($axis->get_max_value()) ? ', max: ' . $axis->get_max_value() : '')
								;
		
		switch ($axis->get_axes_type()){
			case 'text': $output .= '
								, mode: "categories"'; break;
			case 'time': $output .= '
								, mode: "time"'; break;
			default:
		}
		
		$output .= '
					}';
		return $output;
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		// flot 
		$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.resize.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.categories.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.time.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.crosshair.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/flot/plugins/axislabels/jquery.flot.axislabels.js"></script>';
		$includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/flot/plugins/jquery.flot.orderBars.js"></script>';
		
		if ($this->get_widget()->get_stack_series()){
			$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.stack.js"></script>';
		}
		
		if ($this->is_pie_chart()){
			$includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.pie.js"></script>';
		}
		
		// masonry for proper filter alignment
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
		return $includes;
	}
	
	/**
	 * Makes sure, the Chart is always updated, once the linked data widget loads new data - of course, only if there is a data link defined!
	 * @return euiChart
	 */
	protected function register_live_reference_at_linked_element(){
		if ($link = $this->get_widget()->get_data_widget_link()){
			/* @var $linked_element \exface\Templates\jEasyUI\Widgets\euiData */
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($linked_element){
				$linked_element->add_on_load_success($this->build_js_live_refrence());
			}
		}
		return $this;
	}
	
	protected function build_js_live_refrence(){
		$output = '';
		if ($link = $this->get_widget()->get_data_widget_link()){
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			$output .= $this->get_function_prefix() . 'plot(' . $linked_element->build_js_data_getter() . ".rows);";
		}
		return $output;
	}
	
	/**
	 * @return Chart
	 * @see \exface\Templates\jeasyui\Widgets\euiAbstractWidget::get_widget()
	 */
	public function get_widget(){
		return parent::get_widget();
	}
	
	protected function generate_series_config(){
		$output = '';
		$config_array = array();
		foreach ($this->get_widget()->get_series() as $series){
			switch ($series->get_chart_type()){
				case ChartSeries::CHART_TYPE_PIE:
					$config_array[$series->get_chart_type()]['show'] = 'show: true';
					$config_array[$series->get_chart_type()]['radius'] = 'radius: 1';
					$config_array[$series->get_chart_type()]['label'] = 'label: {
							show: true, 
							radius: 0.8, 
							formatter: function (label, series) {
								return "<div style=\'font-size:8pt; text-align:center; padding:2px; color:white;\'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
							}, 
							background: {opacity: 0.8}}';
					break;
				case ChartSeries::CHART_TYPE_COLUMNS:
				case ChartSeries::CHART_TYPE_BARS:
					
					break;
				default: break;
			}
		}
		
		if ($this->get_widget()->get_stack_series()){
			$config_array['stack'] = 'true';
		}
		
		foreach ($config_array as $chart_type => $options){
			$output .= $chart_type . ': ' . (is_array($options) ? '{' . implode (', ', $options) . '}' : $options) . ', ';
		}
		
		$output = $output ? substr($output, 0, -2) : $output;
		return $output;
	}
	
	private function build_html_chart_customizer(){
		$filters_html = '';
		/* @var $widget \exface\Core\Widgets\Chart */
		$widget = $this->get_widget();
	
		foreach ($widget->get_data()->get_filters() as $fltr){
			$filters_html .= $this->get_template()->generate_html($fltr);
		}
	
		$output = <<<HTML
	
<div class="modal" id="{$this->get_id()}_popup_config">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Table settings</h4>
			</div>
			<div class="modal-body">
				<div role="tabpanel">
	
					<!-- Nav tabs -->
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" class="active"><a href="#{$this->get_id()}_popup_filters" aria-controls="{$this->get_id()}_popup_filters" role="tab" data-toggle="tab">Filters</a></li>
					</ul>
					
					<!-- Tab panes -->
					<div class="tab-content">
						<div role="tabpanel" class="tab-pane active" id="{$this->get_id()}_popup_filters">{$filters_html}</div>
					</div>
			
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left">Cancel</button>
				<button type="button" href="#" data-dismiss="modal" class="btn btn-primary" onclick="{$this->get_function_prefix()}doSearch();">OK</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
	
HTML;
		return $output;
	}
	
	protected function build_html_top_toolbar(){
		$table_caption = $this->get_widget()->get_caption() ? $this->get_widget()->get_caption() : $this->get_meta_object()->get_name();
	
		$output = <<<HTML
		
		<h3 class="box-title">$table_caption</h3>
		<div class="box-tools pull-right">
			<button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#{$this->get_id()}_popup_config"><i class="fa fa-filter"></i></button>
			<button type="button" class="btn btn-box-tool" onclick="{$this->get_function_prefix()}doSearch(); return false;"><i class="fa fa-refresh"></i></button>
		</div>
			
HTML;
		return $output;
	}
}
?>