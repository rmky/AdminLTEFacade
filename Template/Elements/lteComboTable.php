<?php
namespace exface\AdminLteTemplate\Template\Elements;

/**
 * In jQuery Mobile a ComboTable is represented by a filterable UL-list.
 * The code is based on the JQM-example below.
 * jqm example: http://demos.jquerymobile.com/1.4.5/listview-autocomplete-remote/
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
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::init()
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
                    $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
                    
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
                                $("#{$this->getId()}_ms").data("_filterSetterUpdate", true);
                            } else {
                                $("#{$this->getId()}_ms").data("_clearFilterSetterUpdate", true);
                            }
                            $("#{$this->getId()}_ms").magicsuggest().setData("{$this->getAjaxUrl()}");
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
     * @return \exface\AdminLteTemplate\Template\Elements\lteComboTable
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
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::generateHtml()
     */
    function generateHtml()
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
        
        return $this->buildHtmlWrapper($output);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::generateJs()
     */
    function generateJs()
    {
        /* @var $widget \exface\Core\Widgets\ComboTable */
        $widget = $this->getWidget();
        
        // Initialer Wert
        $initial_value_script = '';
        $initial_filter_script = '';
        if (! is_null($this->getValueWithDefaults()) && $this->getValueWithDefaults() !== '') {
            $initial_value_script = $this->getId() . '_jquery.data("_valueSetterUpdate", true);';
            $initial_filter_script = ', fltr00_' . $widget->getValueColumn()->getDataColumnName() . ': "' . $this->getValueWithDefaults() . '"';
        }
        
        // Andere Optionen
        $options = [];
        if (! $widget->getMultiSelect()) {
            $options[] = 'maxSelection: 1';
        }
        if ($widget->isDisabled()) {
            $options[] = 'disabled: true';
        }
        $other_options = implode(",\n    ", $options);
        $other_options = $other_options ? ', ' . $other_options : '';
        
        // Debug-Funktionen
        $debug_function = ($this->getJsDebugLevel() > 0) ? $this->buildJsDebugDataToStringFunction() : '';
        
        // Required-Skript
        $requiredSkript = $widget->isRequired() ? $this->buildJsRequired() : '';
        
        $output = <<<JS

// Globale Variablen initialisieren.
window.{$this->getId()}_jquery = $("#{$this->getId()}_ms");
// Debug-Funktionen hinzufuegen.
{$debug_function}

{$initial_value_script}

window.{$this->getId()}_ms = {$this->getId()}_jquery.magicSuggest({
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
    allowFreeEntries: false,
    beforeSend: function(xhr, settings) {
        return false;
    }
    {$other_options}
});

$({$this->getId()}_ms).on("selectionchange", function(e,m){
    $("#{$this->getId()}").val(m.getValue().join()).trigger("change");
    
    //{$this->getId()}_jquery.data("_filterSetterUpdate", true);
    //{$this->getId()}_ms.setData("{$this->getAjaxUrl()}");
    
    {$this->getOnChangeScript()}
});

$({$this->getId()}_ms).on("beforeload", function(e,m){
    {$this->buildJsOnBeforeload()}
});

$({$this->getId()}_ms).on("load", function(e,m){
    {$this->buildJsOnLoad()}
});

//notwendig fuer Eingabe mit BarcodeScanner
//var {$this->getId()}_typingTimer;
//var {$this->getId()}_input = $("#{$this->getId()}_ms .ms-sel-ctn input");
//{$this->getId()}_input.on("keyup", function() {
//  clearTimeout({$this->getId()}_typingTimer);
//  if ({$this->getId()}_input.val()) {
//      {$this->getId()}_typingTimer = setTimeout(function() {
//          $("#{$this->getId()}_ms").magicSuggest().expand();
//      }, 400);
//  }
//});
JS;
        
        // Es werden JavaScript Value-Getter-/Setter- und OnChange-Funktionen fuer die ComboTable erzeugt,
        // um duplizierten Code zu vermeiden.
        $output .= <<<JS

{$this->buildJsValueGetterFunction()}
{$this->buildJsValueSetterFunction()}
{$this->buildJsOnChangeFunction()}
{$this->buildJsClearFunction()}
JS;
        
        // Es werden Dummy-Methoden fuer die Filter der DataTable hinter dieser ComboTable generiert. Diese
        // Funktionen werden nicht benoetigt, werden aber trotzdem vom verlinkten Element aufgerufen, da
        // dieses nicht entscheiden kann, ob das Filter-Input-Widget existiert oder nicht. Fuer diese Filter
        // existiert kein Input-Widget, daher existiert fuer sie weder HTML- noch JavaScript-Code und es
        // kommt sonst bei einem Aufruf der Funktion zu einem Fehler.
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                $output .= <<<JS

function {$this->getTemplate()->getElement($fltr->getWidget())->getId()}_valueSetter(value){}
JS;
            }
        }
        
        // Ein Skript hinzufuegen, dass den required-Status des Widgets behandelt
        $output .= <<<JS

