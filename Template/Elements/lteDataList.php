<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

/**
 *
 * @author PATRIOT
 *        
 */
class lteDataList extends lteDataTable
{

    function init()
    {
        parent::init();
        // Make sure, the DataTable has a UID column. This method will create the column if it does not exist yet.
        // It is important to call the method within init(), because at this point, the processing of the UXON is definitely
        // finished while the creation of the template element has not started yet!
        // FIXME Move this
        $this->getWidget()->getUidColumn();
    }
    
    /**
     * 
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(true);
    }

    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\DataCards */
        $widget = $this->getWidget();
        
        // Contents
        $list_items = '';
        
        if (! $this->isLazyLoading()) {
            if ($widget->getValuesDataSheet()) {
                $data = $widget->getValuesDataSheet();
            }
            
            $data = $widget->prepareDataSheetToRead($data ? $data : null);
            
            if (! $data->isFresh()) {
                $data->dataRead();
            }
            
            if (! $data->isEmpty()) {
                foreach ($data->getRows() as $row) {
                    $row_content = '';
                    foreach ($widget->getColumns() as $col) {
                        $classes = '';
                        if ($col->isHidden()) {
                            $classes = 'hidden';
                        }
                        $row_content .= '<span data-field="' . $col->getDataColumnName() . '" class="exf-data-value ' . $classes . '">' . $row[$col->getDataColumnName()] . '</span>';
                    }
                    $list_items .= "\n" . '<li class="exf-data-row"><a href="#">' . $row_content . '</a></li>';
                }
            }
        }
        
        // Footer
        if ($widget->hasButtons()) {
            $buttons = str_replace('class="btn', 'class="btn-xs btn', $this->buildHtmlToolbars());
            $footer = <<<HTML
    <li class="footer">
		{$buttons}
	</li>
HTML;
        }
        
        // output the html code
        // TODO Use handlebars for lazy loading. Perhaps a common method with DataCards will be possible.
        $output = <<<HTML
<ul class="exf-menu" id="{$this->getId()}">
    <li class="header">
		{$widget->getCaption()}
	</li>
	<li>
        <ul class="menu">
            {$list_items}
    	</ul>
	</li>
    {$footer}
</ul>
HTML;
        
        return $output;
    }

    function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\DataCards */
        $widget = $this->getWidget();
        $buttons_js = '';
        
        // buttons
        if ($widget->hasButtons()) {
            foreach ($widget->getButtons() as $button) {
                $buttons_js .= $this->getTemplate()->buildJs($button);
            }
        }
        
        $output = <<<JS

{$this->buildJsClickFunctions('#'.$this->getId().' .exf-data-row', 'bg-light-blue-active')}

{$this->buildJsDataGetterFunction('#'.$this->getId().' .exf-data-row.selected .exf-data-value')}

{$buttons_js}

JS;
        
        return $output;
    }

    /**
     * Generates a JS function to be called by the data getter
     * @param string $data_field_selector
     * @return string
     */
    protected function buildJsDataGetterFunction($data_field_selector)
    {
        return <<<JS

function {$this->buildJsFunctionPrefix()}getSelection(){
	var data = [];
	var row = {};
	$('{$data_field_selector}').each(function(index, element){
		row[$(element).data('field')] = $(element).text();
	});
	data.push(row);
	return data;
}

JS;
    }

    /**
     * Registers JS listeners for click actions on data rows/items.
     * 
     * This is also where multiselection is to be taken care of as well as
     * distinguishing between two clicks and a double click.
     * 
     * @param string $row_selector
     * @param string $highlight_css_class
     * @return string
     */
    protected function buildJsClickFunctions($row_selector, $highlight_css_class)
    {
        $widget = $this->getWidget();
        // Click actions
        // Single click. Currently only supports one double click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            $leftclick_script = $this->getTemplate()->getElement($leftclick_button)->buildJsClickFunctionName() . '()';
        }
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $dblclick_script = $this->getTemplate()->getElement($dblclick_button)->buildJsClickFunctionName() . '()';
        }
        
        // Right click. Currently only supports one right click action - the first one in the list of buttons
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $rightclick_script = $this->getTemplate()->getElement($rightclick_button)->buildJsClickFunctionName() . '()';
        }
        
        // NOTE: it is important, to remove any existing click listeners before
        // binding new ones as the widget might be loaded via AJAX, so the same
        // listeners would already exist if it is a refresh.
        
        return <<<JS
        
    $(document).off('click', '{$row_selector}').on('click', '{$row_selector}', function(e){
		$('{$row_selector}').removeClass('{$highlight_css_class}').removeClass('selected');
		$(this).addClass('{$highlight_css_class}').addClass('selected');
		{$leftclick_script}
	});
	
	$(document).off('dblclick', '{$row_selector}').on('dblclick', '{$row_selector}', function(e){
		{$dblclick_script}
	});

    $(document).off('contextmenu', '{$row_selector}').on('contextmenu', '{$row_selector}', function(e){
		{$rightclick_script}
	});
JS;
    }

    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if (is_null($action)) {
            // TODO
        } else {
            $rows = $this->buildJsFunctionPrefix() . "getSelection()";
        }
        return "{oId: '" . $this->getWidget()->getMetaObject()->getId() . "', rows: " . $rows . "}";
    }

    /**
     * Renders javascript event handlers for tapping on rows.
     * A single tap (or click) selects a row, while a longtap opens the
     * context menu for the row if one is defined. The long tap also selects the row.
     */
    protected function buildJsRowSelection()
    {
        $output = '';
        if ($this->getWidget()->getMultiSelect()) {
            $output .= "
				$('#{$this->getId()} tbody').on( 'click', 'tr', function (event) {
					if (event.which !== 1) return;
						$(this).toggleClass('selected bg-aqua');
					} );
				";
        } else {
            // Select a row on tap. Make sure no other row is selected
            $output .= "
				$('#{$this->getId()} tbody').on( 'click', 'tr', function (event) {
					if(!(!event.detail || event.detail==1)) return;
				 	if ($(this).hasClass('unselectable')) return;
					
					if ( $(this).hasClass('selected bg-aqua') ) {
						$(this).removeClass('selected bg-aqua');
					} else {
						{$this->getId()}_table.$('tr.selected').removeClass('selected bg-aqua');
						$(this).addClass('selected bg-aqua');
					}
				} );
			";
        }
        return $output;
    }

    protected function buildJsPagination()
    {
        $output = <<<JS
	$('#{$this->getId()}_prevPage').on('click', function(){{$this->getId()}_pages.previous(); {$this->buildJsRefresh(true)}});
	$('#{$this->getId()}_nextPage').on('click', function(){{$this->getId()}_pages.next(); {$this->buildJsRefresh(true)}});
	
	$('#{$this->getId()}_pageInfo').on('click', function(){
		$('#{$this->getId()}_pageInput').val({$this->getId()}_table.page()+1);
	});
	
	$('#{$this->getId()}_pageControls').on('hidden.bs.dropdown', function(){
		{$this->getId()}_table.page(parseInt($('#{$this->getId()}_pageSlider').val())-1).draw(false);
	});
JS;
        return $output;
    }

    public function buildJsRefresh($keep_pagination_position = false)
    {
        return '';
    }
}
?>