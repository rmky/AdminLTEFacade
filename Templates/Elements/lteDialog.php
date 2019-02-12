<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\AbstractWidget;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;

/**
 *
 * @method Dialog getWidget()
 *        
 * @author aka
 *        
 */
class lteDialog extends lteForm
{

    /**
     *
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(false);
    }
    
    function buildJs()
    {
        $output = '';
        if (! $this->isLazyLoading()) {
            $output .= $this->buildJsForWidgets();
        }
        $output .= $this->buildJsButtons();
        // Layout-Funktionen hinzufuegen
        $output .= $this->buildJsLayouterFunction();
        $output .= $this->buildJsLayouterOnShownFunction();
        // Masonry layout starten wenn der Dialog gezeigt wird
        $output .= <<<JS

    $("#{$this->getId()}").on("shown.bs.modal", function() {
        {$this->buildJsFunctionPrefix()}layouterOnShown();
    });
JS;
        
        return $output;
    }

    public function buildHtml()
    {
        $output = '';
        $style = '';
        $widget = $this->getWidget();
        
        if ($widget->getWidth()->isRelative() || $widget->getWidth()->isPercentual()){
            $style .= 'width: ' . $widget->getValue() . ';';
        }
        
        if (! $this->isLazyLoading()) {
            $output = <<<HTML

<div class="modal" id="{$this->getId()}">
    <div class="modal-dialog {$this->getWidthClasses()}" style="{$style}">
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
                {$this->buildHtmlToolbars()}
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
HTML;
        }
        return $output;
    }
    
    public function getWidthClasses()
    {
        $dim = $this->getWidget()->getWidth();   
        if ($dim->isUndefined() || $dim->isMax()) {
            return 'modal-lg';
        } elseif ($dim->isRelative()){
            if ($dim->getValue() >= 2){
                return 'modal-lg';
            } else {
                return ''; 
            }
        }
        return '';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteWidgetGrid::buildJsLayouterFunction()
     */
    protected function buildJsLayouterFunction() : string
    {
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {}
JS;
        
        return $output;
    }

    /**
     * Returns a JavaScript-Function which layouts the dialog once it is visible.
     *
     * @return string
     */
    public function buildJsLayouterOnShownFunction()
    {
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouterOnShown() {
        {$this->getChildrenLayoutScript($this->getWidget())}
    }
JS;
        
        return $output;
    }

    /**
     * Returns a JavaScript-Snippet which layouts the children of the dialog.
     *
     * @param AbstractWidget $widget
     * @return string
     */
    protected function getChildrenLayoutScript(AbstractWidget $widget)
    {
        // Diese Funktion bewegt sich rekursiv durch den Baum und gibt Layout-Skripte fuer
        // alle Layout-Widgets zurueck.
        $output = '';
        if ($widget instanceof iContainOtherWidgets) {
            foreach ($widget->getWidgets() as $child) {
                $output .= $this->getChildrenLayoutScript($child);
            }
        }
        if ($widget instanceof iLayoutWidgets) {
            $output .= $this->getTemplate()->getElement($widget)->buildJsLayouter() . ';';
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\ltePanel::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DIALOG.COLUMNS_BY_DEFAULT");
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\ltePanel::inheritsNumberOfColumns()
     */
    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }
}
?>