{$requiredSkript}
JS;
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::generateHeaders()
     */
    function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<link href="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.css" rel="stylesheet">';
        $headers[] = '<script src="exface/vendor/bower-asset/magicsuggest/magicsuggest-min.js"></script>';
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
     * @return string
     */
    function buildJsValueGetterFunction()
    {
        $widget = $this->getWidget();
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        
        if ($widget->getMultiSelect()) {
            $valueGetter = <<<JS

                var resultArray = [];
                for (i = 0; i < rows.length; i++) {
                    if (rows[i][column] == undefined) {
                        if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                        resultArray.push("");
                    } else {
                        resultArray.push(rows[i][column]);
                    }
                }
                return resultArray.join();
JS;
        } else {
            $valueGetter = <<<JS

                if (rows[0][column] == undefined) {
                    if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                    return "";
                } else {
                    return rows[0][column];
                }
JS;
        }
        
        $output = <<<JS

function {$this->getId()}_valueGetter(column, row){
    {$this->buildJsDebugMessage('valueGetter()')}
    
    if ({$this->getId()}_jquery.data("magicSuggest")) {
        if (column){
            var rows = {$this->getId()}_ms.getSelection();
            if (rows.length > 0) {
                {$valueGetter}
            } else {
                return "";
            }
        } else {
            return {$this->getId()}_ms.getValue().join();
        }
    } else {
        if (column) {
            if (column == "{$uidColumnName}") {
                return $("#{$this->getId()}").val();
            } else {
                return "";
            }
        } else {
            return $("#{$this->getId()}").val();
        }
    }
}
JS;
        
        return $output;
    }

    /**
     * The JS value setter for EasyUI combogrids is a custom function defined in euiComboTable::generateJs() - it only needs to be called here.
     *
     * {@inheritdoc}
     *
     * @see \exface\AdminLteTemplate\Template\Elements\lteInput::buildJsValueSetter()
     */
    function buildJsValueSetter($value)
    {
        return $this->getId() . '_valueSetter(' . $value . ')';
    }

    /**
     * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes.
     * Ist multiselect false
     * wird der Wert nur gesetzt wenn genau ein Wert uebergeben wird. Anschliessend wird
     * der Inhalt des MagicSuggest neu geladen (um ordentliche Label anzuzeigen falls
     * auch ein entsprechender Filter gesetzt ist). Ist MagicSuggest noch nicht erzeugt
     * wird stattdessen der Wert im verknuepften InputHidden gesetzt.
     *
     * @return string
     */
    function buildJsValueSetterFunction()
    {
        $widget = $this->getWidget();
        
        if ($this->getWidget()->getMultiSelect()) {
            $valueSetter = <<<JS

            {$this->getId()}_ms.setValue(valueArray);
            $("#{$this->getId()}").val(value).trigger("change");
JS;
        } else {
            $valueSetter = <<<JS

            if (valueArray.length <= 1) {
                {$this->getId()}_ms.setValue(valueArray);
                $("#{$this->getId()}").val(value).trigger("change");
            }
JS;
        }
        
        $output = <<<JS

function {$this->getId()}_valueSetter(value){
    {$this->buildJsDebugMessage('valueSetter()')}
    var valueArray;
    if ({$this->getId()}_jquery.data("magicSuggest")) {
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
            {$this->getId()}_ms.clear();
            
            {$valueSetter}
            
            {$this->getId()}_jquery.data("_valueSetterUpdate", true);
            {$this->getId()}_ms.setData("{$this->getAjaxUrl()}");
        }
    } else {
        $("#{$this->getId()}").val(value).trigger("change");
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

function {$this->getId()}_onChange(){
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
     * Wurde programmatisch ein Wert gesetzt, wird als Filter
     * nur dieser Wert hinzugefuegt, um das Label ordentlich anzuzeigen. Sonst werden
     * die am Widget definierten Filter gesetzt. Die Filter werden nach dem Laden
     * wieder entfernt, da sich die Werte durch Live-Referenzen aendern koennen.
     *
     * @return string
     */
    function buildJsOnBeforeload()
    {
        $widget = $this->getWidget();
        
        $fltrId = 0;
        // Filter aus Filter-Referenzen erzeugen.
        $filters = [];
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                if ($link = $fltr->getValueWidgetLink()) {
                    // filter is a live reference
                    $linked_element = $this->getTemplate()->getElementByWidgetId($link->getWidgetId(), $this->getPageId());
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->getAttributeAlias()) . ' = "' . $fltr->getComparator() . '"+' . $linked_element->buildJsValueGetter($link->getColumnId()) . ';';
                } else {
                    // filter has a static value
                    $filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->getAttributeAlias()) . ' = "' . $fltr->getComparator() . urlencode(strpos($fltr->getValue(), '=') === 0 ? '' : $fltr->getValue()) . '";';
                }
            }
        }
        $filters_script = implode("\n        ", $filters);
        // Beim Leeren eines Widgets in einer in einer lazy-loading-group wird kein Filter gesetzt,
        // denn alle Filter sollten leer sein (alle Elemente der Gruppe leer). Beim Leeren eines
        // Widgets ohne Gruppe werden die normalen Filter gesetzt.
        $clear_filters_script = $widget->getLazyLoadingGroupId() ? '' : $filters_script;
        // Filter aus dem gesetzten Wert erzeugen.
        $value_filters = [];
        $value_filters[] = 'dataUrlParams.fltr' . str_pad($fltrId ++, 2, 0, STR_PAD_LEFT) . '_' . $widget->getValueColumn()->getDataColumnName() . ' = ' . $this->getId() . '_ms.getValue().join();';
        $value_filters_script = implode("\n        ", $value_filters);
        
        $output = <<<JS

    // Wird vor dem ersten Laden nicht aufgerufen!
    
    {$this->buildJsDebugMessage('onBeforeLoad')}
    
    var dataUrlParams = {$this->getId()}_ms.getDataUrlParams();
    
    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
        dataUrlParams._valueSetterUpdate = true;
        {$value_filters_script}
    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
        dataUrlParams._clearFilterSetterUpdate = true;
        {$clear_filters_script}
    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
        dataUrlParams._filterSetterUpdate = true;
        {$filters_script}
        {$value_filters_script}
    } else {
        {$filters_script}
        {$value_filters_script}
    }
