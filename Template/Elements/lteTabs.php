<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteTabs extends lteContainer
{

    function generateHtml()
    {
        $header_html = '';
        $content_html = '';
        foreach ($this->getWidget()->getChildren() as $tab) {
            $header_html .= $this->getTemplate()->getElement($tab)->generateHtmlHeader();
            ;
            $content_html .= $this->getTemplate()->getElement($tab)->generateHtmlContent();
        }
        
        $output = '
	<div id="' . $this->getId() . '" class="nav-tabs-custom">
		<ul class="nav nav-tabs">
			' . $header_html . '
		</ul>
		<div class="tab-content">
			' . $content_html . '
		</div>
	</div>';
        
        return $output;
    }
}
?>
