<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\InputSelect;

/**
 *
 * @method InputSelect getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteInputSelect extends lteInput
{

    public function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\InputSelect */
        $widget = $this->getWidget();
        $options = '';
        $selected_cnt = count($this->getWidget()->getValues());
        foreach ($widget->getSelectableOptions() as $value => $text) {
            if ($this->getWidget()->getMultiSelect() && $selected_cnt > 1) {
                $selected = in_array($value, $this->getWidget()->getValues());
            } else {
                $selected = strcasecmp($this->getWidget()->getValueWithDefaults(), $value) === 0 ? true : false;
            }
            $options .= '
					<option value="' . $value . '"' . ($selected ? ' selected="selected"' : '') . '>' . $text . '</option>';
        }
        
        $output = '
						<label for="' . $this->getId() . '">' . $this->getWidget()->getCaption() . '</label>
						<select class="form-control"
								name="' . $this->getWidget()->getAttributeAlias() . '"
								value="' . $this->escapeString($this->getValueWithDefaults()) . '"
								id="' . $this->getId() . '"
								' . ($this->getWidget()->isRequired() ? 'required="true" ' : '') . '
								' . ($this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '') . '
								' . ($this->getWidget()->getMultiSelect() ? 'multiple' : '') . '>
							' . $options . '
						</select>
					';
        
        return $this->buildHtmlGridItemWrapper($output);
    }
}
?>