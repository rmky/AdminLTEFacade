<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\Tabs;

/**
 * 
 * @method Tabs getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteTabs extends lteContainer
{

    function buildHtml()
    {
        $html = <<<HTML

    <div id="{$this->getId()}" class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            {$this->buildHtmlTabHeaders()}
        </ul>
        <div class="tab-content">
            {$this->buildHtmlTabBodies()}
        </div>
    </div>

HTML;
        if (! $this->getWidget()->getParent() instanceof Dialog) {
            $html = '<div class="col-xs-12">' . $html . '</div>';
        }
        
        return $html;
    }

    protected function buildHtmlTabBodies()
    {
        $output = '';
        foreach ($this->getWidget()->getTabs() as $tab) {
            $output .= $this->getTemplate()->getElement($tab)->buildHtmlBody();
        }
        return $output;
    }

    protected function buildHtmlTabHeaders()
    {
        $output = '';
        foreach ($this->getWidget()->getTabs() as $tab) {
            $output .= $this->getTemplate()->getElement($tab)->buildHtmlHeader();
        }
        return $output;
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.TABS.COLUMNS_BY_DEFAULT");
    }

    /**
     * Determines if the tabs in this widget inherit their column number from a parent
     * layout widget.
     * 
     * @return boolean
     */
    public function inheritsNumberOfColumns() : bool
    {
        return true;
    }
}
?>
