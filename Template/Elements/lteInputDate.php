<?php
namespace exface\AdminLteTemplate\Template\Elements;

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

    private $dateFormatScreen;

    private $dateFormatInternal;

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
                    <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <input class="form-control pull-right"
                        type="text"
                        name="{$this->getWidget()->getAttributeAlias()}"
                        id="{$this->getId()}"
                        {$requiredScript}
                        {$disabledScript} />
                </div>
                        
HTML;
        return $this->buildHtmlWrapper($output);
    }

    function generateJs()
    {
        $requiredScript = $this->getWidget()->isRequired() ? $this->buildJsRequired() : '';
        
        $output = <<<JS

    $("#{$this->getId()}").{$this->getElementType()}({
        // Bleibt geoeffnet wenn ein Datum selektiert wird. Gibt sonst Probleme, wenn
        // eine Datumseingabe per Enter abgeschlossen wird und anschliessend eine neue
        // Datumseingabe erfolgt.
        autoclose: false,
        format: {
            toDisplay: {$this->getId()}_dateFormatter,
            toValue: {$this->getId()}_dateParser
        },
        // Markiert das heutige Datum.
        todayHighlight: true
    });
    
    // Wird der uebergebene Wert per value="..." im HTML uebergeben, erscheint er
    // unformatiert (z.B. "-1d"). Wird der Wert hier gesetzt, wird er formatiert.
    $("#{$this->getId()}").{$this->getElementType()}("update", "{$this->escapeString($this->getValueWithDefaults())}");
    
    {$this->buildJsDateParser()}
    {$this->buildJsDateFormatter()}
    
    {$requiredScript}
JS;
        
        return $output;
    }

    public function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>';
        if ($localeFilename = $this->getBootstrapDatepickerFileName()) {
            $headers[] = '<script type="text/javascript" src="exface/vendor/bower-asset/bootstrap-datepicker/dist/locales/' . $localeFilename . '"></script>';
        }
        $headers[] = '<link rel="stylesheet" href="exface/vendor/bower-asset/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css">';
        $headers[] = '<script type="text/javascript" src="exface/vendor/npm-asset/datejs/build/production/' . $this->getDateJsFileName() . '"></script>';
        return $headers;
    }

    /**
     * Generates the Bootstrap Datepicker filename based on the locale provided by the
     * translator.
     *
     * @return string
     */
    protected function getBootstrapDatepickerFileName()
    {
        $datepickerBasepath = MODX_BASE_PATH . 'exface' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bower-asset' . DIRECTORY_SEPARATOR . 'bootstrap-datepicker' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR;
        
        $locale = $this->getTemplate()->getApp()->getTranslator()->getLocale();
        $filename = 'bootstrap-datepicker.' . str_replace("_", "-", $locale) . '.min.js';
        if (file_exists($datepickerBasepath . $filename)) {
            return $filename;
        }
        $filename = 'bootstrap-datepicker.' . substr($locale, 0, strpos($locale, '_')) . '.min.js';
        if (file_exists($datepickerBasepath . $filename)) {
            return $filename;
        }
        
        $fallbackLocales = $this->getTemplate()->getApp()->getTranslator()->getFallbackLocales();
        foreach ($fallbackLocales as $fallbackLocale) {
            $filename = 'bootstrap-datepicker.' . str_replace("_", "-", $fallbackLocale) . '.min.js';
            if (file_exists($datepickerBasepath . $filename)) {
                return $filename;
            }
            $filename = 'bootstrap-datepicker.' . substr($fallbackLocale, 0, strpos($fallbackLocale, '_')) . '.min.js';
            if (file_exists($datepickerBasepath . $filename)) {
                return $filename;
            }
        }
        
        return null;
    }

    /**
     * Generates the DateJs filename based on the locale provided by the translator.
     *
     * @return string
     */
    protected function getDateJsFileName()
    {
        $dateJsBasepath = MODX_BASE_PATH . 'exface' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'npm-asset' . DIRECTORY_SEPARATOR . 'datejs' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR;
        
        $locale = $this->getTemplate()->getApp()->getTranslator()->getLocale();
        $filename = 'date-' . str_replace("_", "-", $locale) . '.min.js';
        if (file_exists($dateJsBasepath . $filename)) {
            return $filename;
        }
        
        $fallbackLocales = $this->getTemplate()->getApp()->getTranslator()->getFallbackLocales();
        foreach ($fallbackLocales as $fallbackLocale) {
            $filename = 'date-' . str_replace("_", "-", $fallbackLocale) . '.min.js';
            if (file_exists($dateJsBasepath . $filename)) {
                return $filename;
            }
        }
        
        return 'date.min.js';
    }

    public function buildJsValueGetter()
    {
        return '$("#' . $this->getId() . '").data("_internalValue")';
    }

    protected function buildJsScreenDateFormat()
    {
        if (is_null($this->dateFormatScreen)) {
            $this->dateFormatScreen = $this->translate("DATE.FORMAT.SCREEN");
        }
        return $this->dateFormatScreen;
    }

    protected function buildJsInternalDateFormat()
    {
        if (is_null($this->dateFormatInternal)) {
            $this->dateFormatInternal = $this->translate("DATE.FORMAT.INTERNAL");
        }
        return $this->dateFormatInternal;
    }

    protected function buildJsDateParser()
    {
        // Der Hauptunterschied dieser Methode im Vergleich zum JEasyUi-Template ist,
        // dass der Bootstrap Datepicker das zurueckgegebene Datum in der UTC-Zeitzone
        // erwartet. Daher output.setTimezoneOffset(0).
        
        // setTimezoneOffset nimmt als Argument 1/100 h. Befindet man sich gerade in der
        // Zeitzone +0200 (MESZ) und uebergibt 200, wird die Zeit nicht veraendert.
        // Uebergibt man 0 werden 2 h abgezogen, uebergibt man 400 werden 2 h addiert.
        
        $output = <<<JS

    function {$this->getId()}_dateParser(date, format, language) {
        // date ist ein String und wird zu einem date-Objekt geparst
        
        // date wird entsprechend CultureInfo geparst, hierfuer muss das entsprechende locale
        // DateJs eingebunden werden und ein kompatibler Formatter verwendet werden
        //return Date.parse(date);
        
        // Variablen initialisieren
        var {$this->getId()}_jquery = $("#{$this->getId()}");
        var match = null;
        var dateParsed = false;
        
        // dd.MM.yyyy, dd-MM-yyyy, dd/MM/yyyy, d.M.yyyy, d-M-yyyy, d/M/yyyy
        if (!dateParsed && (match = /(\d{1,2})[.\-/](\d{1,2})[.\-/](\d{4})/.exec(date)) != null) {
            var yyyy = Number(match[3]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // yyyy.MM.dd, yyyy-MM-dd, yyyy/MM/dd, yyyy.M.d, yyyy-M-d, yyyy/M/d
        if (!dateParsed && (match = /(\d{4})[.\-/](\d{1,2})[.\-/](\d{1,2})/.exec(date)) != null) {
            var yyyy = Number(match[1]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[3]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // dd.MM.yy, dd-MM-yy, dd/MM/yy, d.M.yy, d-M-yy, d/M/yy
        if (!dateParsed && (match = /(\d{1,2})[.\-/](\d{1,2})[.\-/](\d{2})/.exec(date)) != null) {
            var yyyy = 2000 + Number(match[3]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // yy.MM.dd, yy-MM-dd, yy/MM/dd, yy.M.d, yy-M-d, yy/M/d
        if (!dateParsed && (match = /(\d{2})[.\-/](\d{1,2})[.\-/](\d{1,2})/.exec(date)) != null) {
            var yyyy = 2000 + Number(match[1]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[3]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // dd.MM, dd-MM, dd/MM, d.M, d-M, d/M
        if (!dateParsed && (match = /(\d{1,2})[.\-/](\d{1,2})/.exec(date)) != null) {
            var yyyy = (new Date()).getFullYear();
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // ddMMyyyy
        if (!dateParsed && (match = /^(\d{2})(\d{2})(\d{4})$/.exec(date)) != null) {
            var yyyy = Number(match[3]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // ddMMyy
        if (!dateParsed && (match = /^(\d{2})(\d{2})(\d{2})$/.exec(date)) != null) {
            var yyyy = 2000 + Number(match[3]);
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // ddMM
        if (!dateParsed && (match = /^(\d{2})(\d{2})$/.exec(date)) != null) {
            var yyyy = (new Date()).getFullYear();
            var MM = Number(match[2]) - 1;
            var dd = Number(match[1]);
            dateParsed = Date.validateYear(yyyy) && Date.validateMonth(MM) && Date.validateDay(dd, yyyy, MM);
        }
        // Ausgabe des geparsten Wertes
        if (dateParsed) {
            var output = new Date(yyyy, MM, dd);
            {$this->getId()}_jquery.data("_internalValue", output.toString("{$this->buildJsInternalDateFormat()}"));
            return output.setTimezoneOffset(0);
        }
        
        // +/- ... T/D/W/M/J/Y
        if (!dateParsed && (match = /^([+\-]?\d+)([TtDdWwMmJjYy])$/.exec(date)) != null) {
            var output = Date.today();
            switch (match[2].toUpperCase()) {
                case "T":
                case "D":
                    output.addDays(Number(match[1]));
                    break;
                case "W":
                    output.addWeeks(Number(match[1]));
                    break;
                case "M":
                    output.addMonths(Number(match[1]));
                    break;
                case "J":
                case "Y":
                    output.addYears(Number(match[1]));
            }
            dateParsed = true;
        }
        // TODAY, HEUTE, NOW, JETZT, YESTERDAY, GESTERN, TOMORROW, MORGEN
        if (!dateParsed) {
            switch (date.toUpperCase()) {
                case "TODAY":
                case "HEUTE":
                case "NOW":
                case "JETZT":
                    var output = Date.today();
                    dateParsed = true;
                    break;
                case "YESTERDAY":
                case "GESTERN":
                    var output = Date.today().addDays(-1);
                    dateParsed = true;
                    break;
                case "TOMORROW":
                case "MORGEN":
                    var output = Date.today().addDays(1);
                    dateParsed = true;
            }
        }
        // Ausgabe des geparsten Wertes
        if (dateParsed) {
            {$this->getId()}_jquery.data("_internalValue", output.toString("{$this->buildJsInternalDateFormat()}"));
            return output.setTimezoneOffset(0);
        } else {
            {$this->getId()}_jquery.data("_internalValue", "");
            return null;
        }
    }
JS;
        
        return $output;
    }

    protected function buildJsDateFormatter()
    {
        // Der Hauptunterschied dieser Methode im Vergleich zum JEasyUi-Template ist,
        // dass der Bootstrap Datepicker das Datum in der UTC-Zeitzone zurueckgibt.
        // Daher date.clone().addMinutes(date.getTimezoneOffset()).
        
        $output = <<<JS

    function {$this->getId()}_dateFormatter(date, format, language) {
        // date ist ein date-Objekt und wird zu einem String geparst
        
        // "d" entspricht CultureInfo shortDate Format Pattern, hierfuer muss das
        // entpsprechende locale DateJs eingebunden werden und ein kompatibler Parser ver-
        // wendet werden
        //return date.toString("d");
        
        // Das Format in dateFormatScreen muss mit dem DateParser kompatibel sein. Das
        // amerikanische Format MM/dd/yyyy wird vom Parser als dd/MM/yyyy interpretiert und
        // kann deshalb nicht verwendet werden. Loesung waere den Parser anzupassen.
        
        // geht auch: date.clone().setTimezoneOffset(2*Number(date.getUTCOffset()))
        return date.clone().addMinutes(date.getTimezoneOffset()).toString("{$this->buildJsScreenDateFormat()}");
    }
JS;
        
        return $output;
    }
}