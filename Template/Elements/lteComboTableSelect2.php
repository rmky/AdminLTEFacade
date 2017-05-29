<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 * Das Hauptproblem an select2 war, dass keine Moeglichkeit gefunden wurde das
 * Element manuell neu zu laden (wichtig z.B. im valueSetter, denn es wird nur
 * eine OID gesetzt und der Rest muss aus der Datenbank nachgeladen werden).
 * Der Grund hierfuer war unter anderem auch die nicht vollstaendige Dokumen-
 * tation (z.B. keine Liste von verfuegbaren Methoden, Properties) (aber auch
 * in Google wurde keine Loesung gefunden). Weiterhin bekommt ein select2-
 * Element beim Druecken von Tab nicht automatisch den Fokus, denn es ist kein
 * <input> sondern ein <span>. Das verhindert das Arbeiten mit der Tastatur.
 */
class lteComboTableSelect2 extends lteInput
{
    
    function generateHtml()
    {
        $value = $this->escapeString($this->getValueWithDefaults());
        // $selectedText = 'Test';
        // $valueScript = $value ? '<option value="' . $value . '" selected="selected">' . $selectedText . '</option>' : '';
        $output = <<<HTML

					<label for="{$this->getId()}">{$this->getWidget()->getCaption()}</label>
					<input type="hidden"
							id="{$this->getId()}"
							name="{$this->getWidget()->getAttributeAlias()}"
							value="{$value}" />
					<select class="form-control select2"
							id="{$this->getId()}_s2"
							style="width:100%;" >
					</select>
HTML;
        return $this->buildHtmlWrapper($output);
    }
    
