<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;

/**
 * generates jEasyUI-Buttons for ExFace dialogs
 * 
 * @author Andrej Kabachnik
 *        
 */
class lteDialogButton extends lteButton
{

    protected function buildJsClickCallServerAction(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        // Check if all required attributes are filled in before sending the request.
        $input_widget = $input_element->getWidget();
        
        $output = "
				var invalidElements = [];";
        if ($input_widget instanceof iContainOtherWidgets) {
            foreach ($input_element->getWidget()->getInputWidgets() as $child) {
                if ($child->isRequired() && ! $child->isHidden()) {
                    $childValueGetter = $this->getTemplate()
                        ->getElement($child)
                        ->buildJsValueGetter();
                    if (! $alias = $child->getCaption()) {
                        $alias = $child instanceof iShowSingleAttribute ? $child->getAttributeAlias() : $child->getMetaObject()->getAliasWithNamespace();
                    }
                    $output .= "
						if(!{$childValueGetter}) { invalidElements.push('" . $alias . "'); }";
                }
            }
        }
        $output .= "
					if(invalidElements.length > 0) {
						{$this->buildJsShowMessageError('"' . $this->translate('MESSAGE.FILL_REQUIRED_ATTRIBUTES') . '" + invalidElements.join(", ")')}
					} else {
						" . parent::buildJsClickCallServerAction($action, $input_element) . "
					}";
        return $output;
    }
}
?>