JS;
        
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
    function buildJsOnLoad()
    {
        $widget = $this->getWidget();
        
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        $textColumnName = $widget->getTextColumn()->getDataColumnName();
        
        $suppressLazyLoadingGroupUpdateScript = $widget->getLazyLoadingGroupId() ? $this->getId() . '_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);' : '';
        
        $output = <<<JS

    {$this->buildJsDebugMessage('onLoad')}
    var suppressAutoSelectSingleSuggestion = false;
    var dataUrlParams = {$this->getId()}_ms.getDataUrlParams();
    
    for (key in dataUrlParams) {
        if (key.substring(0, 4) == "fltr") {
            delete dataUrlParams[key];
        }
    }
    
    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
        // Update durch eine value-Referenz.
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
        
        // Der Wert wird neu gesetzt um das Label ordentlich anzuzeigen.
        var value = {$this->getId()}_ms.getValue();
        {$this->getId()}_ms.clear();
        {$this->getId()}_ms.setValue(value);
        
        // Nach einem Value-Setter-Update wird der Text neu gesetzt um das Label ordentlich
        // anzuzeigen und das onChange-Skript wird ausgefuehrt.
        //var selectedrow = {$this->getId()}_datagrid.datagrid("getSelected");
        //if (selectedrow != null) {
        //    {$this->getId()}_jquery.combogrid("setText", selectedrow["{$textColumnName}"]);
        //}
        
        //{$this->getId()}_onChange();
    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
        // Leeren durch eine filter-Referenz.
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
        
        //{$this->getId()}_clear(false);
        
        // Neu geladen werden muss nicht, denn die Filter waren beim vorangegangenen Laden schon
        // entsprechend gesetzt.
        
        // Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
        // ausgewaehlt werden.
        //suppressAutoSelectSingleSuggestion = true;
    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
        // Update durch eine filter-Referenz.
        
        // Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse waehrend ein Wert
        // gesetzt ist, widerspricht der gesetzte Wert wahrscheinlich den gesetzten Filtern.
        // Deshalb wird der Wert der ComboTable geloescht und anschliessend neu geladen.
        //var rows = {$this->getId()}_datagrid.datagrid("getData");
        //if (rows["total"] == 0 && {$this->getId()}_valueGetter()) {
        //    {$this->getId()}_clear(true);
        //    {$this->getId()}_datagrid.datagrid("reload");
        //}
        
        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
    }
