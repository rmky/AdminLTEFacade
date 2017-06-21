<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\ChartAxis;
use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\Chart;
use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class lteChart extends lteDataTable
{

    public function init()
    {
        parent::init();
        $this->setHeightDefault('9');
        // Connect to an external data widget if a data link is specified for this chart
        // TODO $this->registerLiveReferenceAtLinkedElement();
    }

    function generateHtml()
    {
        $output = '';
        $toolbar = '';
        $widget = $this->getWidget();
        
        // Create the toolbar if the chart has it's own controls and is not bound to another data widget
        if (! $widget->getDataWidgetLink()) {
            // Add promoted filters above the panel. Other filters will be displayed in a popup via JS
            if ($widget->getData()->hasFilters()) {
                foreach ($widget->getData()->getFilters() as $fltr) {
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
                    if (! $button->isHidden()) {
                        if (! $more_buttons_menu) {
                            $more_buttons_menu = $this->getTemplate()->getWorkbench()->ui()->getPageCurrent()->createWidget('MenuButton', $this->getWidget());
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
            
            $bottom_toolbar = $this->buildHtmlBottomToolbar($button_html);
            $top_toolbar = $widget->getHideToolbarTop() ? '' : $this->buildHtmlTopToolbar();
        }
        
        // Create the panel for the chart
        $output = <<<HTML

<div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
	<div class="box">
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body">
			<div id="{$this->getId()}" style="height: {$this->getHeight()}; width: calc(100% + 8px)"></div>
		</div>
	</div>
	{$this->buildHtmlChartCustomizer()}
</div>

HTML;
        
        return $output;
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        
        $output = '';
        $series_data = '';
        
        if ($this->isPieChart()) {
            $this->getWidget()->setHideAxes(true);
        }
        
        // Create the function to process fetched data
        $output .= '
			function ' . $this->buildJsFunctionPrefix() . 'plot(ds){
				';
        
        // Transform the input data to a flot dataset
        foreach ($widget->getSeries() as $series) {
            $series_id = $this->generateSeriesId($series->getId());
            $output .= '
					var ' . $series_id . ' = [];';
            
            if ($series->getChartType() == ChartSeries::CHART_TYPE_PIE) {
                $series_data = $series_id . '[i] = { label: ds.data[i]["' . $series->getAxisX()->getDataColumn()->getDataColumnName() . '"], data: ds.data[i]["' . $series->getDataColumn()->getDataColumnName() . '"] }';
            } else {
                // Prepare the code to transform the ajax data to flot data. It will later run in a for loop.
                switch ($series->getChartType()) {
                    case ChartSeries::CHART_TYPE_BARS:
                        $data_key = $series->getDataColumn()->getDataColumnName();
                        $data_value = $series->getAxisY()->getDataColumn()->getDataColumnName();
                        break;
                    default:
                        $data_key = $series->getAxisX()->getDataColumn()->getDataColumnName();
                        $data_value = $series->getDataColumn()->getDataColumnName();
                }
                $series_data .= '
							' . $series_id . '[i] = [ (ds.data[i]["' . $data_key . '"]' . ($series->getAxisX()->getAxisType() == 'time' ? '*1000' : '') . '), ds.data[i]["' . $data_value . '"] ];';
            }
        }
        
        // Prepare other flot options
        $series_config = $this->generateSeriesConfig();
        
        foreach ($widget->getAxesX() as $axis) {
            if (! $axis->isHidden()) {
                $axis_x_init .= ', ' . $this->generateAxisOptions($axis);
            }
        }
        foreach ($widget->getAxesY() as $axis) {
            if (! $axis->isHidden()) {
                $axis_y_init .= ', ' . $this->generateAxisOptions($axis);
            }
        }
        
        // Plot flot :)
        $output .= '
					for (var i=0; i < ds.data.length; i++){
						' . $series_data . '
					}
		
					$.plot("#' . $this->getId() . '",
						' . $this->generateSeriesData() . ',
						{
							grid:  { ' . $this->generateGridOptions() . ' }
							, crosshair: {mode: "xy"}
							' . ($axis_y_init ? ', yaxes: [ ' . substr($axis_y_init, 2) . ' ]' : '') . '
							' . ($axis_x_init ? ', xaxes: [ ' . substr($axis_x_init, 2) . ' ]' : '') . '
							' . ($series_config ? ', series: { ' . $series_config . ' }' : '') . '
							, legend: { ' . $this->generateLegendOptions() . ' }
						}
					);
								
					$(".axisLabels").css("color", "black");
			}';
        
        // Create the load function to fetch the data via AJAX or from another widget
        $output .= $this->buildJsAjaxLoaderFunction();
        $output .= $this->buildJsTooltipInit();
        
        return $output;
    }

    protected function generateGridOptions()
    {
        return '
											hoverable: true
											, borderColor: "#f3f3f3"
        									, borderWidth: 1
        									, tickColor: "#f3f3f3"';
    }

    protected function generateLegendOptions()
    {
        $output = '';
        if ($this->isPieChart()) {
            $output .= 'show: false';
        } else {
            $output .= 'position: "nw"';
        }
        return $output;
    }

    protected function isPieChart()
    {
        if ($this->getWidget()->getSeries()[0]->getChartType() == ChartSeries::CHART_TYPE_PIE) {
            return true;
        } else {
            return false;
        }
    }

    protected function generateSeriesData()
    {
        $output = '';
        if ($this->isPieChart()) {
            if (count($this->getWidget()->getSeries()) > 1) {
                throw new TemplateUnsupportedWidgetPropertyWarning('The template "' . $this->getTemplate()->getAlias() . '" does not support pie charts with multiple series!');
            }
            
            $output = $this->generateSeriesId($this->getWidget()->getSeries()[0]->getId());
        } else {
            foreach ($this->getWidget()->getSeries() as $series) {
                if ($series->getChartType() == ChartSeries::CHART_TYPE_PIE) {
                    throw new TemplateUnsupportedWidgetPropertyWarning('The template "' . $this->getTemplate()->getAlias() . '" does not support pie charts with multiple series!');
                }
                $series_options = $this->generateSeriesOptions($series);
                $output .= ',
								{
									data: ' . $this->generateSeriesId($series->getId()) . ($series->getChartType() == ChartSeries::CHART_TYPE_BARS ? '.reverse()' : '') . '
									, label: "' . $series->getCaption() . '"
									, yaxis:' . $series->getAxisY()->getNumber() . '
									, xaxis:' . $series->getAxisX()->getNumber() . '
									' . ($series_options ? ', ' . $series_options : '') . '
								}';
            }
            $output = '[' . substr($output, 2) . ']';
        }
        return $output;
    }

    /**
     * Returns the definition of the function elementId_load(urlParams) which is used to fethc the data via AJAX
     * if the chart is not bound to another data widget (in that case, the data should be provided by that widget).
     *
     * @return string
     */
    protected function buildJsAjaxLoaderFunction()
    {
        $widget = $this->getWidget();
        $output = '';
        if (! $widget->getDataWidgetLink()) {
            
            $url_params = '';
            $url_params .= '&resource=' . $this->getPageId();
            $url_params .= '&element=' . $widget->getData()->getId();
            $url_params .= '&object=' . $widget->getMetaObject()->getId();
            $url_params .= '&action=' . $widget->getLazyLoadingAction();
            
            $post_data = '';
            
            // send sort information
            if (count($widget->getData()->getSorters()) > 0) {
                $post_data .= 'data.order = [];' . "\n";
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $post_data .= 'data.order.push({attribute_alias: "' . $sorter->attribute_alias . '", dir: "' . $sorter->direction . '"});';
                }
            }
            
            // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
            if ($widget->getData()->getPaginate()) {
                $post_data .= 'data.start = 0;';
                $post_data .= 'data.length = ' . (!is_null($widget->getData()->getPaginatePageSize()) ? $widget->getData()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ';';
            }
            
            // send preset filters
            if ($widget->getData()->hasFilters()) {
                foreach ($widget->getData()->getFilters() as $fnr => $fltr) {
                    if ($fltr->getValue()) {
                        $fltr_element = $this->getTemplate()->getElement($fltr);
                        $post_data .= 'data.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = ' . $fltr_element->buildJsValueGetter() . ";\n";
                    }
                }
            }
            
            // Loader function
            $output .= '
				function ' . $this->buildJsFunctionPrefix() . 'load(urlParams){
					' . $this->buildJsBusyIconShow() . '
					var data = {};
					' . $post_data . '
					if (!urlParams) urlParams = "";
					$.ajax({
						url: "' . $this->getAjaxUrl() . $url_params . '"+urlParams,
						data: data,
						success: function(data){
							' . $this->buildJsFunctionPrefix() . 'plot($.parseJSON(data));
							' . $this->buildJsBusyIconHide() . '
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				}';
            
            // doSearch function with filters for the search button
            $fltrs = array();
            if ($widget->getData()->hasFilters()) {
                foreach ($widget->getData()->getFilters() as $fnr => $fltr) {
                    $fltr_impl = $this->getTemplate()->getElement($fltr, $this->getPageId());
                    $output .= $fltr_impl->generateJs();
                    $fltrs[] = "'&fltr" . str_pad($fnr, 2, 0, STR_PAD_LEFT) . "_" . urlencode($fltr->getAttributeAlias()) . "='+" . $fltr_impl->buildJsValueGetter();
                }
                // build JS for the search function
                $output .= '
						function ' . $this->buildJsFunctionPrefix() . 'doSearch(){
							' . $this->buildJsFunctionPrefix() . "load(" . implode("+", $fltrs) . ');
						}';
            }
            
            // Call the data loader to populate the Chart initially
            $output .= $this->buildJsFunctionPrefix() . 'load();';
        }
        
        return $output;
    }

    protected function buildJsTooltipInit()
    {
        // Create a tooltip generator function
        // TODO didn't work because I don't know, how to get the axes infomration from an instantiated plot
        $output = '
		 $(\'<div class="tooltip-inner" id="' . $this->getId() . '_tooltip"></div>\').css({
		      position: "absolute",
		      display: "none",
		      opacity: 0.8
		    }).appendTo("body");
		    $("#' . $this->getId() . '").bind("plothover", function (event, pos, item) {
		      if (item) {
		        var x = new Date(item.datapoint[0]),
		            y = item.datapoint[1].toFixed(2);
		
		        $("#' . $this->getId() . '_tooltip").html(x.toLocaleDateString() + "<br/>" + item.series.label + ": " + y)
		            .css({top: item.pageY + 5, left: item.pageX + 5})
		            .fadeIn(200);
		      } else {
		        $("#' . $this->getId() . '_tooltip").hide();
		      }
		
		    });
				';
        return $output;
    }

    public function generateSeriesId($string)
    {
        return str_replace(array(
            '.',
            '(',
            ')',
            '=',
            ',',
            ' '
        ), '_', $string);
    }

    public function generateSeriesOptions(ChartSeries $series)
    {
        $options = '';
        switch ($series->getChartType()) {
            case ChartSeries::CHART_TYPE_LINE:
            case ChartSeries::CHART_TYPE_AREA:
                $options = 'lines: 
								{
									show: true,
									' . ($series->getChartType() == ChartSeries::CHART_TYPE_AREA ? 'fill: true' : '') . '
								}';
                break;
            case ChartSeries::CHART_TYPE_BARS:
            case ChartSeries::CHART_TYPE_COLUMNS:
                $options = 'bars: 
								{
									show: true 
									, align: "center"
									' . (! $series->getChart()->getStackSeries() && count($series->getChart()->getSeriesByChartType($series->getChartType())) > 1 ? ', barWidth: 0.2, order: ' . $series->getSeriesNumber() : '') . '
									';
                if ($series->getAxisX()->getAxisType() == ChartAxis::AXIS_TYPE_TIME || $series->getAxisY()->getAxisType() == ChartAxis::AXIS_TYPE_TIME) {
                    $options .= '
									, barWidth: 24*60*60*1000';
                }
                if ($series->getChartType() == ChartSeries::CHART_TYPE_BARS) {
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

    private function generateAxisOptions(ChartAxis $axis)
    {
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        $output = '{
								position: "' . strtolower($axis->getPosition()) . '"' . ($axis->getPosition() == ChartAxis::POSITION_RIGHT || $axis->getPosition() == ChartAxis::POSITION_TOP ? ', alignTicksWithAxis: 1' : '') . (! $axis->getHideCaption() ? ', axisLabel: "' . $axis->getCaption() . '"' : '') . (is_numeric($axis->getMinValue()) ? ', min: ' . $axis->getMinValue() : '') . (is_numeric($axis->getMaxValue()) ? ', max: ' . $axis->getMaxValue() : '');
        
        switch ($axis->getAxisType()) {
            case ChartAxis::AXIS_TYPE_TEXT:
                $output .= '
								, mode: "categories"';
                break;
            case ChartAxis::AXIS_TYPE_TIME:
                $output .= '
								, mode: "time"';
                break;
            default:
        }
        
        $output .= '
					}';
        return $output;
    }

    public function generateHeaders()
    {
        $includes = parent::generateHeaders();
        // flot
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.resize.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.categories.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.time.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.crosshair.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/flot/plugins/axislabels/jquery.flot.axislabels.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Template/js/flot/plugins/jquery.flot.orderBars.js"></script>';
        
        if ($this->getWidget()->getStackSeries()) {
            $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.stack.js"></script>';
        }
        
        if ($this->isPieChart()) {
            $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/flot-charts/jquery.flot.pie.js"></script>';
        }
        return $includes;
    }

    /**
     * Makes sure, the Chart is always updated, once the linked data widget loads new data - of course, only if there is a data link defined!
     *
     * @return euiChart
     */
    protected function registerLiveReferenceAtLinkedElement()
    {
        if ($link = $this->getWidget()->getDataWidgetLink()) {
            /* @var $linked_element \exface\Templates\jEasyUI\Widgets\euiData */
            $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
            if ($linked_element) {
                $linked_element->addOnLoadSuccess($this->buildJsLiveRefrence());
            }
        }
        return $this;
    }

    protected function buildJsLiveRefrence()
    {
        $output = '';
        if ($link = $this->getWidget()->getDataWidgetLink()) {
            $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
            $output .= $this->buildJsFunctionPrefix() . 'plot(' . $linked_element->buildJsDataGetter() . ".rows);";
        }
        return $output;
    }

    /**
     *
     * @return Chart
     * @see \exface\Templates\jeasyui\Widgets\euiAbstractWidget::getWidget()
     */
    public function getWidget()
    {
        return parent::getWidget();
    }

    protected function generateSeriesConfig()
    {
        $output = '';
        $config_array = array();
        foreach ($this->getWidget()->getSeries() as $series) {
            switch ($series->getChartType()) {
                case ChartSeries::CHART_TYPE_PIE:
                    $config_array[$series->getChartType()]['show'] = 'show: true';
                    $config_array[$series->getChartType()]['radius'] = 'radius: 1';
                    $config_array[$series->getChartType()]['label'] = 'label: {
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
                default:
                    break;
            }
        }
        
        if ($this->getWidget()->getStackSeries()) {
            $config_array['stack'] = 'true';
        }
        
        foreach ($config_array as $chart_type => $options) {
            $output .= $chart_type . ': ' . (is_array($options) ? '{' . implode(', ', $options) . '}' : $options) . ', ';
        }
        
        $output = $output ? substr($output, 0, - 2) : $output;
        return $output;
    }

    private function buildHtmlChartCustomizer()
    {
        $filters_html = '';
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        
        foreach ($widget->getData()->getFilters() as $fltr) {
            $filters_html .= $this->getTemplate()->generateHtml($fltr);
        }
        
        $output = <<<HTML
	
<div class="modal" id="{$this->getId()}_popup_config">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Table settings</h4>
			</div>
			<div class="modal-body">
				<div class="modal-body-content-wrapper">
					<div role="tabpanel">
		
						<!-- Nav tabs -->
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="active"><a href="#{$this->getId()}_popup_filters" aria-controls="{$this->getId()}_popup_filters" role="tab" data-toggle="tab">Filters</a></li>
						</ul>
						
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane active" id="{$this->getId()}_popup_filters">{$filters_html}</div>
						</div>
				
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left">Cancel</button>
				<button type="button" href="#" data-dismiss="modal" class="btn btn-primary" onclick="{$this->buildJsFunctionPrefix()}doSearch();">OK</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
	
HTML;
        return $output;
    }

    protected function buildHtmlTopToolbar()
    {
        $table_caption = $this->getWidget()->getCaption() ? $this->getWidget()->getCaption() : $this->getMetaObject()->getName();
        
        $output = <<<HTML
		
		<h3 class="box-title">$table_caption</h3>
		<div class="box-tools pull-right">
			<button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#{$this->getId()}_popup_config"><i class="fa fa-filter"></i></button>
			<button type="button" class="btn btn-box-tool" onclick="{$this->buildJsFunctionPrefix()}doSearch(); return false;"><i class="fa fa-refresh"></i></button>
		</div>
			
HTML;
        return $output;
    }
    
    /**
     * Determines the number of columns of a widget, based on the width of widget, the number
     * of columns of the parent layout widget and the default number of columns of the widget.
     *
     * @return number
     */
    public function getNumberOfColumns()
    {
        if (! $this->searched_for_number_of_columns) {
            $widget = $this->getWidget();
            if (! is_null($widget->getNumberOfColumns())) {
                $this->number_of_columns = $widget->getNumberOfColumns();
            } elseif ($widget->getWidth()->isRelative() && !$widget->getWidth()->isMax()) {
                $width = $widget->getWidth()->getValue();
                if ($width < 1) {
                    $width = 1;
                }
                $this->number_of_columns = $width;
            } else {
                $this->number_of_columns = $this->getTemplate()->getConfig()->getOption("WIDGET.CHART.COLUMNS_BY_DEFAULT");
            }
            $this->searched_for_number_of_columns = true;
        }
        return $this->number_of_columns;
    }
}
?>