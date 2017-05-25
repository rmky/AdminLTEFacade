<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteTab extends ltePanel
{

    function generateHtml()
    {
        $output = '
	<div id="' . $this->getId() . '" class="nav-tabs-custom">
		<ul class="nav nav-tabs">
			' . $this->generateHtmlHeader() . '
		</ul>
		<div class="tab-content">
			' . $this->generateHtmlContent() . '
		</div>
	</div>';
        
        return $output;
    }

    function generateHtmlHeader()
    {
        // der erste Tab ist aktiv
        $active_class = $this->getWidget() === $this->getWidget()->getParent()->getChildren()[0] ? ' active' : '';
        
        $output = '
	<li class="' . $active_class . '"><a href="#' . $this->getId() . '" data-toggle="tab">' . $this->getWidget()->getCaption() . '</a></li>';
        return $output;
    }

    function generateHtmlContent()
    {
        // der erste Tab ist aktiv
        $active_class = $this->getWidget() === $this->getWidget()->getParent()->getChildren()[0] ? ' active' : '';
        
        $output = '<div class="tab-pane' . $active_class . '" id="' . $this->getId() . '">
		<div class="tab-pane-content-wrapper row">
			' . $this->buildHtmlForChildren() . '
		</div>
	</div>';
        return $output;
    }
}
?>
