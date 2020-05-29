<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\Button;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Widgets\MenuButton;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;

/**
 * generates jQuery Mobile buttons for ExFace
 *
 * @method MenuButton getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class LteMenuButton extends lteAbstractElement
{
    use JqueryButtonTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    function buildHtml()
    {
        $output = '';
        
        $icon = ($this->getWidget()->getIcon() ? '<i class="' . $this->buildCssIconClass($this->getWidget()->getIcon()) . '"></i> ' : '');
        
        $button_class = $this->getWidget()->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED ? ' btn-primary' : ' btn-default';
        $align_class = $this->getAlignClass();
        
        $output .= <<<HTML
 
<div class="dropdown {$align_class}">
<button type="button" class="btn dropdown-toggle{$button_class}" data-toggle="dropdown">{$icon}{$this->getWidget()->getCaption()} <span class="caret"></span></button>
	<ul class="dropdown-menu" role="menu">
		{$this->getFacade()->getElement($this->getWidget()->getMenu())->buildHtmlButtons()}
	</ul>
</div>
HTML;
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array_merge(parent::buildHtmlHeadTags(), $this->getFacade()->getElement($this->getWidget()->getMenu())->buildHtmlHeadTags());
    }

    /**
     * {@inheritdoc}
     *
     * @see \exface\AdminLTEFacade\Facades\Elements\LteAbstractElement::buildJs()
     */
    public function buildJs()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $b) {
            $output .= "\n" . $this->getFacade()->getElement($b)->buildJs();
        }
        return $output;
    }

    protected function getAlignClass()
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
    
    /**
     * 
     * {@inheritdoc}
     * @see JqueryButtonTrait::buildJsCloseDialog()
     */
    protected function buildJsCloseDialog($widget, $input_element)
    {
        return '';
    }
}
?>