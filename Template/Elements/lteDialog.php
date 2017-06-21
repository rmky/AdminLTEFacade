<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Dialog;

/**
 *
 * @method Dialog getWidget()
 *        
 * @author aka
 *        
 */
class lteDialog extends lteForm
{

    private $number_of_columns = null;

    private $searched_for_number_of_columns = false;

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

    /**
     * Determines the number of columns of a widget, based on the width of widget, the number
     * of columns of the parent layout widget and the default number of columns of the widget.
     *
     * @return number
     */
    public function getNumberOfColumns()
    {
        if (! $this->searched_for_number_of_columns) {
            $widget = $this->getWidget();
            if (! is_null($widget->getNumberOfColumns())) {
                $this->number_of_columns = $widget->getNumberOfColumns();
            } elseif ($widget->getWidth()->isRelative() && !$widget->getWidth()->isMax()) {
                $width = $widget->getWidth()->getValue();
                if ($width < 1) {
                    $width = 1;
                }
                $this->number_of_columns = $width;
            } else {
                $this->number_of_columns = $this->getTemplate()->getConfig()->getOption("WIDGET.DIALOG.COLUMNS_BY_DEFAULT");
            }
            $this->searched_for_number_of_columns = true;
        }
        return $this->number_of_columns;
    }
}
?>