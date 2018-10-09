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
    const COLORS = [
        'bg-aqua',
        'bg-navy',
        'bg-light-blue',
        'bg-teal',
        'bg-purple',
        'bg-orange',
        'bg-maroon',
        'bg-black',
        'bg-gray',
        'bg-green',
        'bg-yellow',
        'bg-red'
    ];
    
    
    function buildHtml()
    {
        $widget = $this->getWidget();
        
        $icon_class = $widget->getIcon() && ! $widget->getHideButtonIcon() ? $this->buildCssIconClass($widget->getIcon()) : '';
        
        return <<<JS
                <div class="{$this->getMasonryItemClass()} {$this->getWidthClasses()}"</div>
                    <div id="{$this->getId()}" class="small-box exf-tile {$this->getColorClass($widget)}">
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
        
    protected function getColorClass(Tile $widget) : string
    {
        $container = $widget->getParent();
        if ($container instanceof Container) {
            $idx = $container->getWidgetIndex($widget);
        } else {
            $idx = 0;
        }
        
        return static::COLORS[$idx];
    }
}
