<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\SplitHorizontal;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Widgets\SplitPanel;

/**
 * 
 * 
 * @method SplitPanel getWidget()
 * @author Andrej Kabachnik
 *
 */
class LteSplitPanel extends ltePanel
{
    private $number_of_columns = null;
    
    private $searched_for_number_of_columns = false;
    
    public function buildHtml(){
        $widget = $this->getWidget();
        $childrenCount = $widget->countWidgetsVisible();
        
        if ($childrenCount === 1 && ($widget->getWidgetFirst() instanceof iFillEntireContainer) && ! $widget->getHeight()->isUndefined()) {
            $widget->getWidgetFirst()->setHeight($widget->getHeight());
        }
        
        $content = $this->buildHtmlForChildren();
        
        if ($childrenCount > 1) {
            $content = $this->buildHtmlChildrenWrapperGrid($content);
        }
        
        return <<<HTML

<div id="{$this->getId()}">
    {$content}
</div>

HTML;
    }
    
    public function buildCssWidthClasses()
    {
        return '';
    }

    public function getNumberOfColumns() : int
    {
        if (! $this->searched_for_number_of_columns) {
            $widget = $this->getWidget();
            
            if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget instanceof SplitHorizontal)) {
                if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
                    $columnNumber = $this->getFacade()->getElement($layoutWidget)->getNumberOfColumns();
                } else {
                    $columnNumber = $this->getFacade()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
                }
                $panelNumber = count($containerWidget->getPanels());
                
                $col_no = floor($panelNumber / $columnNumber);
                if ($col_no < 1) {
                    $col_no = 1;
                }
                $this->number_of_columns = $col_no;
            } else {
                $this->number_of_columns = parent::getNumberOfColumns();
            }
            $this->searched_for_number_of_columns = true;
        }
        return $this->number_of_columns;
    }
}
?>
