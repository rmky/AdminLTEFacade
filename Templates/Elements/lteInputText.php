<?php
namespace exface\AdminLteTemplate\Templates\Elements;

class lteInputText extends lteInput
{

    function buildHtml()
    {
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true"' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled"' : '';
        
        $output = <<<HTML

                        {$this->buildHtmlLabel()}
                        <textarea class="form-control"
                                name="{$this->getWidget()->getAttributeAlias()}"
                                id="{$this->getId()}"
                                style="height: {$this->getHeight()}; width: 100%;" 
                                {$requiredScript}
                                {$disabledScript}>{$this->getValueWithDefaults()}</textarea>
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    function buildJs()
    {
        $output = parent::buildJs();
        
        // Das Layout des Containers wird erneuert wenn das InputText die Groesse veraendert.
        if ($layoutWidget = $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $output .= <<<JS

    $("#{$this->getId()}").on("resize", function() {
        {$this->getTemplate()->getElement($layoutWidget)->buildJsLayouter()};
    });
JS;
        }
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 2) . 'px';
    }
}
?>