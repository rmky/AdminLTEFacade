<?php
namespace exface\AdminLteTemplate\Templates;

use exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate;
use exface\Core\Templates\AbstractAjaxTemplate\Middleware\JqueryDataTablesUrlParamsReader;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\WidgetInterface;

class AdminLteTemplate extends AbstractAjaxTemplate
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::init()
     */
    public function init()
    {
        parent::init();
        $this->setClassPrefix('lte');
        $this->setClassNamespace(__NAMESPACE__);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        $middleware[] = new JqueryDataTablesUrlParamsReader($this, 'getInputData', 'setInputData');
        return $middleware;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Templates\HttpTemplateInterface::getUrlRoutePatterns()
     */
    public function getUrlRoutePatterns() : array
    {
        return [
            "/[\?&]tpl=adminlte/",
            "/\/api\/adminlte[\/?]/"
        ];
    }
    
    public function buildResponseData(DataSheetInterface $data_sheet, WidgetInterface $widget = null)
    {
        $data = array();
        $data['data'] = $data_sheet->getRows();
        $data['recordsFiltered'] = $data_sheet->countRowsInDataSource();
        $data['recordsTotal'] = $data_sheet->countRowsInDataSource();
        $data['footer'] = $data_sheet->getTotalsRows();
        return $data;
    }
}