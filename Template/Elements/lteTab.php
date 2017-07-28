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
        $output = '
	<div id="' . $this->getId() . '" class="nav-tabs-custom">
		<ul class="nav nav-tabs">
			' . $this->buildHtmlHeader() . '
		</ul>
		<div class="tab-content">
			' . $this->buildHtmlBody() . '
		</div>
	</div>';
        
        return $output;
    }

    function buildHtmlHeader()
    {
        $widget = $this->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getParent()->getChildren()[0] ? ' active' : '';
        $disabled_class = $widget->isDisabled() ? ' disabled' : '';
        $icon = $widget->getIconName() ? '<i class="' . $this->buildCssIconClass($widget->getIconName()) . '"></i> ' : '';
        
        $output = '
           <li class="' . $active_class . $disabled_class . '">
                <a href="#' . $this->getId() . '" data-toggle="tab" class="' . $disabled_class . '">' . $icon . $this->getWidget()->getCaption() . '</a>
           </li>';
        return $output;
    }

    function buildHtmlBody()
    {
        $widget = $this->getWidget();
        // der erste Tab ist aktiv
        $active_class = $widget === $widget->getParent()->getChildren()[0] ? ' active' : '';
        
        $output = '<div class="tab-pane' . $active_class . '" id="' . $this->getId() . '">
		<div class="tab-pane-content-wrapper row">
			' . $this->buildHtmlForChildren() . '
		</div>
	</div>';
        return $output;
    }
}
?>
