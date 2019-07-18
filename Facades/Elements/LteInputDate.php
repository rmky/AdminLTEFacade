<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputDateTrait;

// Es waere wuenschenswert die Formatierung des Datums abhaengig vom Locale zu machen.
// Das Problem dabei ist folgendes: Wird im DateFormatter das Datum von DateJs ent-
// sprechend dem Locale formatiert, so muss der DateParser kompatibel sein. Es kommt
// sonst z.B. beim amerikanischen Format zu Problemen. Der 5.11.2015 wird als 11/5/2015
// formatiert, dann aber entsprechend den alexa RMS Formaten als 11.5.2015 geparst. Der
// Parser von DateJs kommt hingegen leider nicht mit allen alexa RMS Formaten zurecht.

// Eine Loesung waere fuer die verschiedenen Locales verschiedene eigene Parser zu
// schreiben, dann koennte man aber auch gleich verschiedene eigene Formatter
// hinzufuegen.
// In der jetzt umgesetzten Loesung wird das Anzeigeformat in den Uebersetzungsdateien
// festgelegt. Dabei ist darauf zu achten, dass es kompatibel zum Parser ist, das
// amerikanische Format MM/dd/yyyy ist deshalb nicht moeglich, da es vom Parser als
// dd/MM/yyyy interpretiert wird.
class LteInputDate extends lteInput
{
    use JqueryInputDateTrait;

    private $bootstrapDatepickerLocale;

    protected function init()
    {
        parent::init();
        $this->setElementType('datepicker');
    }

    function buildHtml()
    {
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true" ' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML

                {$this->buildHtmlLabel()}
                <div class="form-group input-group date">
                    <input class="form-control"
                        type="text"
                        name="{$this->getWidget()->getAttributeAlias()}"
                        id="{$this->getId()}"
                        value="{$this->getValueWithDefaults()}"
                        {$requiredScript}
                        {$disabledScript} />
                    <div class="input-group-addon" onclick="$('#{$this->getId()}').{$this->getElementType()}('show');">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>

HTML;
        return $this->buildHtmlGridItemWrapper($output);
    }

    function buildJs()
    {
        $languageScript = $this->getBootstrapDatepickerLocale() ? 'language: "' . $this->getBootstrapDatepickerLocale() . '",' : '';
        if ($this->getWidget()->isRequired()) {
            $validateScript = $this->buildJsFunctionPrefix() . 'validate();';
            $requiredScript = $this->buildJsRequired();
        }
        
        $output = <<<JS

    $("#{$this->getId()}").{$this->getElementType()}({
        // Bleibt geoeffnet wenn ein Datum selektiert wird. Gibt sonst Probleme, wenn
        // eine Datumseingabe per Enter abgeschlossen wird und anschliessend eine neue
        // Datumseingabe erfolgt.
        autoclose: false,
        format: {
            toDisplay: function (date) {
                // date ist ein date-Objekt und wird zu einem String geparst
                return (date instanceof Date ? {$this->getDataTypeFormatter()->buildJsDateFormatter('date')} : '');
            },
            toValue: function(date, format, language) {
                var output = {$this->getDataTypeFormatter()->buildJsDateParserFunctionName()}(date);
                if (output) {
                    $('#{$this->getId()}')
                        .data("_internalValue", {$this->getDataTypeFormatter()->buildJsDateStringifier('output')})
                        .data("_isValid", true);
                } else {
                    $('#{$this->getId()}')
                        .data("_internalValue", "")
                        .data("_isValid", false);
                }
                {$validateScript}
                return output != null ? output.setTimezoneOffset(0) : output;
            }
        },
        {$languageScript}
        // Markiert das heutige Datum.
        todayHighlight: true
    });
    // Wird der uebergebene Wert per value="..." im HTML uebergeben, erscheint er
    // unformatiert (z.B. "-1d"). Wird der Wert hier gesetzt, wird er formatiert.
    $("#{$this->getId()}").{$this->getElementType()}("update", "{$this->escapeString($this->getValueWithDefaults())}");
    
    // Bei leeren Werten, wird die toValue-Funktion nicht aufgerufen, und damit der
    // interne Wert fuer die Rueckgabe des value-Getters nicht entfernt. Dies geschieht
    // hier.
    $("#{$this->getId()}").on("input change", function() {
        if (!$("#{$this->getId()}").val()) {
            $("#{$this->getId()}").data("_internalValue", "");
        }
    });
    
    {$requiredScript}
JS;
        
        return $output;
    }

    public function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/js/bootstrap-datepicker.js"></script>';
        if ($locale = $this->getBootstrapDatepickerLocale()) {
            $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/locales/bootstrap-datepicker.' . $locale . '.min.js"></script>';
        }
        $headers[] = '<link rel="stylesheet" href="exface/vendor/bower-asset/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css">';
        
        $formatter = $this->getDataTypeFormatter();
        $headers = array_merge($headers, $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
        return $headers;
    }

    /**
     * Generates the Bootstrap Datepicker Locale-name based on the Locale provided by
     * the translator.
     *
     * @return string
     */
    protected function getBootstrapDatepickerLocale()
    {
        if (is_null($this->bootstrapDatepickerLocale)) {
            $datepickerBasepath = $this->getWorkbench()->filemanager()->getPathToVendorFolder() . DIRECTORY_SEPARATOR . 'bower-asset' . DIRECTORY_SEPARATOR . 'bootstrap-datepicker' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR;
            
            $fullLocale = $this->getFacade()->getApp()->getTranslator()->getLocale();
            $locale = str_replace("_", "-", $fullLocale);
            if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                return ($this->bootstrapDatepickerLocale = $locale);
            }
            $locale = substr($fullLocale, 0, strpos($fullLocale, '_'));
            if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                return ($this->bootstrapDatepickerLocale = $locale);
            }
            
            $fallbackLocales = $this->getFacade()->getApp()->getTranslator()->getFallbackLocales();
            foreach ($fallbackLocales as $fallbackLocale) {
                $locale = str_replace("_", "-", $fallbackLocale);
                if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                    return ($this->bootstrapDatepickerLocale = $locale);
                }
                $locale = substr($fallbackLocale, 0, strpos($fallbackLocale, '_'));
                if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                    return ($this->bootstrapDatepickerLocale = $locale);
                }
            }
            
            $this->bootstrapDatepickerLocale = '';
        }
        return $this->bootstrapDatepickerLocale;
    }

    public function buildJsValueGetter()
    {
        return '($("#' . $this->getId() . '").data("_internalValue") !== undefined ? $("#' . $this->getId() . '").data("_internalValue") : $("#' . $this->getId() . '").val())';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteInput::buildJsRequired()
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
    
    // Bei leeren Werten, wird die toValue-Funktion und damit der Validator nicht aufgerufen.
    // Ueberprueft die Validitaet wenn das Element erzeugt wird.
    if (!$("#{$this->getId()}").val()) {
        $("#{$this->getId()}").data("_isValid", false);
        {$this->buildJsFunctionPrefix()}validate();
    }
    // Ueberprueft die Validitaet wenn das Element geaendert wird.
    $("#{$this->getId()}").on("input change", function() {
        if (!$("#{$this->getId()}").val()) {
            $("#{$this->getId()}").data("_isValid", false);
            {$this->buildJsFunctionPrefix()}validate();
        }
    });
JS;
        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteInput::buildJsValidator()
     */
    function buildJsValidator()
    {
        if ($this->isValidationRequired() === true && $this->getWidget()->isRequired()) {
            $output = '$("#' . $this->getId() . '").data("_isValid")';
        } else {
            $output = 'true';
        }
        
        return $output;
    }
}