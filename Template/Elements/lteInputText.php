<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteInputText extends lteInput
{

    function generateHtml()
    {
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true"' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled"' : '';
        
        $output = <<<HTML

                        <label for="{$this->getId()}">{$this->getWidget()->getCaption()}</label>
                        <textarea class="form-control"
                                name="{$this->getWidget()->getAttributeAlias()}"
                                id="{$this->getId()}"
                                style="height: {$this->getHeight()}; width: 100%;" 
                                {$requiredScript}
                                {$disabledScript}>{$this->getValueWithDefaults()}</textarea>
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    function generateJs()
    {
        $output = parent::generateJs();
        
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
}
?>