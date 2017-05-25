<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 * In jQuery Mobile a ComboTable is represented by a filterable UL-list.
 * The code is based on the JQM-example below.
 * jqm example: http://demos.jquerymobile.com/1.4.5/listview-autocomplete-remote/
 * 
 * @author Andrej Kabachnik
 *        
 */
class lteComboTable extends lteInput
{

    private $min_chars_to_search = 1;

    function generateHtml()
    {
        $value = $this->escapeString($this->getValueWithDefaults());
        $output = '
						<label for="' . $this->getId() . '">' . $this->getWidget()->getCaption() . '</label>
						<input type="hidden"
								id="' . $this->getId() . '" 
								name="' . $this->getWidget()->getAttributeAlias() . '"
								value="' . $value . '" />
						<input class="form-control"
								id="' . $this->getId() . '_ms"
								' . ($value ? "value='[\"" . $value . "\"]' " : '') . '/>
					';
        return $this->buildHtmlWrapper($output);
    }

    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\ComboTable */
        $widget = $this->getWidget();
        
        // Add initial value
        if ($link = $widget->getValueWidgetLink()) {
            // widget has a live reference value
            $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
            if ($widget->getValueText()) {
                $initial_value_script = 'ms.setSelection([{"' . $widget->getTextColumn()->getDataColumnName() . '": "' . preg_replace("/\r|\n/", "", $widget->getValueText()) . '", "' . $widget->getValueColumn()->getDataColumnName() . '": ' . $linked_element->buildJsValueGetter($link->getColumnId()) . '}]);';
            } else {
                $initial_value_script = $this->buildJsValueSetter($linked_element->buildJsValueGetter($link->getColumnId()));
                $initial_filter_script = ', fltr00_' . $widget->getValueColumn()->getDataColumnName() . ': ' . $linked_element->buildJsValueGetter($link->getColumnId());
            }
        } elseif ($widget->getValue()) {
            // widget has a static value
            if ($widget->getValueText()) {
                $initial_value_script = 'ms.setSelection([{"' . $widget->getTextColumn()->getDataColumnName() . '": "' . preg_replace("/\r|\n/", "", $widget->getValueText()) . '", "' . $widget->getValueColumn()->getDataColumnName() . '": "' . $widget->getValue() . '"}]);';
            } else {
                $initial_value_script = $this->buildJsValueSetter($widget->getValue());
                $initial_filter_script = ', fltr00_' . $widget->getValueColumn()->getDataColumnName() . ': ' . $widget->getValue();
            }
        }
        
        // Add other options
        $options = [];
        if (! $widget->getMultiSelect()) {
            $options[] = 'maxSelection: 1';
        }
        if ($widget->isDisabled()) {
            $options[] = 'disabled: true';
        }
        $other_options = implode(",\n\t\t", $options);
        $other_options = $other_options ? ', ' . $other_options : '';
        
        $output = <<<JS
		
$(document).ready(function() {
	
	var ms = $('#{$this->getId()}_ms').magicSuggest({
		data: "{$this->getAjaxUrl()}",
		dataUrlParams: {
			resource: "{$this->getPageId()}",
			element: "{$widget->getTable()->getId()}",
			object: "{$widget->getTable()->getMetaObject()->getId()}",
			action: "{$widget->getLazyLoadingAction()}",
			length: {$widget->getMaxSuggestions()},
			start: 0
			{$initial_filter_script}
		},
		queryParam: 'q',
		resultsField: 'data',
		valueField: '{$widget->getValueColumn()->getDataColumnName()}',
		displayField: '{$widget->getTextColumn()->getDataColumnName()}',
		allowFreeEntries: false
		{$other_options}
	});
	
	{$initial_value_script}
	
	$(ms).on("selectionchange", function(e,m){
		$("#{$this->getId()}").val(m.getValue().join()).trigger("change");
		{$this->getOnChangeScript()}
	});
	
	$(ms).on("beforeload", function(e,m){
		{$this->buildJsOnBeforeloadLiveReference()}
	});
	
	$(ms).on("load", function(e,m){
		{$this->buildJsOnLoadLiveReference()}
	});
	
	//notwendig fuer Eingabe mit BarcodeScanner
	var {$this->getId()}_typingTimer;
	var {$this->getId()}_input = $("#{$this->getId()}_ms .ms-sel-ctn input");
	{$this->getId()}_input.on("keyup", function() {
		clearTimeout({$this->getId()}_typingTimer);
		if ({$this->getId()}_input.val()) {
			{$this->getId()}_typingTimer = setTimeout(function() {
				$("#{$this->getId()}_ms").magicSuggest().expand();
			}, 400);
		}
	});
});		
JS;
        
        if ($widget->isRequired()) {
            $output .= $this->buildJsRequired();
        }
        
