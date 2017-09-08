<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\ChartAxis;
use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\Chart;
use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarsTrait;
use exface\Core\CommonLogic\Constants\Icons;

class lteChart extends lteDataTable
{
    use JqueryToolbarsTrait;

    public function init()
    {
        parent::init();
        $this->setHeightDefault('9');
        // Connect to an external data widget if a data link is specified for this chart
        // TODO $this->registerLiveReferenceAtLinkedElement();
    }

    public function generateHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        
        // Create the toolbar if the chart has it's own controls and is not bound to another data widget
        if (! $widget->getDataWidgetLink()) {
            $header = $widget->getHideHeader() ? '' : $this->buildHtmlHeader();
        }
        
        // Create the panel for the chart
        $output = <<<HTML

<div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
    <div class="box">
        <div class="box-header">
            {$header}
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
            $series_id = $this->sanitizeSeriesId($series->getId());
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
        $series_config = $this->buildJsSeriesConfig();
        
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
						' . $this->buildJsSeriesData() . ',
						{
							grid:  { ' . $this->buildJsGridOptions() . ' }
							, crosshair: {mode: "xy"}
							' . ($axis_y_init ? ', yaxes: [ ' . substr($axis_y_init, 2) . ' ]' : '') . '
							' . ($axis_x_init ? ', xaxes: [ ' . substr($axis_x_init, 2) . ' ]' : '') . '
							' . ($series_config ? ', series: { ' . $series_config . ' }' : '') . '
							, legend: { ' . $this->buildJsLegendOptions() . ' }
						}
					);
								
					$(".axisLabels").css("color", "black");
			}';
        
        // Create the load function to fetch the data via AJAX or from another widget
        $output .= $this->buildJsAjaxLoaderFunction();
        // Initialize tooltips
        $output .= $this->buildJsTooltipInit();
        // Add JS code for the configurator
        $output .= $this->getTemplate()->getElement($widget->getConfiguratorWidget())->generateJs();
        // Add JS for all buttons
        $output .= $this->buildJsButtons();
        
        // Starten des Layouters wenn der Konfigurator angezeigt wird.
        $output .= <<<JS

    {$this->buildJsTableCustomizerOnShownFunction()}
    $("#{$this->getId()}_popup_config").on("shown.bs.modal", function() {
        {$this->buildJsFunctionPrefix()}tableCustomizerOnShown();
    });
JS;
        
        return $output;
    }

    protected function buildJsGridOptions()
    {
        return '
											hoverable: true
											, borderColor: "#f3f3f3"
        									, borderWidth: 1
        									, tickColor: "#f3f3f3"';
    }

    protected function buildJsLegendOptions()
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

    protected function buildJsSeriesData()
    {
        $output = '';
        if ($this->isPieChart()) {
            if (count($this->getWidget()->getSeries()) > 1) {
                throw new TemplateUnsupportedWidgetPropertyWarning('The template "' . $this->getTemplate()->getAlias() . '" does not support pie charts with multiple series!');
            }
            
            $output = $this->sanitizeSeriesId($this->getWidget()->getSeries()[0]->getId());
        } else {
            foreach ($this->getWidget()->getSeries() as $series) {
                if ($series->getChartType() == ChartSeries::CHART_TYPE_PIE) {
                    throw new TemplateUnsupportedWidgetPropertyWarning('The template "' . $this->getTemplate()->getAlias() . '" does not support pie charts with multiple series!');
                }
                $series_options = $this->buildJsSeriesOptions($series);
                $output .= ',
								{
									data: ' . $this->sanitizeSeriesId($series->getId()) . ($series->getChartType() == ChartSeries::CHART_TYPE_BARS ? '.reverse()' : '') . '
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
            
            $post_data = '
                            data.resource = "' . $this->getPageAlias() . '";
                            data.element = "' . $widget->getData()->getId() . '";
                            data.object = "' . $widget->getMetaObject()->getId() . '";
                            data.action = "' . $widget->getLazyLoadingAction() . '";
            ';
            
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
                $post_data .= 'data.length = ' . (! is_null($widget->getData()->getPaginatePageSize()) ? $widget->getData()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ';';
            }
            
            // Loader function
            $output .= '
				function ' . $this->buildJsFunctionPrefix() . 'load(){
					' . $this->buildJsBusyIconShow() . '
					var data = { };
					' . $post_data . '
                    data.data = ' . $this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . '
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
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
            
            // Call the data loader to populate the Chart initially
            $output .= $this->buildJsRefresh();
        }
        
        return $output;
    }

    public function buildJsRefresh()
    {
        return $this->buildJsFunctionPrefix() . 'load();';
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

    public function sanitizeSeriesId($string)
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

    public function buildJsSeriesOptions(ChartSeries $series)
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
            $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageAlias());
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
            $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageAlias());
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

    protected function buildJsSeriesConfig()
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
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        
        $output = <<<HTML

<div class="modal" id="{$this->getId()}_popup_config">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$this->translate('WIDGET.CHART.SETTINGS_DIALOG_TITLE')}</h4>
            </div>
            <div class="modal-body">
                {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->generateHtml()}
            </div>
            <div class="modal-footer">
                <button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left"><i class="{$this->buildCssIconClass(Icons::TIMES)}"></i> {$this->getWorkbench()->getCoreApp()->getTranslator()->translate('ACTION.SHOWDIALOG.CANCEL_BUTTON')}</button>
                <button type="button" href="#" data-dismiss="modal" class="btn btn-primary pull-right" onclick="{$this->buildJsRefresh(false)}"><i class="{$this->buildCssIconClass(Icons::SEARCH)}"></i> {$this->getWorkbench()->getCoreApp()->getTranslator()->translate('ACTION.READDATA.SEARCH')}</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

HTML;
        return $output;
    }

    protected function buildJsTableCustomizerOnShownFunction()
    {
        // Der 1. Tab ist der aktive wenn der Konfigurator angezeigt wird. Von diesem wird
        // beim Anzeigen des Dialogs der Layouter gestartet.
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}chartCustomizerOnShown() {
        {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget()->getChildren()[0])->buildJsLayouter()}
    }
JS;
        
        return $output;
    }

    protected function buildHtmlHeader()
    {
        $table_caption = $this->getWidget()->getCaption() ? $this->getWidget()->getCaption() : $this->getMetaObject()->getName();
        
        $output = <<<HTML

        <h3 class="box-title">$table_caption</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#{$this->getId()}_popup_config"><i class="fa fa-filter"></i></button>
            <button type="button" class="btn btn-box-tool" onclick="{$this->buildJsRefresh()} return false;"><i class="fa fa-refresh"></i></button>
        </div>

HTML;
        return $output;
    }
}
?>