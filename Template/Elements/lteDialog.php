<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\AbstractWidget;

/**
 *
 * @method Dialog getWidget()
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
        // Layout-Funktionen hinzufuegen
        $output .= $this->buildJsLayouterFunction();
        $output .= $this->buildJsLayouterOnShownFunction();
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
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\ltePanel::buildJsLayouterFunction()
     */
    public function buildJsLayouterFunction()
    {
        $output = <<<JS
    
    function {$this->getId()}_layouter() {}
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

    function {$this->getId()}_layouterOnShown() {
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
        // bestimmte Layout-Widgets zurueck. Sie sucht das letzte Layout-Widget, welches kein
        // weiteres Layout-Widget beinhaltet und gibt dessen Layout-Skript zurueck.
        // Uebergeordnete Layout-Widgets werden nicht beachtet, da ihre Layout-Skripte in den
        // Layout-Skripten der untergeordneten Widgets am Ende sowieso aufgerufen werden.
        foreach ($widget->getChildren() as $child) {
            if ($child instanceof iLayoutWidgets) {
                $childScript = $this->getLayoutElements($child);
                $output .= $childScript ? $childScript : $this->getTemplate()->getElement($child)->buildJsLayouter() . ';';
            }
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\ltePanel::getDefaultColumnNumber()
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DIALOG.COLUMNS_BY_DEFAULT");
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\ltePanel::inheritsColumnNumber()
     */
    public function inheritsColumnNumber()
    {
        return false;
    }
}
?>