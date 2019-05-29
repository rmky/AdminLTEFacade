<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\Core\CommonLogic\Constants\Icons;
use  exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataTableTrait;
use exface\Core\DataTypes\BooleanDataType;

class LteChart extends lteDataTable
{
    use JqueryToolbarsTrait;
    
    use EChartsTrait;

    public function init()
    {
        parent::init();
        // Connect to an external data widget if a data link is specified for this chart
        // TODO $this->registerLiveReferenceAtLinkedElement();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return $this->buildCssCanvasHeightValue(($this->getHeightRelativeUnit() * 5) . 'px'); 
    }

    public function buildHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        
        // Create the toolbar if the chart has it's own controls and is not bound to another data widget
        if ($this->hasBoxTitle()) {
            $header = $this->buildHtmlHeader();
        }
        
        $style = '';
        if (! $widget->getHeight()->isUndefined()){
            $style .= 'height:' . $this->getHeight() . ';';
        } 
        
        if ($widget->getHeight()->isPercentual() || $widget->getHeight()->isUndefined()){
            $style .= 'min-height: ' . $this->buildCssHeightDefaultValue() . ';';
        }
        
        if (! $this->getWidthClasses()) {
            $wrapper_style .= 'width: 100%';
        }
        
        // Create the panel for the chart
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} {$this->getWidthClasses()}" style="{$wrapper_style}">
    <div class="box">
        <div class="box-header">
            {$header}
        </div><!-- /.box-header -->
        <div id="{$this->getId()}_box" class="box-body">
            {$this->buildHtmlChart($style)}
        </div>
    </div>
    {$this->buildHtmlChartCustomizer()}
</div>

HTML;
        
        return $output;
    }

    function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        $output = '';
        
        // Add JS code for the configurator
        $output .= $this->getFacade()->getElement($widget->getConfiguratorWidget())->buildJs();
        // Add JS for all buttons
        $output .= $this->buildJsButtons();
        
        // Starten des Layouters wenn der Konfigurator angezeigt wird.
        $output .= <<<JS

    {$this->buildJsCustomizerOnShownFunction()}
    $("#{$this->getId()}_popup_config").on("shown.bs.modal", function() {
        {$this->buildJsCustomizerOnShown()};
    });

    new ResizeSensor(document.getElementById("{$this->getId()}_box"), function() {
        echarts.getInstanceByDom(document.getElementById('{$this->getId()}')).resize();
    });

JS;
   
        
        $output .= $this->buildJsEChartsInit('light');
        $output .= $this->buildJsFunctions();
        $output .= $this->buildJsOnClickHandlers();
        $output .= $this->buildJsRefresh();
        
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
    
    protected function buildJsDataLoadFunctionBody() : string
    {
        return $this->buildJsDataLoader();
    }
    /**
     * Returns the JS code to fetch data: either via AJAX or from a Data widget (if the chart is bound to another data widget).
     *
     * @return string
     */
    protected function buildJsDataLoader()
    {
        $widget = $this->getWidget();
        $output = '';
        if (! $widget->getDataWidgetLink()) {
            
            $post_data = '
                            data.resource = "' . $widget->getPage()->getAliasWithNamespace() . '";
                            data.element = "' . $widget->getData()->getId() . '";
                            data.object = "' . $widget->getMetaObject()->getId() . '";
                            data.action = "' . $widget->getLazyLoadingActionAlias() . '";
            ';
            
            // send sort information
            if (count($widget->getData()->getSorters()) > 0) {
                $post_data .= 'data.order = [];' . "\n";
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $post_data .= 'data.order.push({attribute_alias: "' . $sorter->getProperty('attribute_alias') . '", dir: "' . $sorter->getProperty('direction') . '"});';
                }
            }
            
            // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
            if ($widget->getData()->isPaged()) {
                $post_data .= 'data.start = 0;';
                $post_data .= 'data.length = ' . $widget->getData()->getPaginator()->getPageSize($this->getFacade()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ';';
            }
            
            // Loader function
            $output .= '
					' . $this->buildJsBusyIconShow() . '
					var data = { };
					' . $post_data . '
                    data.data = ' . $this->getFacade()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . '
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
						data: data,
						success: function(data){
							' . $this->buildJsRedraw('data.data') . ';
							' . $this->buildJsBusyIconHide() . ';
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				';
        }
        
        return $output;
    }
    
    protected function buildJsDataRowsSelector()
    {
        return '.data';
    }

    protected function buildHtmlChartCustomizer()
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
                {$this->getFacade()->getElement($this->getWidget()->getConfiguratorWidget())->buildHtml()}
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

    public function buildHtmlHeader()
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
    
    protected function hasFooter()
    {
        // TODO
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteDataTable::getHeight()
     */
    public function getHeight($calculate = true)
    {
        $height = parent::getHeight(false);
        if (! $calculate) {
            return $height;
        }
        
        return $this->buildCssCanvasHeightValue($height);
    }
    
    protected function buildCssCanvasHeightValue($cssValue)
    {
        $height = $cssValue;
        if (strtolower(substr($height, 0, 5)) === 'calc(') {
            $height = trim(substr($height, 4), "()");
        }
        
        $widget = $this->getWidget();
        $calc = [];
        $calc[] = '+ 20px'; // box padding at the bottom
        $calc[] = '- 10px'; // padding of the chart canvas
        if ($this->hasFooter()){
            $calc[] = '- 55px';
        } else {
            $calc[] = '- 40px';
        }
        if ($this->hasBoxTitle()){
            $calc[] = '- 54px'; // If 
        }
        if (! empty($calc)) {
            $height = 'calc(' . $height . ' ' . implode(' ', $calc) . ')';
        }
        return $height;
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see JqueryDataTableTrait::isEditable()
     */
    public function isEditable()
    {
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteDataTable::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = $this->buildHtmlHeadDefaultIncludes();
        // Resize-Sensor
        $includes[] = '<script src="exface/vendor/npm-asset/css-element-queries/src/ResizeSensor.js"></script>';
        $includes[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
        $includes[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
        
        return $includes;
    }
    
    protected function hasBoxTitle() : bool
    {
        return $this->getWidget()->getDataWidgetLink() === null;
    }
}
?>