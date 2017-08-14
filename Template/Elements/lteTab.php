<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Tab;

/**
 * @method Tab getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteTab extends ltePanel
{

    function generateHtml()
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
        $active_class = $widget === $widget->getParent()->getChildren()[0] ? 'active' : '';
        $disabled_class = $widget->isDisabled() ? 'disabled' : '';
        $icon = $widget->getIconName() ? '<i class="' . $this->buildCssIconClass($widget->getIconName()) . '"></i>' : '';
        
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
        $active_class = $widget === $widget->getParent()->getChildren()[0] ? 'active' : '';
        
        $output = <<<HTML

    <div class="tab-pane {$active_class}" id="{$this->getId()}">
        <div class="tab-pane-content-wrapper row">
            <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                {$this->buildHtmlForChildren()}
                <div class="{$this->getColumnWidthClasses()} {$this->getId()}_masonry_fitem" id="{$this->getId()}_sizer"></div>
            </div>
        </div>
    </div>
HTML;
        
        return $output;
    }

    public function generateJs()
    {
        $output = parent::generateJs();
        
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
    public function getDefaultColumnNumber()
    {
        $parent_element = $this->getTemplate()->getElement($this->getWidget()->getParent());
        if (method_exists($parent_element, 'getDefaultColumnNumber')) {
            return $parent_element->getDefaultColumnNumber();
        }
        return parent::getDefaultColumnNumber();
    }

    /**
     * If the tab inherits the column number from a parent layout widget is defined for
     * the tabs widget or its derivatives.
     * 
     * @return boolean
     */
    public function inheritsColumnNumber()
    {
        $parent_element = $this->getTemplate()->getElement($this->getWidget()->getParent());
        if (method_exists($parent_element, 'inheritsColumnNumber')) {
            return $parent_element->inheritsColumnNumber();
        }
        return parent::inheritsColumnNumber();
    }
}
?>
