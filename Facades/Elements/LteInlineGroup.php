<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\InlineGroup;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsInlineGroupTrait;
use exface\Core\Interfaces\WidgetInterface;

/**
 * Renders a inline-widget similar to Display or Input with a caption, but with
 * multiple contained widgets instead of the single value next to the caption.
 *
 * @author Andrej Kabachnik
 *        
 * @method InlineGroup getWidget()
 */
class LteInlineGroup extends LteValue
{
    use JqueryContainerTrait;
    use JsInlineGroupTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
        $this->optimizeChildrenWidths();
        // Make all direct children use CSS with instead of bootstrap gird classes,
        // so that the width optimization works correctly
        foreach ($this->getWidget()->getWidgets() as $subw) {
            $this->getFacade()->getElement($subw)->setWidthUsesGridClasses(false);
        }
        return;
    }
    
    /**
     * Returns the padding the given child in pixels.
     * 
     * Currently every widget has a right-padding of 4px except for the last widget.
     * 
     * @return int
     */
    protected function getChildPadding(WidgetInterface $child) : int
    {
        $group = $this->getWidget();
        if ($child === $group->getWidget($group->countWidgets()-1)) {
            return 0;
        }
        return 4;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildHtml()
     */
    public function buildHtml()
    {
        $output = <<<HTML

                        {$this->buildHtmlLabel()}
                        {$this->buildHtmlForWidgets()}                     
                            
HTML;
        return $this->buildHtmlGridItemWrapper($output);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inline-group';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsForWidgets();
    }
}