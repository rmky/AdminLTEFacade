<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryInputDateTrait;

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
class lteInputDate extends lteInput
{
    
    use JqueryInputDateTrait;

    private $bootstrapDatepickerLocale;

    protected function init()
    {
        parent::init();
        $this->setElementType('datepicker');
    }

    function generateHtml()
    {
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true" ' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML

                <label for="{$this->getId()}">{$this->getWidget()->getCaption()}</label>
                <div class="form-group input-group date">
                    <input class="form-control"
                        type="text"
                        name="{$this->getWidget()->getAttributeAlias()}"
                        id="{$this->getId()}"
                        {$requiredScript}
                        {$disabledScript} />
                    <div class="input-group-addon" onclick="$('#{$this->getId()}').{$this->getElementType()}('show');">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>

HTML;
        return $this->buildHtmlWrapper($output);
    }

    function generateJs()
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
            toDisplay: {$this->buildJsFunctionPrefix()}dateFormatter,
            toValue: function(date, format, language) {
                var output = {$this->buildJsFunctionPrefix()}dateParser(date);
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
    
    {$this->buildJsDateParser()}
    {$this->buildJsDateFormatter()}
    
    {$requiredScript}
JS;
        
        return $output;
    }

    public function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/js/bootstrap-datepicker.js"></script>';
        if ($locale = $this->getBootstrapDatepickerLocale()) {
            $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/locales/bootstrap-datepicker.' . $locale . '.min.js"></script>';
        }
        $headers[] = '<link rel="stylesheet" href="exface/vendor/bower-asset/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css">';
        $headers[] = '<script type="text/javascript" src="exface/vendor/npm-asset/datejs/build/production/' . $this->buildDateJsLocaleFilename() . '"></script>';
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
            
            $fullLocale = $this->getTemplate()->getApp()->getTranslator()->getLocale();
            $locale = str_replace("_", "-", $fullLocale);
            if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                return ($this->bootstrapDatepickerLocale = $locale);
            }
            $locale = substr($fullLocale, 0, strpos($fullLocale, '_'));
            if (file_exists($datepickerBasepath . 'bootstrap-datepicker.' . $locale . '.min.js')) {
                return ($this->bootstrapDatepickerLocale = $locale);
            }
            
            $fallbackLocales = $this->getTemplate()->getApp()->getTranslator()->getFallbackLocales();
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
        return '$("#' . $this->getId() . '").data("_internalValue")';
    }

    protected function buildJsDateFormatter()
    {
        // Der Hauptunterschied dieser Methode im Vergleich zum JEasyUi-Template ist,
        // dass der Bootstrap Datepicker das Datum in der UTC-Zeitzone zurueckgibt.
        // Daher date.clone().addMinutes(date.getTimezoneOffset()).
        // geht auch: date.clone().setTimezoneOffset(2*Number(date.getUTCOffset()))
        
        // Das Format in dateFormatScreen muss mit dem DateParser kompatibel sein. Das
        // amerikanische Format MM/dd/yyyy wird vom Parser als dd/MM/yyyy interpretiert
        // und kann deshalb nicht verwendet werden. Loesung waere den Parser anzupassen.
        
        // Auch moeglich: Verwendung des DateJs-Formatters:
        // "d" entspricht CultureInfo shortDate Format Pattern, hierfuer muss das
        // entsprechende locale DateJs eingebunden werden und ein kompatibler Parser ver-
        // wendet werden
        // return date.toString("d");
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}dateFormatter(date, format, language) {
        // date ist ein date-Objekt und wird zu einem String geparst
        return date.clone().addMinutes(date.getTimezoneOffset()).toString("{$this->buildJsDateFormatScreen()}");
    }
JS;
        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::buildJsRequired()
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
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::buildJsValidator()
     */
    function buildJsValidator()
    {
        $widget = $this->getWidget();
        
        $must_be_validated = $widget->isRequired() && ! ($widget->isHidden() || $widget->isReadonly() || $widget->isDisabled() || $widget->isDisplayOnly());
        if ($must_be_validated) {
            $output = '$("#' . $this->getId() . '").data("_isValid")';
        } else {
            $output = 'true';
        }
        
        return $output;
    }
}