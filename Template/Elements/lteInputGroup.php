<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteInputGroup extends ltePanel
{

    public function generateHtml()
    {
        $children_html = $this->buildHtmlForChildren();
        
        $output = '
				<fieldset class="exface_inputgroup">
					<legend>' . $this->getWidget()->getCaption() . '</legend>
					' . $children_html . '
				</fieldset>';
        return $output;
    }

    public function generateJs()
    {
        return $this->buildJsForChildren();
    }
}
?>
