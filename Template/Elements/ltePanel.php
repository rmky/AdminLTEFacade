<?php
namespace exface\AdminLteTemplate\Template\Elements;

class ltePanel extends lteContainer
{

    function generateHtml()
    {
        return '
				<div id="' . $this->getId() . '" class="' . $this->getWidthClasses() . ' exf_grid masonry">
					' . $this->buildHtmlForChildren() . '
					<div class="col-xs-1" id="' . $this->getId() . '_sizer" style=""></div>
				</div>';
    }

    function generateJs()
    {
        $output = "
				$('#" . $this->getId() . "').masonry({columnWidth: '#" . $this->getId() . "_sizer', itemSelector: '#" . $this->getId() . " > .exf_grid_item'});
				$('#" . $this->getId() . "').children('.exf_grid_item').on('resize', function(event){ $('#" . $this->getId() . "').masonry('layout'); });
				";
        
        return $output . $this->buildJsForChildren();
    }
}
?>