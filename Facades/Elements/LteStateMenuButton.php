<?php
namespace exface\AdminLteFacade\Facades\Elements;

/**
 *
 * @author SFL
 *        
 */
class lteStateMenuButton extends lteMenuButton
{

    /**
     *
     * @see \exface\Facades\jeasyui\Widgets\abstractWidget::buildHtml()
     */
    function buildHtml()
    {
        $widget = $this->getWidget();
        $button_no = count($widget->getButtons());
        $output = '';
        
        if ($button_no == 1) {
            /* @var $b \exface\Core\Widgets\Button */
            $b = $widget->getButtons()[0];
            $b->setCaption($widget->getCaption());
            $b->setAlign($widget->getAlign());
            $b->setVisibility($widget->getVisibility());
            $output = $this->getFacade()->getElement($b)->buildHtml();
        } elseif ($button_no > 1) {
            $output = parent::buildHtml();
        }
        
        return $output;
    }
}
?>