    function generateJs()
    {
        /* @var \exface\Core\Widgets\ComboTableSelect2 $widget */
        $widget = $this->getWidget();
        
        // Add initial value
        if ($link = $widget->getValueWidgetLink()) {
            // widget has a live reference value
            $linkedElement = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
            if ($widget->getValueText()) {
                $initialValueScript = 'ms.setSelection([{"' . $widget->getTextColumn()->getDataColumnName() . '": "' . preg_replace("/\r|\n/", "", $widget->getValueText()) . '", "' . $widget->getValueColumn()->getDataColumnName() . '": ' . $linkedElement->buildJsValueGetter($link->getColumnId()) . '}]);';
            } else {
                $initialValueScript = $this->buildJsValueSetter($linkedElement->buildJsValueGetter($link->getColumnId()));
                $initialFilterScript = ', fltr00_' . $widget->getValueColumn()->getDataColumnName() . ': ' . $linkedElement->buildJsValueGetter($link->getColumnId());
            }
        } elseif ($widget->getValue()) {
            // widget has a static value
            if ($widget->getValueText()) {
                $initialValueScript = 'ms.setSelection([{"' . $widget->getTextColumn()->getDataColumnName() . '": "' . preg_replace("/\r|\n/", "", $widget->getValueText()) . '", "' . $widget->getValueColumn()->getDataColumnName() . '": "' . $widget->getValue() . '"}]);';
            } else {
                $initialValueScript = $this->buildJsValueSetter($widget->getValue());
                $initialFilterScript = ', fltr00_' . $widget->getValueColumn()->getDataColumnName() . ': ' . $widget->getValue();
            }
        }
        
        // Add other options
        $options = [];
        $options[] = $widget->getMultiSelect() ? 'multiple: true' : 'multiple: false';
        $options[] = $widget->isDisabled() ? 'disabled: true' : 'disabled: false';
        $otherOptions = implode(",\n\t\t", $options);
        
        $initialFilterScript = '';
        
        $output = <<<JS

$(document).ready(function() {
    
	$("#{$this->getId()}_s2").select2({
		ajax: {
			url: "{$this->getAjaxUrl()}",
			method: "POST",
			dataType: "json",
			delay: 400,
			data: function (params) {
				var queryParams = {
					q: params.term,
					resource: "{$this->getPageId()}",
					element: "{$widget->getTable()->getId()}",
					object: "{$widget->getTable()->getMetaObject()->getId()}",
					action: "{$widget->getLazyLoadingAction()}",
					length: {$widget->getMaxSuggestions()},
					start: 0
					{$initialFilterScript}
				}
				return queryParams;
			},
			processResults: function (data, params) {
				for (i = 0; i < data.data.length; i++) {
					// id muss gesetzt werden, html enthaelt den in den Resultaten angezeigten Text.
					if (!data.data[i].id) { data.data[i].id = data.data[i]["{$widget->getValueColumn()->getDataColumnName()}"]; }
					//if (!data.data[i].html) { data.data[i].html = "<div class=\"row\"><div class=\"col-md-2\">" + data.data[i].CONSUMER_NO + "</div><div class=\"col-md-2\">" + data.data[i].EXT_CONSUMER_NO + "</div><div class=\"col-md-2\">" + data.data[i].CONSUMER_DESCRIPTION + "</div><div class=\"col-md-2\">" + data.data[i].CONSUMER_MAIL_PHONE + "</div><div class=\"col-md-2\">" + data.data[i].CONSUMER_CLASS__LABEL + "</div><div class=\"col-md-2\">" + data.data[i].STATE_ID + "</div></div>"; }
					if (!data.data[i].html) { data.data[i].html = data.data[i]["{$widget->getTextColumn()->getDataColumnName()}"]; }
				}
				// Eine Headerzeile hinzufuegen
				data.data.unshift({
					id: "headerRow",
					html: "<div class=\"row\"><div class=\"col-md-2\">Kundennr.</div><div class=\"col-md-2\">Ext. Kundennr.</div><div class=\"col-md-2\">Anrede</div><div class=\"col-md-2\">Kontaktdaten</div><div class=\"col-md-2\">Kundenklasse</div><div class=\"col-md-2\">Status</div></div>",
					disabled: true
				});
				return { results: data.data };
			}
		},
		escapeMarkup: function(markup) { return markup; },
		templateSelection: function(data) { return data["{$widget->getTextColumn()->getDataColumnName()}"]; },
		templateResult: function(data) { return data.html; },
		
		// placeholder muss gesetzt sein, damit allowClear funktioniert
		placeholder: "Type or click here",
		// Zeigt ein Kreuz zum Leeren der ComboTable an (triggert change nicht).
		allowClear: true,
		
		{$otherOptions}
	});
    
	$("#{$this->getId()}_s2").on("change", function(e) {
		$("#{$this->getId()}").val({$this->buildJsValueGetter()}).trigger("change");
		{$this->getOnChangeScript()}
	});
});
JS;
        
        /*
         * data: "{$this->getAjaxUrl()}",
         * dataUrlParams: {
         * resource: "{$this->getPageId()}",
         * element: "{$widget->getTable()->getId()}",
         * object: "{$widget->getTable()->getMetaObject()->getId()}",
         * action: "{$widget->getLazyLoadingAction()}",
         * length: {$widget->getMaxSuggestions()},
         * start: 0
         * {$initialFilterScript}
         * },
         * queryParam: 'q',
         * resultsField: 'data',
         * valueField: '{$widget->getValueColumn()->getDataColumnName()}',
         * displayField: '{$widget->getTextColumn()->getDataColumnName()}',
         * allowFreeEntries: false
         * {$otherOptions}
         * });
         *
         * {$initialValueScript}
         *
         * $(ms).on("selectionchange", function(e,m){
         * $("#{$this->getId()}").val(m.getValue().join()).trigger("change");
         * {$this->getOnChangeScript()}
         * });
         *
         * $(ms).on("beforeload", function(e,m){
         * {$this->buildJsOnBeforeloadLiveReference()}
         * });
         *
         * $(ms).on("load", function(e,m){
         * {$this->buildJsOnLoadLiveReference()}
         * });
         * });
         * JS;
         */
        
		$output .= <<<JS

		{$this->buildJsValueGetterFunction()}
JS;
		
        if ($widget->isRequired()) {
            $output .= $this->buildJsRequired();
        }
        
        return $output;
    }
    
