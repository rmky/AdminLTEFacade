<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryLiveReferenceTrait;

class lteInput extends lteText
{
    
    use JqueryLiveReferenceTrait;

    protected function init()
    {
        parent::init();
        $this->setElementType('text');
        // If the input's value is bound to another element via an expression, we need to make sure, that other element will
        // change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
        // has not generated it's JS code yet!
        $this->registerLiveReferenceAtLinkedElement();
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
    }

    function generateHtml()
    {
        $output = '
						<label for="' . $this->getId() . '">' . $this->getWidget()->getCaption() . '</label>
						<input class="form-control"
								type="' . $this->getElementType() . '"
								name="' . $this->getWidget()->getAttributeAlias() . '" 
								value="' . $this->escapeString($this->getValueWithDefaults()) . '" 
								id="' . $this->getId() . '"  
								' . ($this->getWidget()->isRequired() ? 'required="true" ' : '') . '
								' . ($this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '') . '/>
					';
        return $this->buildHtmlWrapper($output);
    }

    public function buildHtmlWrapper($inner_html)
    {
        $output = '
					<div class="fitem exf_input exf_grid_item ' . $this->getWidthClasses() . '" title="' . $this->buildHintText() . '">
							' . $inner_html . '
					</div>';
        return $output;
    }

    public function getValueWithDefaults()
    {
        if ($this->getWidget()->getValueExpression() && $this->getWidget()->getValueExpression()->isReference()) {
            $value = '';
        } else {
            $value = $this->getWidget()->getValue();
        }
        if (is_null($value) || $value === '') {
            $value = $this->getWidget()->getDefaultValue();
        }
        return $value;
    }

    function generateJs()
    {
        $output = '';
        
        if ($this->getWidget()->isRequired()) {
            $output .= $this->buildJsRequired();
        }
        $output .= $this->buildJsOnChangeHandler();

        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        return $output;
    }

    function buildJsRequired()
    {
        $output = '
					// checks if a value is set when the element is created
					if ($(\'#' . $this->getId() . '\').first().val()) {
						$(\'#' . $this->getId() . '\').first().parent().removeClass(\'invalid\');
					} else {
						$(\'#' . $this->getId() . '\').first().parent().addClass(\'invalid\');
					};
					
					// checks if a value is set when the element is changed
					$(\'#' . $this->getId() . '\').on(\'input change\', function() {
						if (this.value) {
							$(this).parent().removeClass(\'invalid\');
						} else {
							$(this).parent().addClass(\'invalid\');
						}
					});';
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::buildJsDataGetter($action, $custom_body_js)
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($this->getWidget()->isDisplayOnly()) {
            return '{}';
        } else {
            return parent::buildJsDataGetter($action);
        }
    }

    protected function buildJsOnChangeHandler()
    {
        if ($this->getOnChangeScript()) {
            // verknuepfter Wert wird initialisiert, bei Aenderungen aktualisiert
            $output = '
					' . $this->getOnChangeScript() . '
					$("#' . $this->getId() . '").on("input change", function() {
						' . $this->getOnChangeScript() . '
					});';
        } else {
            $output = '';
        }
        
        return $output;
    }

    function buildJsValueSetter($value)
    {
        $output = '
				var ' . $this->getId() . ' = $("#' . $this->getId() . '");
				' . $this->getId() . '.val(' . $value . ');
				' . $this->getId() . '.trigger("change");';
        
        return $output;
    }
}
?>