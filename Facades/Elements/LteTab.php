<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\Tab;

/**
 * @method Tab getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class LteTab extends ltePanel
{

    function buildHtml()
    {
        $output = <<<HTML

    <div id="{$this->getId()}" class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            {$this->buildHtmlHeader()}
        </ul>
        <div class="tab-content">
            {$this->buildHtmlBody()}
        </div>
    </div>
HTML;
        
        return $output;
    }

    function buildHtmlHeader()
    {
        $widget = $this->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getParent()->getTab(0) ? 'active' : '';
        $disabled_class = $widget->isDisabled() ? 'disabled' : '';
        $icon = $widget->getIcon() ? '<i class="' . $this->buildCssIconClass($widget->getIcon()) . '"></i>' : '';
        
        $output = <<<HTML

            <li class="{$active_class} {$disabled_class}">
                <a href="#{$this->getId()}" data-toggle="tab" class="{$disabled_class}">{$icon} {$this->getWidget()->getCaption()}</a>
            </li>
HTML;
        return $output;
    }

    function buildHtmlBody()
    {
        $widget = $this->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getParent()->getTab(0) ? 'active' : '';
        
        $output = <<<HTML

    <div class="tab-pane {$active_class}" id="{$this->getId()}">
        <div class="tab-pane-content-wrapper row">
            <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                {$this->buildHtmlForChildren()}
                <div class="{$this->getColumnWidthClasses()} {$this->buildCssLayoutItemClass()}" id="{$this->getId()}_sizer"></div>
            </div>
        </div>
    </div>
HTML;
        
        return $output;
    }

    public function buildJs()
    {
        $output = parent::buildJs();
        
        $output .= <<<JS

    $("a[href='#{$this->getId()}']").on("shown.bs.tab", function(e) {
        {$this->buildJsLayouter()};
    });
JS;
        
        return $output;
    }

    /**
     * The default column number for tabs is defined for the tabs widget or its derivatives.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        $parent_element = $this->getFacade()->getElement($this->getWidget()->getParent());
        if (method_exists($parent_element, 'getNumberOfColumnsByDefault')) {
            return $parent_element->getNumberOfColumnsByDefault();
        }
        return parent::getNumberOfColumnsByDefault();
    }

    /**
     * If the tab inherits the column number from a parent layout widget is defined for
     * the tabs widget or its derivatives.
     * 
     * @return boolean
     */
    public function inheritsNumberOfColumns() : bool
    {
        $parent_element = $this->getFacade()->getElement($this->getWidget()->getParent());
        if (method_exists($parent_element, 'inheritsNumberOfColumns')) {
            return $parent_element->inheritsNumberOfColumns();
        }
        return parent::inheritsNumberOfColumns();
    }
}
?>
