<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\ImageCarousel;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryJssorTrait;

/**
 * Creates an AdminLTE box with a Jssor image slider for a ImageCarousel widget.
 * 
 * @author PATRIOT
 * 
 * @method ImageCarousel getWidget()
 *        
 */
class lteImageCarousel extends lteDataCards
{
    use JqueryJssorTrait;

    function buildHtml()
    {
        $top_toolbar = $this->buildHtmlHeader();
        
        // output the html code
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} {$this->getWidthClasses()}">
	<div class="box" >
		<div class="box-header">
			{$top_toolbar}
		</div><!-- /.box-header -->
		<div class="box-body">
			
{$this->buildHtmlSlider()}

		</div>
	</div>
	{$this->buildHtmlTableCustomizer()}
</div>
					
{$this->buildHtmlImageTemplate()}
	
HTML;
        
        return $output;
    }
    
    /**
     *
     * @return string
     */
    protected function buildCssSliderHeight() : string
    {
        return "height: calc({$this->getHeight()} - 43px);";
    }

    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 12) . 'px';
    }

    public function buildJs()
    {
        return <<<JS

$(document).ready(function() {
	
	{$this->buildJsFunctionPrefix()}load();
	
	{$this->buildJsPagination()}
	
	{$this->buildJsQuicksearch()}
	
	{$this->buildJsRowSelection()}
	
});

{$this->buildJsSliderInit()}

JS;
    }
}