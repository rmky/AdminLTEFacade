<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\Core\Widgets\Data;

class LteDataSpreadSheet extends LteDataTable
{    
    use JExcelTrait;
    
    protected function init()
    {
        $this->registerReferencesAtLinkedElements();
        $this->addOnLoadSuccess($this->buildJsFooterRefresh('data', 'jqSelf'));
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildHtml()
     */
    public function buildHtmlTable($css_class = '')
    {
        return $this->buildHtmlJExcel();
    }
    
    
    
    public function buildJs()
    {
        $widget = $this->getWidget();
        
        return <<<JS
        
var {$this->getId()}_table;
if ($.fn.dataTable != undefined){
    $.fn.dataTable.ext.errMode = 'throw';
}

{$this->getFacade()->getElement($widget->getConfiguratorWidget())->buildJs()}

{$this->buildJsFunctionPrefix()}Init();

function {$this->buildJsFunctionPrefix()}Init(){

    if ({$this->getId()}_table && $.fn.DataTable.isDataTable( '#{$this->getId()}' )) {
        {$this->getId()}_table.columns.adjust();
        return;
    }
    
    setTimeout(function() {
        {$this->buildJsJExcelInit()}
        {$this->buildJsRefresh()}
    }, 0);
    
    {$this->buildJsClickListeners()}
    
    {$this->buildJsPagination()}
    
    {$this->buildJsQuicksearch()}
    
    // Starten des Layouters wenn der Konfigurator angezeigt wird.
    {$this->buildJsCustomizerOnShownFunction()}
    $("#{$this->getId()}_popup_config").on("shown.bs.modal", function() {
        {$this->buildJsCustomizerOnShown()};
    });
}

function {$this->getId()}_drawPagination(){
    var pages = {$this->getId()}_table.page.info();
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

{$this->buildJsButtons()}
    
{$this->buildJsDataLoadFunction()}

{$this->buildJsFunctionsForJExcel()}

JS;
    }

    protected function getDataWidget() : Data
    {
        return $this->getWidget();
    }
    
    protected function buildJsDataLoadFunction() : string
    {
        return <<<JS
        
function {$this->buildJsDataLoadFunctionName()}() {
    {$this->buildJsDataLoadFunctionBody()}
}


JS;
    }
    
    protected function buildJsDataLoadFunctionName() : string
    {
        return $this->buildJsFunctionPrefix() . 'LoadData';
    }
    
    /**
     * Returns the JS code to fetch data: either via AJAX or from a Data widget (if the chart is bound to another data widget).
     *
     * @return string
     */
    protected function buildJsDataLoadFunctionBody() : string
    {
        $widget = $this->getWidget();
        $dataWidget = $this->getDataWidget();
        
        $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
        
        $url_params = '';
        
        // send sort information
        if (count($dataWidget->getSorters()) > 0) {
            foreach ($dataWidget->getSorters() as $sorter) {
                $sort .= ',' . urlencode($sorter->getProperty('attribute_alias'));
                $order .= ',' . urldecode($sorter->getProperty('direction'));
            }
            $url_params .= '
                        sort: "' . substr($sort, 1) . '",
                        order: "' . substr($order, 1) . '",';
        }
        
        // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
        if ($dataWidget->isPaged()) {
            $url_params .= '
                        page: 1,
                        rows: ' . $dataWidget->getPaginator()->getPageSize($this->getFacade()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ',';
        }
        
        // Loader function
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        $output .= <<<JS
					{$this->buildJsBusyIconShow()}
					
                    try {
                        if (! {$configurator_element->buildJsValidator()}) {
                            {$this->buildJsDataResetter()}
                            /* TODO { $ this->buildJsMessageOverlayShow( $ dataWidget->getAutoloadDisabledHint())} */
                            {$this->buildJsBusyIconHide()}
                            return false;
                        }
                    } catch (e) {
                        console.warn('Could not check filter validity - ', e);
                    }
                    
					$.ajax({
						url: "{$this->getAjaxUrl()}",
                        method: "POST",
                        {$headers}
                        data: {
                            resource: "{$dataWidget->getPage()->getAliasWithNamespace()}",
                            element: "{$dataWidget->getId()}",
                            object: "{$dataWidget->getMetaObject()->getId()}",
                            action: "{$dataWidget->getLazyLoadingActionAlias()}",
                            {$url_params}
                            data: {$configurator_element->buildJsDataGetter()}
                            
                        },
						success: function(data){
                            var jqSelf = $('#{$this->getId()}');
							{$this->buildJsDataLoaderOnLoaded('data')}
                            {$this->getOnLoadSuccess()}
							{$this->buildJsBusyIconHide()}
						},
						error: function(jqXHR, textStatus, errorThrown){
							{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
							{$this->buildJsBusyIconHide()}
						}
					});
JS;
							
							return $output;
    }
    
    /**
     * 
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $dataJs): string
    {
        return $this->buildJsDataSetter($dataJs);
    }
    
    public function buildHtmlHeadTags()
    {
        $includes = $this->buildHtmlHeadTagsForJExcel();
        $includes[] = '<script type="text/javascript">' . $this->buildJsFixJqueryImportUseStrict() . '</script>';
        
        // Resize-Sensor
        $includes[] = '<script type="text/javascript" src="exface/vendor/npm-asset/css-element-queries/src/ResizeSensor.js"></script>';
        return $includes;
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-spreadsheet';
    }
    
    /**
     * Function to refresh the chart
     *
     * @return string
     */
    public function buildJsRefresh() : string
    {
        return $this->buildJsDataLoadFunctionName() . '();';
    }
}