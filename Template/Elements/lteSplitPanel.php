<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\SplitHorizontal;

class lteSplitPanel extends ltePanel
{

    public function generateHtml(){
        return <<<HTML

<div id="{$this->getId()}">
    {$this->buildHtmlChildrenWrapperPlain($this->buildHtmlForChildren())}
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
