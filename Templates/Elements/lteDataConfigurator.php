<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataConfiguratorTrait;
use exface\Core\Widgets\DataConfigurator;

/**
 * 
 * @method DataConfigurator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteDataConfigurator extends lteTabs
{
    use JqueryDataConfiguratorTrait;

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DATACONFIGURATOR.COLUMNS_BY_DEFAULT");
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteTabs::inheritsNumberOfColumns()
     */
    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteTabs::buildHtmlTabHeaders()
     */
    protected function buildHtmlTabHeaders()
    {
        $output = parent::buildHtmlTabHeaders();
        
        if (! $this->needTableTabs()) {
            return $output;
        }
        
        $output .= <<<HTML

                            <li role="presentation">
                                <a href="#{$this->getId()}_popup_columns" aria-controls="{$this->getId()}_popup_columns" role="tab" data-toggle="tab"><i class="fa fa-table" aria-hidden="true"></i> {$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.COLUMNS')}</a>
                            </li>

HTML;
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteTabs::buildHtmlTabBodies()
     */
    protected function buildHtmlTabBodies()
    {
        $output = parent::buildHtmlTabBodies();
        
        if (! $this->needTableTabs()) {
            return $output;
        }
        
        $columns_html = '';
        $widget = $this->getWidget();
        
        foreach ($widget->getWidgetConfigured()->getColumns() as $col) {
            if ($col->isHidden() && ! $this->getWorkbench()->getContext()->getScopeUser()->getUserCurrent()->isUserAdmin()) {
                continue;
            }
            $columns_html .= '
                                <li>
                                    <i class="fa fa-arrows-v pull-right handle"></i>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="' . $col->getDataColumnName() . '"' . ($col->isHidden() || $col->getVisibility() === EXF_WIDGET_VISIBILITY_OPTIONAL ? '' : ' checked="true"') . '>
                                            ' . $col->getCaption() . '
                                        </label>
                                    </div>
                                </li>';
        }
        $columns_html = '
                                <ol id="' . $this->getId() . '_popup_columnList" class="sortable">
                                    ' . $columns_html . '
                                </ol>';
        
        $output .= <<<HTML
                             <div role="tabpanel" class="tab-pane" id="{$this->getId()}_popup_columns">{$columns_html}</div>
HTML;
        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $output = parent::buildJs();
        $output .= <<<JS
    
    // $('#{$this->getId()}_popup_columnList').sortable();
	
JS;
        return $output;
    }

    /**
     * Returns true, if the configured widget supports adding/removing columns
     * 
     * @return boolean
     */
    protected function needTableTabs()
    {
        // Currently only the DataTable supports the column feature.
        if ($this->getWidget()->getWidgetConfigured()->getWidgetType() == 'DataTable') {
            return true;
        }
        return false;
    }
}
?>
