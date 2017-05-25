<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Dialog;

/**
 *
 * @method Dialog get_widget()
 *        
 * @author aka
 *        
 */
class lteDialog extends lteForm
{

    function generateJs()
    {
        $output = '';
        if (! $this->getWidget()->getLazyLoading()) {
            $output .= $this->buildJsForWidgets();
        }
        $output .= $this->buildJsButtons();
        return $output;
    }

    public function generateHtml()
    {
        $output = '';
        if (! $this->getWidget()->getLazyLoading()) {
            $output = <<<HTML
<div class="modal" id="{$this->getId()}">
	<div class="modal-dialog" style="width:{$this->getWidth()};">
		<div class="modal-content box">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{$this->getWidget()->getCaption()}</h4>
			</div>
			<div class="modal-body">
				<div class="modal-body-content-wrapper row">
					{$this->buildHtmlForWidgets()}
				</div>
			</div>
			<div class="modal-footer">
				{$this->buildHtmlButtons()}
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
HTML;
        }
        return $output;
    }

    function getWidth()
    {
        if ($this->getWidget()->getWidth()->isUndefined()) {
            $this->getWidget()->setWidth((2 * $this->getWidthRelativeUnit() + 35) . 'px');
        }
        return parent::getWidth();
    }
}
?>