    function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<link href="exface/vendor/almasaeed2010/adminlte/plugins/select2/select2.min.css" rel="stylesheet">';
        $headers[] = '<script src="exface/vendor/almasaeed2010/adminlte/plugins/select2/select2.min.js"></script>';
        return $headers;
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    function buildJsValueGetter($column = null, $row = null)
    {
        if (is_null($column) || $column === '') {
            $column = $this->getWidget()->getTable()->getUidColumn()->getDataColumnName();
        }
        
        $params = $column ? '"' . $column . '"' : '';
        $params = $row ? ($params ? $params . ', ' . $row : $row) : $params;
        return $this->getId() . '_valueGetter(' . $params . ')';
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
    function buildJsValueGetterFunction()
    {
        $widget = $this->getWidget();
        
        if ($widget->getMultiSelect()) {
            $valueGetter = <<<JS

							var resultArray = [];
							for (i = 0; i < rows.length; i++) {
								resultArray.push(rows[i][column]);
							}
							return resultArray.join();
JS;
        } else {
            $valueGetter = <<<JS

						    return rows[0][column];
JS;
        }
        
        $output = <<<JS

                function {$this->getId()}_valueGetter(column, row){
    				var {$this->getId()}_s2 = $("#{$this->getId()}_s2");
    				if ({$this->getId()}_s2.data("select2")) {
    					var rows = {$this->getId()}_s2.select2("data");
    					if (rows && rows.length > 0) {
    						{$valueGetter}
    					} else {
    						return "";
    					}
    				} else {
    					return $("#{$this->getId()}").val();
    				}
    			}
JS;
        
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
        
        if ($widget->getMultiSelect()) {
            $valueSetter = <<<JS

                    {$this->getId()}_s2.val(valueArray).trigger("change");
					$("#{$this->getId()}").val(value).trigger("change");
JS;
        } else {
            $valueSetter = <<<JS

					if (valueArray.length == 1) {
					    //{$this->getId()}_s2.val([]).trigger("change");
						{$this->getId()}_s2.val(valueArray).trigger("change");
						$("#{$this->getId()}").val(value).trigger("change");
					}
JS;
        }
        
        $output = <<<JS

				var {$this->getId()}_s2 = $("#{$this->getId()}_s2");
				var value = {$value}, valueArray;
				if ({$this->getId()}_s2.data("select2")) {
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
					//{$this->getId()}_s2.magicSuggest().clear();
                    
                    {$valueSetter}
        
                    //{$this->getId()}_s2.magicSuggest().getDataUrlParams().jsValueSetterUpdate = true;
					//{$this->getId()}_s2.magicSuggest().setData("{$this->getAjaxUrl()}");
				} else {
					$("#{$this->getId()}").val(value).trigger("change");
				}
JS;
        
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
                    $linkedElement = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = ' . $linkedElement->buildJsValueGetter($link->getColumnId()) . ';';
                } else {
                    // filter has a static value
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $fltr->getAttributeAlias() . ' = "' . $fltr->getValue() . '";';
                }
            }
        }
        $filtersScript = implode("\n\t\t\t\t\t", $filters);
        // Add value filter (to show proper label for a set value)
        $valueFilters = [];
        $valueFilters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $widget->getValueColumn()->getDataColumnName() . ' = m.getValue().join();';
        $valueFiltersScript = implode("\n\t\t\t\t\t", $valueFilters);
        
        $output = '
				var dataUrlParams = m.getDataUrlParams();
                
				if (dataUrlParams.jsValueSetterUpdate) {
					' . $valueFiltersScript . '
				} else {
					' . $filtersScript . '
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