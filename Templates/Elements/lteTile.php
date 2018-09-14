<?php
namespace exface\AdminLteTemplate\Templates\Elements;

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
                    <div id="{$this->getId()}" class="small-box bg-aqua">
                        <div class="inner">
                            <h3 style="overflow:hidden;">{$widget->getTitle()}</h3>
           					<p style="overflow:hidden;">{$widget->getSubtitle()}</p>
                		</div>
                		<div class="icon">
                			<i class="{$icon_class}"></i>
                		</div>
                		<a href="javascript:void(0)" onclick="{$this->buildJsClickFunctionName()}();" class="small-box-footer">Start <i class="fa fa-arrow-circle-right"></i></a>
        			</div>
                </div>
JS;
    }
}
