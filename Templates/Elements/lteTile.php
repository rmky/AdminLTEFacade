<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\Tile;
use exface\Core\Widgets\Container;

/**
 * Tile-widget for AdminLte-Template.
 * 
 * @author SFL
 *
 */
class lteTile extends lteButton
{
    function buildHtml()
    {
        $widget = $this->getWidget();
        
        $icon_class = $widget->getIcon() && ! $widget->getHideButtonIcon() ? $this->buildCssIconClass($widget->getIcon()) : '';
        
        return <<<JS
                <div class="{$this->getMasonryItemClass()} {$this->getWidthClasses()}"</div>
                    <div id="{$this->getId()}" class="small-box exf-tile {$this->getColorClass($widget)}" style="{$this->buildCssElementStyle()}">
                        <div class="inner">
                            <h3>{$widget->getTitle()}</h3>
           					<p>{$widget->getSubtitle()}</p>
                		</div>
                		<div class="icon">
                			<i class="{$icon_class}"></i>
                		</div>
                		<a href="javascript:void(0)" onclick="{$this->buildJsClickFunctionName()}();" class="small-box-footer">Start <i class="fa fa-arrow-circle-right"></i></a>
        			</div>
                </div>
JS;
    }
       
    /**
     * 
     * @param Tile $widget
     * @return string
     */
    protected function getColorClass(Tile $widget) : string
    {
        if ($widget->getColor() !== null) {
            return '';
        }
        
        $container = $widget->getParent();
        if ($container instanceof Container) {
            $idx = $container->getWidgetIndex($widget);
        } else {
            $idx = 0;
        }
        
        return $this->getTemplate()->getConfig()->getOption('WIDGET.TILE.AUTOCOLORS')->getProperty($idx);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssElementStyle()
     */
    public function buildCssElementStyle()
    {
        $style = '';
        $bgColor = $this->getWidget()->getColor();
        if ($bgColor !== null && $bgColor !== '') {
            $style .= 'background-color:' . $bgColor;
        }
        return $style;
    }
}
