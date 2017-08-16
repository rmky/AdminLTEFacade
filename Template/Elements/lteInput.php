<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait;

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
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true" ' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML

                        <label for="{$this->getId()}">{$this->getWidget()->getCaption()}</label>
                        <input class="form-control"
                            type="{$this->getElementType()}"
                            name="{$this->getWidget()->getAttributeAlias()}" 
                            value="{$this->escapeString($this->getValueWithDefaults())}" 
                            id="{$this->getId()}"  
                            {$requiredScript}
                            {$disabledScript} />

HTML;
        return $this->buildHtmlWrapper($output);
    }

    public function buildHtmlWrapper($inner_html)
    {
        $output = <<<HTML

                    <div class="exf_input fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()}" title="{$this->buildHintText()}">
                        {$inner_html}
                    </div>
HTML;
        
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

    /**
     * Returns a JavaScript-snippet, which highlights an invalid widget
     * (similiar to the JEasyUi-Template).
     *  
     * @return string
     */
    function buildJsRequired()
    {
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}validate() {
        if ({$this->buildJsValidator()}) {
            $("#{$this->getId()}").parent().removeClass("invalid");
        } else {
            $("#{$this->getId()}").parent().addClass("invalid");
        }
    }
    
    // Ueberprueft die Validitaet wenn das Element erzeugt wird.
    {$this->buildJsFunctionPrefix()}validate();
    // Ueberprueft die Validitaet wenn das Element geaendert wird.
    $("#{$this->getId()}").on("input change", function() {
        {$this->buildJsFunctionPrefix()}validate();
    });
JS;
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsDataGetter($action, $custom_body_js)
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
        $output = '';
        if ($this->getOnChangeScript()) {
            $output = <<<JS

$("#{$this->getId()}").on("input change", function() {
    {$this->getOnChangeScript()}
});
JS;
        }
        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueSetter()
     */
    function buildJsValueSetter($value)
    {
        return '$("#' . $this->getId() . '").val(' . $value . ').trigger("change")';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        $widget = $this->getWidget();
        
        $must_be_validated = $widget->isRequired() && ! ($widget->isHidden() || $widget->isReadonly() || $widget->isDisabled() || $widget->isDisplayOnly());
        if ($must_be_validated) {
            $output = 'Boolean($("#' . $this->getId() . '").val())';
        } else {
            $output = 'true';
        }
        
        return $output;
    }
}
?>