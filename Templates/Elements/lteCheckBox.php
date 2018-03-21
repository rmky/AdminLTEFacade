<?php
namespace exface\AdminLteTemplate\Templates\Elements;

class lteCheckBox extends lteAbstractElement
{

    function buildHtml()
    {
        $checkedScript = $this->getWidget()->getValue() ? 'checked="checked"' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled"' : '';
        $output = <<<HTML

                    <div class="exf-input exf-grid-item {$this->getMasonryItemClass()} checkbox {$this->getWidthClasses()}" title="{$this->buildHintText()}">
                        <label>
                            <input type="checkbox" value="1"
                                    form=""
                                    id="{$this->getWidget()->getId()}_checkbox"
                                    onchange="$('#{$this->getWidget()->getId()}').val(this.checked);"
                                    {$checkedScript}
                                    {$disabledScript} />
                            {$this->getWidget()->getCaption()}
                        </label>
                        <input type="hidden" name="{$this->getWidget()->getAttributeAlias()}" id="{$this->getWidget()->getId()}" value="{$this->getWidget()->getValue()}" />
                    </div>
HTML;
        return $output;
    }

    function buildJs()
    {
        return '';
    }
}
?>