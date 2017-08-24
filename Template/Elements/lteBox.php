<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteBox extends lteForm
{

    public function generateHtml()
    {
        $output = <<<HTML
<div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
	{$this->buildHtmlBox()}
</div>
HTML;
        return $output;
    }

    protected function buildHtmlBox()
    {
        // Does the box need a header?
        $header = '';
        if ($this->getWidget()->getCaption()) {
            $header .= '<h3 class="box-title">' . $this->getWidget()->getCaption() . '</h3>';
        }
        if ($header) {
            $header = '<div class="box-header">' . $header . '</div>';
        }
        
        // Does the box need a footer (for buttons)?
        if ($buttons_html = $this->buildHtmlToolbars()) {
            $footer = '	<div class="box-footer clearfix">' . $buttons_html . '</div>';
        }
        
        $output = <<<HTML
<div class="box">
	{$header}
	<div class="box-body">
		<div class="row" id="{$this->getId()}">
			{$this->buildHtmlForWidgets()}
			<div class="{$this->getColumnWidthClasses()} {$this->buildCssLayoutItemClass()}" id="{$this->getId()}_sizer" style=""></div>
		</div>
	</div>
	{$footer}
</div>
HTML;
        return $output;
    }
}
?>