        return $output;
    }

    function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
        $headers[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
        return $headers;
    }

    /**
     * Erzeugung einer JavaScript-Funktion zum Auslesen des Wertes.
     * Die zurueckgegebenen
     * Werte sind per MagicSuggest valueField definiert. Sind mehrere Werte ausgewaehlt
     * wird eine Komma-separierte Liste dieser Werte zurueckgegeben. Ist eine spezifische
     * Spalte ausgewaehlt, wird statt dem valueField der Wert dieser Spalte zurueckgegeben.
     * Ist MagicSuggest noch nicht erzeugt wird stattdessen der Wert aus dem verknuepften
     * InputHidden zurueckgegeben.
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    function buildJsValueGetter($column = null, $row = null)
    {
        if ($this->getWidget()->getMultiSelect() || is_null($column) || $column === '') {
            $output = '(function() {
					var ' . $this->getId() . '_ms = $("#' . $this->getId() . '_ms");
					if (' . $this->getId() . '_ms.data("magicSuggest")) {
						return ' . $this->getId() . '_ms.magicSuggest().getValue().join();
					} else {
						return $("#' . $this->getId() . '").val();
					}
				})()';
        } else {
            $output = '(function() {
					var ' . $this->getId() . '_ms = $("#' . $this->getId() . '_ms");
					if (' . $this->getId() . '_ms.data("magicSuggest")) {
						var row = ' . $this->getId() . '_ms.magicSuggest().getSelection();
						if (row.length > 0) { return row[0]["' . $column . '"]; } else { return ""; }
					} else {
						return $("#' . $this->getId() . '").val();
					}
				})()';
        }
        
        return $output;
    }

    /**
     * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes.
     * Ist multiselect false
     * wird der Wert nur gesetzt wenn genau ein Wert uebergeben wird. Anschliessend wird
     * der Inhalt des MagicSuggest neu geladen (um ordentliche Label anzuzeigen falls
     * auch ein entsprechender Filter gesetzt ist). Ist MagicSuggest noch nicht erzeugt
     * wird stattdessen der Wert im verknuepften InputHidden gesetzt.
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::buildJsValueSetter()
     */
    function buildJsValueSetter($value)
    {
        $widget = $this->getWidget();
        
        $output = '
				var ' . $this->getId() . '_ms = $("#' . $this->getId() . '_ms");
				var value = ' . $value . ', valueArray;
				if (' . $this->getId() . '_ms.data("magicSuggest")) {
					if (value) {
						switch ($.type(value)) {
							case "number":
								valueArray = [value]; break;
							case "string":
								valueArray = $.map(value.split(","), $.trim); break;
							case "array":
								valueArray = value; break;
							default:
								valueArray = [];
						}
					} else {
						valueArray = [];
					}
					' . $this->getId() . '_ms.magicSuggest().clear();';
        
        if ($this->getWidget()->getMultiSelect()) {
            $output .= '
					' . $this->getId() . '_ms.magicSuggest().setValue(valueArray);
					$("#' . $this->getId() . '").val(value).trigger("change");';
        } else {
            $output .= '
					if (valueArray.length == 1) {
						' . $this->getId() . '_ms.magicSuggest().setValue(valueArray);
						$("#' . $this->getId() . '").val(value).trigger("change");
					}';
        }
        
        $output .= '
					' . $this->getId() . '_ms.magicSuggest().getDataUrlParams().jsValueSetterUpdate = true;
					' . $this->getId() . '_ms.magicSuggest().setData("' . $this->getAjaxUrl() . '");
				} else {
					$("#' . $this->getId() . '").val(value).trigger("change");
				}';
        
        return $output;
    }

    /**
     * Erzeugt den JavaScript-Code welcher vor dem Laden des MagicSuggest-Inhalts
     * ausgefuehrt wird.
     * Wurde programmatisch ein Wert gesetzt, wird als Filter
     * nur dieser Wert hinzugefuegt, um das Label ordentlich anzuzeigen. Sonst werden
     * die am Widget definierten Filter gesetzt. Die Filter werden nach dem Laden
     * wieder entfernt, da sich die Werte durch Live-Referenzen aendern koennen.
     *
     * @return string
     */
    function buildJsOnBeforeloadLiveReference()
    {
        $widget = $this->getWidget();
        
        $fltrId = 0;
        // Add filters from widget
        $filters = [];
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                if ($link = $fltr->getValueWidgetLink()) {
                    // filter is a live reference
                    $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = ' . $linked_element->buildJsValueGetter($link->getColumnId()) . ';';
                } else {
                    // filter has a static value
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = "' . $fltr->getValue() . '";';
                }
            }
        }
        $filters_script = implode("\n\t\t\t\t\t", $filters);
        // Add value filter (to show proper label for a set value)
        $value_filters = [];
        $value_filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $widget->getValueColumn()->getDataColumnName() . ' = m.getValue().join();';
        $value_filters_script = implode("\n\t\t\t\t\t", $value_filters);
        
        $output = '
				var dataUrlParams = m.getDataUrlParams();
				
				if (dataUrlParams.jsValueSetterUpdate) {
					' . $value_filters_script . '
				} else {
					' . $filters_script . '
				}';
        
        return $output;
    }

    /**
     * Erzeugt den JavaScript-Code welcher nach dem Laden des MagicSuggest-Inhalts
     * ausgefuehrt wird.
     * Alle gesetzten Filter werden entfernt, da sich die Werte
     * durch Live-Referenzen aendern koennen (werden vor dem naechsten Laden wieder
     * hinzugefuegt). Wurde der Wert zuvor programmatisch gesetzt, wird er neu
     * gesetzt um das Label ordentlich anzuzeigen. Nach der Erzeugung von MagicSuggest
     * werden initiale Werte gesetzt und neu geladen.
     *
     * @return string
     */
    function buildJsOnLoadLiveReference()
    {
        $output = '
				var dataUrlParams = m.getDataUrlParams();
				
				for (key in dataUrlParams) {
					if (key.substring(0, 4) == "fltr") {
						delete dataUrlParams[key];
					}
				}
				
				if (dataUrlParams.jsValueSetterUpdate) {
					var value = m.getValue();
					m.clear();
					m.setValue(value);
					
					delete dataUrlParams.jsValueSetterUpdate;
				}';
        
        return $output;
    }
}
?>