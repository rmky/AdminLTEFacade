<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Exceptions\InvalidArgumentException;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\DataTypes\StringDataType;

/**
 * 
 *
 * @author Andrej Kabachnik
 */
class lteComboTable extends lteInput
{

    private $js_debug_level = 0;

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('combogrid');
        $this->setJsDebugLevel($this->getTemplate()->getConfig()->getOption("JAVASCRIPT_DEBUG_LEVEL"));
        
        // Register onChange-Handler for Filters with Live-Reference-Values
        $widget = $this->getWidget();
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                if ($link = $fltr->getValueWidgetLink()) {
                    $linked_element = $this->getTemplate()->getElement($link->getTargetWidget());
                    
                    $widget_lazy_loading_group_id = $widget->getLazyLoadingGroupId();
                    $linked_element_lazy_loading_group_id = method_exists($linked_element->getWidget(), 'getLazyLoadingGroupId') ? $linked_element->getWidget()->getLazyLoadingGroupId() : '';
                    // Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Filterreferenzen
                    // zu Widgets außerhalb dieser Gruppe haben.
                    if ($widget_lazy_loading_group_id && ($linked_element_lazy_loading_group_id != $widget_lazy_loading_group_id)) {
                        throw new WidgetConfigurationError($widget, 'Widget "' . $widget->getId() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a filter-reference to widget "' . $linked_element->getWidget()->getId() . '" in lazy-loading-group "' . $linked_element_lazy_loading_group_id . '". Filter-references to widgets outside the own lazy-loading-group are not allowed.', '6V6C2HY');
                    }
                    
                    $on_change_script = <<<JS

                        if (typeof suppressFilterSetterUpdate == "undefined" || !suppressFilterSetterUpdate) {
                            if (typeof clearFilterSetterUpdate == "undefined" || !clearFilterSetterUpdate) {
                                {$this->getId()}_jquery.data("_filterSetterUpdate", true);
                            } else {
                                {$this->getId()}_jquery.data("_clearFilterSetterUpdate", true);
                            }
                            {$this->getId()}_ms.setData("{$this->getAjaxUrl()}");
                        }
JS;
                    
                    if ($widget_lazy_loading_group_id) {
                        $on_change_script = <<<JS

                    if (typeof suppressLazyLoadingGroupUpdate == "undefined" || !suppressLazyLoadingGroupUpdate) {
                        {$on_change_script}
                    }
JS;
                    }
                    
                    $linked_element->addOnChangeScript($on_change_script);
                }
            }
        }
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
    }

    /**
     *
     * @throws WidgetConfigurationError
     * @return \exface\AdminLteTemplate\Templates\Elements\lteComboTable
     */
    protected function registerLiveReferenceAtLinkedElement()
    {
        $widget = $this->getWidget();
        
        if ($linked_element = $this->getLinkedTemplateElement()) {
            // Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Value-
            // Referenzen haben.
            $widget_lazy_loading_group_id = $widget->getLazyLoadingGroupId();
            if ($widget_lazy_loading_group_id) {
                throw new WidgetConfigurationError($widget, 'Widget "' . $widget->getId() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a value-reference to widget "' . $linked_element->getWidget()->getId() . '". Value-references to other widgets are not allowed.', '6V6C3AP');
            }
            
            $linked_element->addOnChangeScript($this->buildJsLiveReference());
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::buildHtml()
     */
    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\ComboTable */
        $widget = $this->getWidget();
        
        $value = $this->escapeString($this->getValueWithDefaults());
        $valueSkript = $value ? 'value=\'["' . $value . '"]\'' : '';
        
        $output = <<<HTML

						<label for="{$this->getId()}">{$this->getWidget()->getCaption()}</label>
						<input type="hidden"
								id="{$this->getId()}" 
								name="{$widget->getAttributeAlias()}"
								value="{$value}" />
						<input class="form-control"
								id="{$this->getId()}_ms"
								{$valueSkript} />
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::buildJs()
     */
    function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\ComboTable */
        $widget = $this->getWidget();
        
        // Initialer Wert
        $initialValueScriptBeforeMsInit = '';
        $initialValueScriptAfterMsInit = '';
        $initialFilterScript = '';
        if (! is_null($this->getValueWithDefaults()) && $this->getValueWithDefaults() !== '') {
            if (trim($widget->getValueText())) {
                // If the text is already known, set it and prevent initial backend request
                $widget_value_text = preg_replace('/\r|\n/', '', str_replace('"', '\"', trim($widget->getValueText())));
                $initialValueScriptBeforeMsInit = $this->getId() . '_jquery.data("_suppressXhr", true);';
                $initialValueScriptAfterMsInit = $this->getId() . '_ms.setSelection([{"' . $widget->getTextColumn()->getDataColumnName() . '": "' . $widget_value_text . '", "' . $widget->getValueColumn()->getDataColumnName() . '": "' . $this->getValueWithDefaults() . '"}]);';
            } else {
                $initialValueScriptBeforeMsInit = $this->getId() . '_jquery.data("_valueSetterUpdate", true);';
                $initialFilterScript = ', filter_' . $widget->getValueColumn()->getDataColumnName() . ': "' . $this->getValueWithDefaults() . '"';
            }
        } else {
            // If no value set, just supress initial autoload
            $initialValueScriptBeforeMsInit = $this->getId() . '_jquery.data("_suppressXhr", true);';
        }
        
        // Andere Optionen
        $options = [];
        if (! $widget->getMultiSelect()) {
            $options[] = 'maxSelection: 1';
            $options[] = 'maxSelectionRenderer: function() { return "' . $this->translate('WIDGET.COMBOTABLE.MAX_SELECTION', [
                '%number%' => 1
            ], 1) . '"; }';
        }
        if ($widget->isDisabled()) {
            $options[] = 'disabled: true';
        }
        if ($widget->isRequired()) {
            $options[] = 'required: true';
        }
        $other_options = implode(",\n    ", $options);
        $other_options = $other_options ? ', ' . $other_options : '';
        
        // Debug-Funktionen
        $debug_function = ($this->getJsDebugLevel() > 0) ? $this->buildJsDebugDataToStringFunction() : '';
        
        // Das entspricht dem urspruenglichen Verhalten. Filter-Referenzen werden beim Loeschen eines
        // Elements nicht geleert, sondern nur aktualisiert.
        $filterSetterUpdateScript = $widget->getLazyLoadingGroupId() ? '
        // Der eigene Wert wird geloescht.
        ' . $this->getId() . '_jquery.data("_clearFilterSetterUpdate", true);
        // Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
        ' . $this->getId() . '_jquery.data("_otherClearFilterSetterUpdate", true);' : '
        // Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
        // nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
        ' . $this->getId() . '_jquery.data("_suppressFilterSetterUpdate", true);';
        
        // Wird nur wenn das Widget in einer lazy-loading-group ist, um die Gruppe
        // in einem konsistenten Zustand zu halten. Wuerde aber Probleme geben wenn
        // das Widget multi_select ist, da dann nach der ersten Auswahl keine
        // weiteren Optionen angezeigt werden.
        $reloadOnSelectSkript = $widget->getLazyLoadingGroupId() ? '
        // Update des eigenen Widgets.
        ' . $this->getId() . '_jquery.data("_filterSetterUpdate", true);
        ' . $this->getId() . '_ms.setData("' . $this->getAjaxUrl() . '");' : '';
        
        $output = <<<JS

// Globale Variablen initialisieren.
{$this->buildJsInitGlobalsFunction()}
{$this->buildJsFunctionPrefix()}initGlobals();

// Debug-Funktionen hinzufuegen.
{$debug_function}

{$initialValueScriptBeforeMsInit}

window.{$this->getId()}_ms = $("#{$this->getId()}_ms").magicSuggest({
    allowFreeEntries: false,
    beforeSend: function(xhr, settings) {
        {$this->buildJsDebugMessage('beforeSend')}
        // Das Laden wird verhindert wenn _suppressXhr gesetzt ist.
        if ({$this->getId()}_jquery.data("_suppressXhr")) {
            {$this->getId()}_jquery.removeData("_suppressXhr");
            return false;
        }
    },
    data: "{$this->getAjaxUrl()}",
    dataUrlParams: {
        resource: "{$widget->getPage()->getAliasWithNamespace()}",
        element: "{$widget->getTable()->getId()}",
        object: "{$widget->getTable()->getMetaObject()->getId()}",
        action: "{$widget->getLazyLoadingActionAlias()}",
        length: {$widget->getMaxSuggestions()},
        start: 0
        {$initialFilterScript}
    },
    displayField: "{$widget->getTextColumn()->getDataColumnName()}",
    noSuggestionText: "{$this->translate('WIDGET.COMBOTABLE.NO_SUGGESTION')}",
    placeholder: "{$this->translate('WIDGET.COMBOTABLE.PLACEHOLDER')}",
    queryParam: "q",
    resultAsString: true, // Das Resultat wird als String, nicht als Tag, dargestellt.
    resultsField: "data",
    // Ist toggleOnClick true wird die ComboTable expandiert, egal wohin man klickt. Das gibt
    // allerdings Probleme beim Loeschen von Lazy-Loading-Groups. Man klickt ins Feld um zu
    // Loeschen, expandiert dadurch aber die ComboTable und loest eine Abfrage aus. Loescht
    // man jetzt schnell das Feld, kommt die Anfrage spaeter zurueck, mit dem vorherigen
    // Inhalt als einzigem Ergebnis, wodurch dieses wieder automatisch ausgewaehlt wird.
    toggleOnClick: false,
    valueField: "{$widget->getValueColumn()->getDataColumnName()}"
    {$other_options}
});

{$this->buildJsFunctionPrefix()}initGlobals();

{$initialValueScriptAfterMsInit}

$({$this->getId()}_ms).on("selectionchange", function(e,m){
    {$this->buildJsDebugMessage('onSelectionChange')}
    {$this->getId()}_jquery.val({$this->getId()}_ms.getValue().join()).trigger("change");
    
    if (!{$this->buildJsFunctionPrefix()}valueGetter()) {
        // Wird ausgefuehrt wenn der Wert geloescht wurde.
        {$filterSetterUpdateScript}
    }
    
    {$this->buildJsFunctionPrefix()}onChange();
    
    if ({$this->getId()}_jquery.data("_suppressReloadOnSelect")) {
        // Verhindert das neu Laden onSelect, siehe onLoadSuccess (autoselectsinglesuggestion)
        {$this->getId()}_jquery.removeData("_suppressReloadOnSelect");
    } else {
        {$reloadOnSelectSkript}
    }
});

$({$this->getId()}_ms).on("beforeload", function(e,m){
    {$this->buildJsOnBeforeload()}
});

$({$this->getId()}_ms).on("load", function(e,m){
    {$this->buildJsOnLoad()}
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
JS;
        
        // Es werden JavaScript Value-Getter-/Setter- und OnChange-Funktionen fuer die ComboTable erzeugt,
        // um duplizierten Code zu vermeiden.
        $output .= <<<JS

{$this->buildJsValueGetterFunction()}
{$this->buildJsValueSetterFunction()}
{$this->buildJsOnChangeFunction()}
JS;
        
        // Es werden Dummy-Methoden fuer die Filter der DataTable hinter dieser ComboTable generiert. Diese
        // Funktionen werden nicht benoetigt, werden aber trotzdem vom verlinkten Element aufgerufen, da
        // dieses nicht entscheiden kann, ob das Filter-Input-Widget existiert oder nicht. Fuer diese Filter
        // existiert kein Input-Widget, daher existiert fuer sie weder HTML- noch JavaScript-Code und es
        // kommt sonst bei einem Aufruf der Funktion zu einem Fehler.
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                $output .= <<<JS

function {$this->getTemplate()->getElement($fltr->getInputWidget())->buildJsFunctionPrefix()}valueSetter(value){}
JS;
            }
        }
        
        // Ein Skript hinzufuegen, dass den required-Status des Widgets behandelt
        $output .= $widget->isRequired() ? $this->buildJsRequired() : '';
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
        $headers[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
        return $headers;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    function buildJsValueGetter($column = null, $row = null)
    {
        if (is_null($column) || $column === '') {
            $column = $this->getWidget()->getTable()->getUidColumn()->getDataColumnName();
        }
        
        $params = $column ? '"' . $column . '"' : '';
        $params = $row ? ($params ? $params . ', ' . $row : $row) : $params;
        return $this->buildJsFunctionPrefix() . 'valueGetter(' . $params . ')';
    }

    /**
     * Erzeugung einer JavaScript-Funktion zum Auslesen des Wertes.
     * Die zurueckgegebenen Werte sind per MagicSuggest valueField definiert. Sind
     * mehrere Werte ausgewaehlt wird eine Komma-separierte Liste dieser Werte
     * zurueckgegeben. Ist eine spezifische Spalte ausgewaehlt, wird statt dem valueField
     * der Wert dieser Spalte zurueckgegeben. Ist MagicSuggest noch nicht erzeugt wird
     * stattdessen der Wert aus dem verknuepften InputHidden zurueckgegeben.
     *
     * @return string
     */
    function buildJsValueGetterFunction()
    {
        $widget = $this->getWidget();
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        
        if ($widget->getMultiSelect()) {
            $valueGetter = <<<JS

                var resultArray = [];
                for (i = 0; i < selectedRows.length; i++) {
                    if (selectedRows[i][column] == undefined || selectedRows[i][column] === false) {
                        if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                        resultArray.push("");
                    } else {
                        resultArray.push(selectedRows[i][column]);
                    }
                }
                return resultArray.join();
JS;
        } else {
            $valueGetter = <<<JS

                // Wird die Spalte vom Server angefordert, das Attribut des Objekts existiert
                // aber nicht, wird false zurueckgegeben (Booleans werden als "0"/"1" zurueckgegeben).
                if (selectedRows[0][column] == undefined || selectedRows[0][column] === false) {
                    if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                    return "";
                } else {
                    return selectedRows[0][column];
                }
JS;
        }
        
        $output = <<<JS

function {$this->buildJsFunctionPrefix()}valueGetter(column, row){
    // Der value-Getter wird in manchen Faellen aufgerufen, bevor die globalen
    // Variablen definiert sind. Daher hier noch einmal initialisieren.
    {$this->buildJsFunctionPrefix()}initGlobals();
    
    {$this->buildJsDebugMessage('valueGetter()')}
    
    // Wird kein Spaltenname uebergeben, wird die UID-Spalte zurueckgegeben.
    if (!column) {
        column = "{$uidColumnName}";
    }
    
    if ({$this->getId()}_ms_jquery.data("magicSuggest")) {
        var selectedRows = {$this->getId()}_ms.getSelection();
        if (selectedRows.length > 0) {
            {$valueGetter}
        } else {
            return $('#{$this->getId()}').val();
        }
    } else {
        if (column == "{$uidColumnName}") {
            return {$this->getId()}_jquery.val();
        } else {
            return "";
        }
    }
}
JS;
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::buildJsValueSetter()
     */
    function buildJsValueSetter($value)
    {
        return $this->buildJsFunctionPrefix() . 'valueSetter(' . $value . ')';
    }

    /**
     * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes.
     * Ist multiselect false wird der Wert nur gesetzt wenn genau ein Wert uebergeben
     * wird. Anschliessend wird der Inhalt des MagicSuggest neu geladen (um ordentliche
     * Label anzuzeigen falls auch ein entsprechender Filter gesetzt ist). Ist
     * MagicSuggest noch nicht erzeugt wird stattdessen der Wert im verknuepften
     * InputHidden gesetzt.
     *
     * @return string
     */
    function buildJsValueSetterFunction()
    {
        $widget = $this->getWidget();
        
        $valueSetter = <<<JS

                {$this->getId()}_ms.clear(true);
                // Bei setValue() wird selectionChange getriggert. Es soll aber weder
                // neu geladen werden, noch sollen andere Widgets geupdated werden.
                {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
                {$this->getId()}_jquery.data("_otherSuppressAllUpdates", true);
                {$this->getId()}_ms.setValue(valueArray);
                // Wird ein leerer Wert gesetzt wird selectionChange nicht getriggert.
                // Daher die Werte hier wieder entfernen.
                {$this->getId()}_jquery.removeData("_suppressReloadOnSelect");
                {$this->getId()}_jquery.removeData("_otherSuppressAllUpdates");
                {$this->getId()}_jquery.val(value).trigger("change");
JS;
        if (! $this->getWidget()->getMultiSelect()) {
            $valueSetter = <<<JS

            if (valueArray.length <= 1) {
                {$valueSetter}
            }
JS;
        }
        
        $output = <<<JS

function {$this->buildJsFunctionPrefix()}valueSetter(value){
    {$this->buildJsDebugMessage('valueSetter()')}
    var valueArray;
    if ({$this->getId()}_ms_jquery.data("magicSuggest")) {
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
        
        if  (!{$this->getId()}_ms.getValue().equals(valueArray)) {
            {$valueSetter}
            
            {$this->getId()}_jquery.data("_valueSetterUpdate", true);
            {$this->getId()}_ms.setData("{$this->getAjaxUrl()}");
        }
    } else {
        {$this->getId()}_jquery.val(value).trigger("change");
    }
}
JS;
        
        return $output;
    }

    /**
     * Creates a JavaScript function which sets the value of the element.
     *
     * @return string
     */
    function buildJsOnChangeFunction()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

function {$this->buildJsFunctionPrefix()}onChange(){
    {$this->buildJsDebugMessage('onChange()')}
    // Diese Werte koennen gesetzt werden damit, wenn der Wert der ComboTable
    // geaendert wird, nur ein Teil oder gar keine verlinkten Elemente aktualisiert
    // werden.
    var suppressFilterSetterUpdate = false, clearFilterSetterUpdate = false, suppressAllUpdates = false, suppressLazyLoadingGroupUpdate = false;
    if ({$this->getId()}_jquery.data("_otherSuppressFilterSetterUpdate")){
        // Es werden keine Filter-Links aktualisiert.
        {$this->getId()}_jquery.removeData("_otherSuppressFilterSetterUpdate");
        suppressFilterSetterUpdate = true;
    }
    if ({$this->getId()}_jquery.data("_otherClearFilterSetterUpdate")){
        // Filter-Links werden geleert.
        {$this->getId()}_jquery.removeData("_otherClearFilterSetterUpdate");
        clearFilterSetterUpdate = true;
    }
    if ({$this->getId()}_jquery.data("_otherSuppressAllUpdates")){
        // Weder Werte-Links noch Filter-Links werden aktualisiert.
        {$this->getId()}_jquery.removeData("_otherSuppressAllUpdates");
        suppressAllUpdates = true;
    }
    if ({$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate")){
        // Die LazyLoadingGroup wird nicht aktualisiert.
        {$this->getId()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
        suppressLazyLoadingGroupUpdate = true;
    }
    
    if (!suppressAllUpdates) {
        {$this->getOnChangeScript()}
    }
}
JS;
        
        return $output;
    }

    /**
     * Erzeugt den JavaScript-Code welcher vor dem Laden des MagicSuggest-Inhalts
     * ausgefuehrt wird.
     * Wurde programmatisch ein Wert gesetzt, wird als Filter nur dieser Wert
     * hinzugefuegt, um das Label ordentlich anzuzeigen. Sonst werden die am Widget
     * definierten Filter gesetzt. Die Filter werden nach dem Laden wieder entfernt, da
     * sich die Werte durch Live-Referenzen aendern koennen.
     *
     * @return string
     */
    function buildJsOnBeforeload()
    {
        $widget = $this->getWidget();
        
        // Run the data getter of the (unrendered) DataConfigurator widget to get the data
        // parameter with filters, sorters, etc.
        $dataParam = 'dataUrlParams.data = ' . $this->getTemplate()->getElement($widget->getTable()->getConfiguratorWidget())->buildJsDataGetter(null, true);
        // Beim Leeren eines Widgets in einer in einer lazy-loading-group wird kein Filter gesetzt,
        // denn alle Filter sollten leer sein (alle Elemente der Gruppe leer). Beim Leeren eines
        // Widgets ohne Gruppe werden die normalen Filter gesetzt.
        $clearFiltersParam = $widget->getLazyLoadingGroupId() ? '' : $dataParam;
        // Filter aus dem gesetzten Wert erzeugen.
        $valueFilterParam = 'dataUrlParams.filter_' . $widget->getValueColumn()->getDataColumnName() . ' = ' . $this->getId() . '_ms.getValue().join();';
        
        $output = <<<JS

    // Wird vor dem ersten Laden nicht aufgerufen!
    
    {$this->buildJsDebugMessage('onBeforeLoad')}
    
    var dataUrlParams = {$this->getId()}_ms.getDataUrlParams();
    // Nach dem Loeschen einer Eingabe ist q in dataUrlParams immer noch gesetzt.
    // Deshalb hier loeschen.
    delete dataUrlParams.q;
    
    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
        dataUrlParams._valueSetterUpdate = true;
        {$valueFilterParam}
    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
        dataUrlParams._clearFilterSetterUpdate = true;
        {$clearFiltersParam}
    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
        dataUrlParams._filterSetterUpdate = true;
        {$dataParam}
        {$valueFilterParam}
    } else {
        dataUrlParams.q = {$this->getId()}_ms.getRawValue();
        {$dataParam}
    }
JS;
        
        return $output;
    }

    /**
     * Erzeugt den JavaScript-Code welcher nach dem Laden des MagicSuggest-Inhalts
     * ausgefuehrt wird.
     * Alle gesetzten Filter werden entfernt, da sich die Werte durch Live-Referenzen
     * aendern koennen (werden vor dem naechsten Laden wieder hinzugefuegt). Wurde der
     * Wert zuvor programmatisch gesetzt, wird er neu gesetzt um das Label ordentlich
     * anzuzeigen.
     *
     * @return string
     */
    function buildJsOnLoad()
    {
        $widget = $this->getWidget();
        
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        
        $suppressLazyLoadingGroupUpdateScript = $widget->getLazyLoadingGroupId() ? '
                // Ist das Widget in einer lazy-loading-group, werden keine Filter-Referenzen aktualisiert,
                // denn alle Elemente der Gruppe werden vom Orginalobjekt bedient.
                if (suppressLazyLoadingGroupUpdate) {
                    ' . $this->getId() . '_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
                }' : '';
        
        $output = <<<JS

    {$this->buildJsDebugMessage('onLoad')}
    var suppressAutoSelectSingleSuggestion = false;
    var suppressLazyLoadingGroupUpdate = false;
    var dataUrlParams = {$this->getId()}_ms.getDataUrlParams();
    var urlFilterPrefix = ("{$this->getTemplate()->getUrlFilterPrefix()}").toLowerCase();  

    for (key in dataUrlParams) {
        if (key.toLowerCase().startsWith(urlFilterPrefix)) {
            delete dataUrlParams[key];
        }
    }
    delete(dataUrlParams._valueSetterUpdate);
    delete(dataUrlParams._clearFilterSetterUpdate);
    delete(dataUrlParams._filterSetterUpdate);
    
    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
        // Update durch eine Value-Referenz.
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
        
        
        var value = {$this->getId()}_ms.getValue();
        // Der Wert wird neu gesetzt um das Label ordentlich anzuzeigen.
        {$this->getId()}_ms.clear(true);
        {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
        {$this->getId()}_ms.setValue(value);
        {$this->getId()}_jquery.removeData("_suppressReloadOnSelect");
        // Ist der Wert leer wird selectionChange und damit onChange sonst nicht
        // getriggert.
        if (value.length == 0) {
            {$this->buildJsFunctionPrefix()}onChange();
        }
    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
        // Leeren durch eine Filter-Referenz.
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
        
        // Neu geladen werden muss nicht, denn die Filter waren beim vorangegangenen Laden schon
        // entsprechend gesetzt.
        {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
        {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
        {$this->getId()}_ms.clear();
        {$this->getId()}_jquery.removeData("_suppressReloadOnSelect");
        {$this->getId()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        
        // Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
        // ausgewaehlt werden.
        suppressAutoSelectSingleSuggestion = true;
    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
        // Update durch eine Filter-Referenz.
        
        // Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse waehrend ein Wert
        // gesetzt ist, widerspricht der gesetzte Wert wahrscheinlich den gesetzten Filtern.
        // Deshalb wird der Wert der ComboTable geloescht und anschliessend neu geladen.
        var rows = {$this->getId()}_ms.getData();
        if (rows.length == 0 && {$this->buildJsFunctionPrefix()}valueGetter()) {
            {$this->getId()}_ms.clear(true);
            {$this->getId()}_ms.setData("{$this->getAjaxUrl()}");
        }
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
        
        // Wurde das Widget ueber eine Filter-Referenz befuellt (lazy-loading-group), werden
        // keine Filter-Referenzen aktualisiert, denn alle Elemente der Gruppe werden vom
        // Orginalobjekt bedient (wurde es hingegen manuell befuellt (autoselect) muessen
        // die Filter-Referenzen bedient werden).
        suppressLazyLoadingGroupUpdate = true;
    }
JS;
        
        if ($widget->getAutoselectSingleSuggestion()) {
            $output .= <<<JS

    if (!suppressAutoSelectSingleSuggestion) {
        // Automatisches Auswaehlen des einzigen Suchvorschlags.
        var rows = {$this->getId()}_ms.getData();
        if (rows.length == 1) {
            var selectedRows = {$this->getId()}_ms.getSelection();
            if (selectedRows.length == 0 || selectedRows.length > 1 || selectedRows[0]["{$uidColumnName}"] != rows[0]["{$uidColumnName}"]) {
                // Fuer multi_select werden erst alle angewaehlten Werte entfernt.
                {$this->getId()}_ms.clear(true);
                {$suppressLazyLoadingGroupUpdateScript}
                // Beim Autoselect wurde ja zuvor schon geladen und es gibt nur noch einen Vorschlag
                // im Resultat (im Gegensatz zur manuellen Auswahl eines Ergebnisses aus einer Liste).
                {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
                // selectionChange wird getriggert
                {$this->getId()}_ms.setSelection(rows[0]);
                {$this->getId()}_ms.collapse(true);
            }
        }
    }
JS;
        }
        
        return $output;
    }

    function getJsDebugLevel()
    {
        return $this->js_debug_level;
    }

    /**
     * Determines the detail-level of the debug-messages which are written to the browser-
     * console.
     * 0 = off, 1 = low, 2 = medium, 3 = high detail-level (default: 0)
     *
     * @param integer|string $value            
     * @return lteComboTable
     */
    function setJsDebugLevel($value)
    {
        if (is_int($value)) {
            $this->js_debug_level = $value;
        } else if (is_string($value)) {
            $this->js_debug_level = intval($value);
        } else {
            throw new InvalidArgumentException('Can not set js_debug_level for "' . $this->getId() . '": the argument passed to set_js_debug_level() is neither an integer nor a string!');
        }
        return $this;
    }

    /**
     * Creates javascript-code that writes a debug-message to the browser-console.
     *
     * @param string $source            
     * @return string
     */
    function buildJsDebugMessage($source)
    {
        switch ($this->getJsDebugLevel()) {
            case 0:
                $output = '';
                break;
            case 1:
            case 2:
                $output = <<<JS
if (window.console) { console.debug(Date.now() + "|{$this->getId()}.{$source}"); }
JS;
                break;
            case 3:
                $output = <<<JS
if (window.console) { console.debug(Date.now() + "|{$this->getId()}.{$source}|" + {$this->buildJsFunctionPrefix()}debugDataToString()); }
JS;
                break;
            default:
                $output = '';
        }
        return $output;
    }

    /**
     * Creates a javascript-function, which returns a string representation of the content
     * of private variables which are stored in the data-object of the element and which
     * are important for the function of the object.
     * It is required for debug-messages with a high detail-level.
     *
     * @return string
     */
    function buildJsDebugDataToStringFunction()
    {
        $output = <<<JS

function {$this->buildJsFunctionPrefix()}debugDataToString() {
    var currentValue = {$this->getId()}_ms_jquery.data("magicsuggest") ? {$this->getId()}_ms.getValue().join() : {$this->getId()}_jquery.val();
    var output =
        "_valueSetterUpdate: " + {$this->getId()}_jquery.data("_valueSetterUpdate") + ", " +
        "_filterSetterUpdate: " + {$this->getId()}_jquery.data("_filterSetterUpdate") + ", " +
        "_clearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_clearFilterSetterUpdate") + ", " +
        "_otherSuppressFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherSuppressFilterSetterUpdate") + ", " +
        "_otherClearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherClearFilterSetterUpdate") + ", " +
        "_otherSuppressAllUpdates: " + {$this->getId()}_jquery.data("_otherSuppressAllUpdates") + ", " +
        "_otherSuppressLazyLoadingGroupUpdate: " + {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate") + ", " +
        "_suppressReloadOnSelect: " + {$this->getId()}_jquery.data("_suppressReloadOnSelect") + ", " +
        "_currentText: " + {$this->getId()}_jquery.data("_currentText") + ", " +
        "_lastValidValue: " + {$this->getId()}_jquery.data("_lastValidValue") + ", " +
        "currentValue: " + currentValue;
    return output;
}
JS;
        return $output;
    }

    /**
     *
     * @return string
     */
    function buildJsInitGlobalsFunction()
    {
        $output = <<<JS

function {$this->buildJsFunctionPrefix()}initGlobals() {
    window.{$this->getId()}_jquery = $("#{$this->getId()}");
    window.{$this->getId()}_ms_jquery = $("#{$this->getId()}_ms");
}
JS;
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    function buildJsEnabler()
    {
        return $this->getId() . '_ms.enable()';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    function buildJsDisabler()
    {
        return $this->getId() . '_ms.disable()';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::buildJsValidator()
     */
    function buildJsValidator()
    {
        $widget = $this->getWidget();
        
        $must_be_validated = $widget->isRequired() && ! ($widget->isHidden() || $widget->isReadonly() || $widget->isDisabled() || $widget->isDisplayOnly());
        if ($must_be_validated) {
            if ($widget->isRequired()) {
                $fallback = '(' . $this->buildJsValueGetter() . ' !== "")';
            } else {
                $fallback = 'true';
            }
            $output = '(' . $this->getId() . '_ms.getData().length === 0 ? ' . $fallback . ' : ' . $this->getId() . '_ms.isValid())';
        } else {
            $output = 'true';
        }
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Templates\Elements\lteInput::buildJsRequired()
     */
    function buildJsRequired()
    {
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}validate() {
        if ({$this->buildJsValidator()}) {
            {$this->getId()}_jquery.parent().removeClass("invalid");
            {$this->getId()}_ms_jquery.removeClass("ms-inv");
        } else {
            {$this->getId()}_jquery.parent().addClass("invalid");
        };
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
}
?>