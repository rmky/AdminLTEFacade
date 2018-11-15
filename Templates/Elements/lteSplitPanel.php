<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\SplitHorizontal;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;

class lteSplitPanel extends ltePanel
{

    public function buildHtml(){
        $widget = $this->getWidget();
        $childrenCount = $widget->countWidgetsVisible();
        
        if ($childrenCount === 1 && ($widget->getWidgetFirst() instanceof iFillEntireContainer) && ! $widget->getHeight()->isUndefined()) {
            $widget->getChildren()[0]->setHeight($widget->getHeight());
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
    
    public function getWidthClasses()
    {
        return '';
    }

    public function getNumberOfColumns()
    {
        if (! $this->searched_for_number_of_columns) {
            $widget = $this->getWidget();
            
            if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget instanceof SplitHorizontal)) {
                if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
                    $columnNumber = $this->getTemplate()->getElement($layoutWidget)->getNumberOfColumns();
                } else {
                    $columnNumber = $this->getTemplate()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
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
