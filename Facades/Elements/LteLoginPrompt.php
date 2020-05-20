<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\LoginPrompt;
use exface\Core\Widgets\Dialog;

/**
 * 
 * @method LoginPrompt getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class LteLoginPrompt extends LteContainer
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteTabs::buildHtml()
     */
    function buildHtml()
    {
        $html = <<<HTML
        
    <div id="{$this->getId()}" class="nav-tabs-custom exf-loginprompt">
        <ul class="nav nav-tabs">
            {$this->buildHtmlTabHeaders()}
        </ul>
        <div class="tab-content">
            {$this->buildHtmlTabBodies()}
        </div>
    </div>
    
HTML;
            
            return $html;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteTabs::buildHtmlTabBodies()
     */
    protected function buildHtmlTabBodies()
    {
        $output = '';
        foreach ($this->getWidget()->getWidgets() as $tab) {
            $tabEl = $this->getFacade()->getElement($tab);
            $output .= $this->buildHtmlTabBody($tabEl);
        }
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteTabs::buildHtmlTabHeaders()
     */
    protected function buildHtmlTabHeaders()
    {
        $output = '';
        foreach ($this->getWidget()->getWidgets() as $tab) {
            $tabEl = $this->getFacade()->getElement($tab);
            $output .= $this->buildHtmlTabHeader($tabEl);
        }
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteTabs::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return 1;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteTabs::inheritsNumberOfColumns()
     */
    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }
    
    function buildHtmlTab(LteForm $loginForm)
    {
        $output = <<<HTML
        
    <div id="{$this->getId()}" class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            {$this->buildHtmlTabHeader($loginForm)}
        </ul>
        <div class="tab-content">
            {$this->buildHtmlTabBody($loginForm)}
        </div>
    </div>
HTML;
            
            return $output;
    }
    
    function buildHtmlTabHeader(LteForm $loginForm)
    {
        $widget = $loginForm->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getWidget(0) ? 'active' : '';
        $disabled_class = $widget->isDisabled() ? 'disabled' : '';
        $icon = $widget->getIcon() ? '<i class="' . $loginForm->buildCssIconClass($widget->getIcon()) . '"></i>' : '';
        
        $output = <<<HTML
        
            <li class="{$active_class} {$disabled_class}">
                <a href="#{$loginForm->getId()}" data-toggle="tab" class="{$disabled_class}">{$icon} {$loginForm->getWidget()->getCaption()}</a>
            </li>
HTML;
        return $output;
    }
    
    function buildHtmlTabBody(LteForm $loginForm)
    {
        $widget = $loginForm->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getParent()->getWidgetFirst() ? 'active' : '';
        
        $output = <<<HTML
        
    <div class="tab-pane {$active_class}" id="{$loginForm->getId()}">
        <div class="tab-pane-content-wrapper row">
            <div class="grid" id="{$loginForm->getId()}_masonry_grid" style="width:100%;height:100%;">
                {$loginForm->buildHtmlForChildren()}
                <div class="{$loginForm->getColumnWidthClasses()}" id="{$loginForm->getId()}_sizer"></div>
            </div>
        </div>
    </div>
HTML;
                
                return $output;
    }
}