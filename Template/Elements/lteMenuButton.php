<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Button;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryButtonTrait;

/**
 * generates jQuery Mobile buttons for ExFace
 *
 * @author Andrej Kabachnik
 *        
 */
class lteMenuButton extends lteAbstractElement
{
    
    use JqueryButtonTrait;

    /**
     *
     * @see \exface\Templates\jeasyui\Widgets\abstractWidget::generateHtml()
     */
    function generateHtml()
    {
        $buttons_html = '';
        $output = '';
        /* @var $b \exface\Core\Widgets\Button */
        foreach ($this->getWidget()->getButtons() as $b) {
            // If the button has an action, make some action specific HTML depending on the action
            if ($action = $b->getAction()) {
                if ($action->implementsInterface('iShowDialog')) {
                    $dialog_widget = $action->getDialogWidget();
                    $output .= $this->getTemplate()->generateHtml($dialog_widget);
                }
            }
            // In any case, create a menu entry
            $disabled_class = $b->isDisabled() ? ' disabled' : '';
            $buttons_html .= '
					<li class="' . $disabled_class . '">
						<a id="' . $this->getTemplate()->getElement($b)->getId() . '" data-target="#"' . ($b->isDisabled() ? '' : ' onclick="' . $this->buildJsButtonFunctionName($b) . '();"') . '>
							<i class="' . $this->buildCssIconClass($b->getIconName()) . '"></i>' . $b->getCaption() . '
						</a>
					</li>';
        }
        $icon = ($this->getWidget()->getIconName() ? '<i class="' . $this->buildCssIconClass($this->getWidget()->getIconName()) . '"></i> ' : '');
        
        $button_class = $this->getWidget()->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED ? ' btn-primary' : ' btn-default';
        $align_class = $this->getAlignClass();
        
        $output .= <<<HTML

<div class="btn-group dropup{$align_class}">
	<button type="button" class="btn{$button_class}" data-toggle="dropdown">{$icon}{$this->getWidget()->getCaption()}</button>
	<ul class="dropdown-menu" role="menu">
		{$buttons_html}
	</ul>
</div>
HTML;
        
        return $output;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \exface\AdminLteTemplate\Template\Elements\jqmAbstractElement::generateJs()
     */
    function generateJs()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $b) {
            if ($js_click_function = $this->getTemplate()->getElement($b)->buildJsClickFunction()) {
                $output .= "
					function " . $this->buildJsButtonFunctionName($b) . "(){
						" . $js_click_function . "
					}
					";
            }
        }
        return $output;
    }

    function buildJsButtonFunctionName(Button $button)
    {
        return $this->getTemplate()->getElement($button)->buildJsClickFunctionName();
    }

    function getAlignClass()
    {
        $align = $this->getWidget()->getAlign();
        if ($align == 'left') {
            $align_class = ' pull-left';
        } elseif ($align == 'right') {
            $align_class = ' pull-right';
        } else {
            $align_class = '';
        }
        return $align_class;
    }
}
?>