JS;
        
        if ($widget->getAutoselectSingleSuggestion()) {
            $output .= <<<JS

    if (!suppressAutoSelectSingleSuggestion) {
        // Automatisches Auswaehlen des einzigen Suchvorschlags.
        //var rows = {$this->getId()}_datagrid.datagrid("getData");
        //if (rows["total"] == 1) {
        //    var selectedrow = {$this->getId()}_datagrid.datagrid("getSelected");
        //    if (selectedrow == null || selectedrow["{$uidColumnName}"] != rows["rows"][0]["{$uidColumnName}"]) {
                // Ist das Widget in einer lazy-loading-group, werden keine Filter-Referenzen aktualisiert,
                // denn alle Elemente der Gruppe werden vom Orginalobjekt bedient.
        //        {$suppressLazyLoadingGroupUpdateScript}
                // Beim Autoselect wurde ja zuvor schon geladen und es gibt nur noch einen Vorschlag
                // im Resultat (im Gegensatz zur manuellen Auswahl eines Ergebnisses aus einer Liste).
        //        {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
                // onSelect wird getriggert
        //        {$this->getId()}_datagrid.datagrid("selectRow", 0);
        //        {$this->getId()}_jquery.combogrid("setText", rows["rows"][0]["{$textColumnName}"]);
        //        {$this->getId()}_jquery.combogrid("hidePanel");
        //    }
        //}
    }
JS;
        }
        
        return $output;
    }

    /**
     * Creates a javascript-function which empties the object.
     * If the object had a value
     * before, onChange is triggered by clearing it. If suppressAllUpdates = true is
     * passed to the function, linked elements are not updated by clearing the object.
     * This behavior is usefull, if the object should really just be cleared.
     *
     * @return string
     */
    function buildJsClearFunction()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

function {$this->getId()}_clear(suppressAllUpdates) {
    {$this->buildJsDebugMessage('clear()')}
    
    // Bestimmt ob durch das Leeren andere verlinkte Elemente aktualisiert werden sollen.
    {$this->getId()}_jquery.data("_otherSuppressAllUpdates", suppressAllUpdates);
    // Beim Leeren wird die LazyLoadingGroup (wenn es eine gibt) nicht aktualisiert.
    {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
    // Durch das Leeren aendert sich das resultSet und es sollte das naechste Mal neu geladen
    // werden, auch wenn sich das Filterset nicht geaendert hat (siehe onBeforeLoad).
    {$this->getId()}_jquery.data("_resultSetChanged", true);
    // Triggert onChange, wenn vorher ein Element ausgewaehlt war.
    {$this->getId()}_jquery.combogrid("clear");
    // Wurde das Widget bereits manuell geleert, wird mit clear kein onChange getriggert und
    // _otherSuppressAllUpdates nicht entfernt. Wird clear mit _otherSuppressAllUpdates
    // gestartet, dann ist hinterher _clearFilterSetterUpdate gesetzt. Daher werden hier
    // vorsichtshalber _otherSuppressAllUpdates und _clearFilterSetterUpdate manuell geloescht.
    {$this->getId()}_jquery.removeData("_otherSuppressAllUpdates");
    {$this->getId()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
    {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
}
JS;
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
     * @return \exface\JEasyUiTemplate\Template\Elements\euiComboTable
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
				if (window.console) { console.debug(Date.now() + "|{$this->getId()}.{$source}|" + {$this->getId()}_debugDataToString()); }
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
     * It is required for debug-messages with
     * a high detail-level.
     *
     * @return string
     */
    function buildJsDebugDataToStringFunction()
    {
        $output = <<<JS

function {$this->getId()}_debugDataToString() {
    var currentValue = {$this->getId()}_jquery.data("combogrid") ? {$this->getId()}_jquery.combogrid("getValues").join() : {$this->getId()}_jquery.val();;
    var output =
        "_valueSetterUpdate: " + {$this->getId()}_jquery.data("_valueSetterUpdate") + ", " +
        "_filterSetterUpdate: " + {$this->getId()}_jquery.data("_filterSetterUpdate") + ", " +
        "_clearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_clearFilterSetterUpdate") + ", " +
        "_firstLoad: " + {$this->getId()}_jquery.data("_firstLoad") + ", " +
        "_otherSuppressFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherSuppressFilterSetterUpdate") + ", " +
        "_otherClearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherClearFilterSetterUpdate") + ", " +
        "_otherSuppressAllUpdates: " + {$this->getId()}_jquery.data("_otherSuppressAllUpdates") + ", " +
        "_otherSuppressLazyLoadingGroupUpdate: " + {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate") + ", " +
        "_suppressReloadOnSelect: " + {$this->getId()}_jquery.data("_suppressReloadOnSelect") + ", " +
        "_currentText: " + {$this->getId()}_jquery.data("_currentText") + ", " +
        "_lastValidValue: " + {$this->getId()}_jquery.data("_lastValidValue") + ", " +
        "currentValue: " + currentValue + ", " +
        "_lastFilterSet: "+ JSON.stringify({$this->getId()}_jquery.data("_lastFilterSet")) + ", " +
        "_resultSetChanged: " + {$this->getId()}_jquery.data("_resultSetChanged");
    return output;
}
JS;
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    function buildJsEnabler()
    {
        return '$("#' . $this->getId() . '_ms").magicsuggest().enable()';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    function buildJsDisabler()
    {
        return '$("#' . $this->getId() . '_ms").magicsuggest().disable()';
    }
}
?>