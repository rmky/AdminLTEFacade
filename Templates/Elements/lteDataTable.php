<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\Tab;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTablesTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarsTrait;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\DataTable;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\MenuButton;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\DataTypes\JsonDataType;

/**
 *
 * @method DataTable getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteDataTable extends lteAbstractElement
{
    
    use JqueryDataTableTrait;
    
    use JqueryDataTablesTrait {
        buildHtmlHeadTags as buildHtmlHeadTagsViaTrait;
    }
    
    use JqueryToolbarsTrait;

    protected function init()
    {
        parent::init();
        // Do not render the search action in the main toolbar. We will add custom
        // buttons via HTML instead.
        $this->getWidget()->getToolbarMain()->setIncludeSearchActions(false);
    }

    function buildHtml()
    {
        $widget = $this->getWidget();
        
        // Toolbars
        $footer_style = $widget->getHideFooter() ? 'display: none;' : '';
        $footer = $this->buildHtmlFooter($this->buildHtmlToolbars());
        $header = $this->buildHtmlHeader();
        
        $style = '';
        if (! $this->getWidget()->getHeight()->isUndefined()){
            $style .= 'height:' . $this->getHeight() . '; overflow-y: auto;';
        }
        
        // output the html code
        $output = <<<HTML
    <div class="box-header">
        {$header}
    </div><!-- /.box-header -->
    <div class="box-body no-padding" style="{$style}">
        {$this->buildHtmlTable('table table-striped table-hover')}
    </div>
    <div class="box-footer clearfix" style="padding-bottom: 0px; min-height: 55px; {$footer_style}">
        {$footer}
    </div>
    {$this->buildHtmlTableCustomizer()}
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    protected function buildHtmlGridItemWrapper($html)
    {
        $result = $html;
        
        if (! $this->getWidget()->getParent() || $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) {
            $result = <<<HTML
<div class="box">{$result}</div>
HTML;
        }
        $result = <<<HTML
<div class="exf-grid-item {$this->getMasonryItemClass()} {$this->getWidthClasses()}">{$result}</div>
HTML;
        return $result;
    }

    function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\DataTable */
        $widget = $this->getWidget();
        
        $output = <<<JS

var {$this->getId()}_table;
if ($.fn.dataTable != undefined){
    $.fn.dataTable.ext.errMode = 'throw';
}

{$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJs()}

{$this->buildJsFunctionPrefix()}Init();

function {$this->buildJsFunctionPrefix()}Init(){
    
    if ({$this->getId()}_table && $.fn.DataTable.isDataTable( '#{$this->getId()}' )) {
        {$this->getId()}_table.columns.adjust();
        return;
    }
    
    $('#{$this->getTemplate()->getElement($widget->getConfiguratorWidget())->getId()}_popup_columns input').click(function(){
        setColumnVisibility(this.name, (this.checked ? true : false) );
    });
    
    {$this->buildJsNoInitialLoadMessageBefore()}
    
    {$this->getId()}_table = {$this->buildJsTableInit()}
    
    {$this->buildJsNoInitialLoadMessageAfter()}
    
    {$this->buildJsClickListeners()}
    
    {$this->buildJsInitialSelection()}
    
    {$this->buildJsPagination()}
    
    {$this->buildJsQuicksearch()}
    
    {$this->buildJsRowDetails()}
    
    {$this->buildJsFixes()}
    
    context.init({preventDoubleContext: false});
    
    // Code der bei DataTable onResize ausgefuehrt wird
    new ResizeSensor(document.getElementById("{$this->getId()}"), function() {
        // Die Spaltenbreiten in Header und Body werden synchronisiert. Die Ursache ist folg-
        // endes aelteres Problem: https://datatables.net/forums/discussion/5771/row-headers-resize,
        // und besteht scheinbar noch immer.
        {$this->getId()}_table.columns.adjust();
    });
    
    // Starten des Layouters wenn der Konfigurator angezeigt wird.
    {$this->buildJsTableCustomizerOnShownFunction()}
    $("#{$this->getId()}_popup_config").on("shown.bs.modal", function() {
        {$this->buildJsFunctionPrefix()}tableCustomizerOnShown();
    });
}

function setColumnVisibility(name, visible){
    {$this->getId()}_table.column(name+':name').visible(visible);
    $('#columnToggle_'+name).attr("checked", visible);
    try {
        $('#columnToggle_'+name).checkboxradio('refresh');
    } catch (ex) {}
}

function {$this->getId()}_drawPagination(){
    var pages = {$this->getId()}_table.page.info();
    if (pages.page == 0) {
        $('#{$this->getId()}_prevPage').attr('disabled', 'disabled');
    } else {
        $('#{$this->getId()}_prevPage').attr('disabled', false);
    }
    if (pages.page == pages.pages-1 || pages.end == pages.recordsDisplay) {
        $('#{$this->getId()}_nextPage').attr('disabled', 'disabled');
    } else {
        $('#{$this->getId()}_nextPage').attr('disabled', false);	
    }
    $('#{$this->getId()}_pageInfo').html(pages.page*pages.length+1 + ' - ' + (pages.recordsDisplay < (pages.page+1)*pages.length || pages.end == pages.recordsDisplay ? pages.recordsDisplay : (pages.page+1)*pages.length) + ' / ' + pages.recordsDisplay);
    
}

{$this->buildJsRowGroupFunctions()}

{$this->buildJsButtons()}

JS;
        
        return $output;
    }

    public function buildHtmlHeadTags()
    {
        $includes = $this->buildHtmlHeadTagsViaTrait();
        $template = $this->getTemplate();
        
        if ($this->isResponsive()) {
            $includes[] = '<script type="text/javascript" src="' . $template->buildUrlToSource('LIBS.DATATABLES.RESPONSIVE.JS_THEME') . '"></script>';
        }
        
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Templates/js/DataTables.exface.helpers.js"></script>';
        
        // Sortable plugin for column sorting in the table configuration popup
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jquery-sortable/source/js/jquery-sortable-min.js"></script>';
        
        // Right-click menu with context.js
        $includes[] = '<link rel="stylesheet" type="text/css" href="exface/vendor/exface/AdminLteTemplate/Templates/js/context.js/context.bootstrap.css">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Templates/js/context.js/context.js"></script>';
        // $includes[] = '<script type="text/javascript" src="exface/vendor/exface/AdminLteTemplate/Templates/js/jquery.contextmenu.js"></script>';
        
        // Resize-Sensor
        $includes[] = '<script src="exface/vendor/npm-asset/css-element-queries/src/ResizeSensor.js"></script>';
        
        return $includes;
    }

    public function buildHtmlHeader()
    {
        $widget = $this->getWidget();
        $table_caption = $this->getWidget()->getCaption() ? $this->getWidget()->getCaption() : $this->getMetaObject()->getName();
        
        if (! $this->isLazyLoading()) {
            $filter_button_disabled = ' disabled';
        }
        
        if ($widget->getHideHeader()) {
            $header_pagination = '';
            if ($widget->getHideFooter() && $widget->isPaged()){
                $header_pagination = <<<HTML
        <button type="button" href="#" id="{$this->getId()}_prevPage" class="btn btn-box-tool"><i class="fa fa-caret-left"></i></button>
        <button type="button" href="#" id="{$this->getId()}_nextPage" class="btn btn-box-tool"><i class="fa fa-caret-right"></i></button>
HTML;
            }
            
            $output = <<<HTML
    <h3 class="box-title">$table_caption</h3>
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#{$this->getId()}_popup_config" title="{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}"><i class="fa fa-filter"></i></button>
        <button type="button" class="btn btn-box-tool" onclick="{$this->buildJsRefresh(false)} return false;"  title="{$this->translate('WIDGET.REFRESH')}"><i class="fa fa-refresh"></i></button>
        {$header_pagination}
    </div>
HTML;
        } else {
            $output = <<<HTML
    <form id="{$this->getId()}_quickSearch_form">

        <div class="row">
            <div class="col-xs-12 col-md-6">
                <h3 class="box-title" style="line-height: 34px;">$table_caption</h3>
            </div>
            <div class="col-xs-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default btn-advanced-filtering" data-toggle="modal"{$filter_button_disabled} data-target="#{$this->getId()}_popup_config"><i class="fa fa-filter"></i></button>
                    </span>
                    <input id="{$this->getId()}_quickSearch" type="text" class="form-control" placeholder="{$widget->getQuickSearchPlaceholder()}" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" onclick="{$this->buildJsRefresh(false)} return false;"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
        </div>
        <div id="{$this->getId()}_filters_container" style="display: none;">
        </div>
    </form>
HTML;
        }
        return $output;
    }

    protected function buildHtmlFooter($buttons_html)
    {
        $widget = $this->getWidget();
        
        $paginator_class = ! $widget->isPaged() ? 'hidden' : '';
        $refresh_button_class = ! $this->isLazyLoading() ? 'hidden' : '';
        $configurator_button_class = ! $this->isLazyLoading() ? 'hidden': '';
        
        $output = <<<HTML
            <div class="pull-right text-right exf-toolbar" style="min-width: 240px;">
                <form class="form-inline">
                    <div class="btn-group dropup {$paginator_class}" role="group" id="#{$this->getId()}_pageControls">
                        <button type="button" href="#" id="{$this->getId()}_prevPage" class="btn btn-default"><i class="fa fa-caret-left"></i></button>
                        <button type="button" href="#" id="{$this->getId()}_pageInfo" class="btn btn-default" data-toggle="dropdown">0 - 0 / 0</buton>
                        <button type="button" href="#" id="{$this->getId()}_nextPage" class="btn btn-default"><i class="fa fa-caret-right"></i></button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="{$this->getId()}_pageInfo" style="width: 307px;">
                            <li class="box-body">
                                <button href="#" type="button" id="{$this->getId()}_firstPage" class="btn btn-default" onclick="$('#{$this->getId()}_pageInput').val(1);"><i class="fa fa-fast-backward"></i></button>	
                                <div class="input-group">
                                    <input id="{$this->getId()}_pageInput" type="number" class="form-control" value="1" />
                                    <span class="input-group-btn">
                                        <button href="#" type="button" class="btn btn-default"><i class="fa fa-calculator"></i></button>
                                    </span>
                                </div>
                                <button href="#" type="button" id="{$this->getId()}_lastPage" class="btn btn-default" onclick="$('#{$this->getId()}_pageInput').val(Math.floor({$this->getId()}_table.page.info().recordsDisplay / {$this->getId()}_table.page.info().length));"><i class="fa fa-fast-forward"></i></button>	
                            </li>
                        </ul>
                    </div>
                    <button type="button" data-target="#" class="btn btn-default {$refresh_button_class}" onclick="{$this->buildJsRefresh(true)} return false;" title="{$this->translate('WIDGET.REFRESH')}"><i class="fa fa-refresh"></i></button>
                    <button type="button" data-target="#{$this->getId()}_popup_config" data-toggle="modal" class="btn btn-default {$configurator_button_class}" title="{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}"><i class="fa fa-gear"></i></button>
                </form>
            </div>
            {$buttons_html}
            
HTML;
        return $output;
    }

    /**
     * Generates JS fixes for various template-specific issues.
     *
     * @return string
     */
    protected function buildJsFixes()
    {
        // If the table is in a tab, recalculate column width once the tab is opened
        if ($this->getWidget()->getParent() instanceof Tab) {
            $js = <<<JS
                $('a[href="#' + $('#{$this->getId()}').parents('.tab-pane').first().attr('id') + '"]').on('shown.bs.tab', function (e) {
                    {$this->getId()}_table.columns.adjust();
                })
JS;
        } elseif ($this->getWidget()->getParent() instanceof Dialog) {
            // If the table is in a dialog, recalculate column width once the tab is opened
            $js = <<<JS
                $('a[href="#' + $('#{$this->getId()}').parents('.modal').first().attr('id') + '"]').on('shown.bs.modal', function (e) {
                    {$this->getId()}_table.columns.adjust();
                })
JS;
        }
        return $js;
    }

    public function buildHtmlTableCustomizer()
    {
        return <<<HTML

<div class="modal" id="{$this->getId()}_popup_config">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}</h4>
            </div>
            <div class="modal-body">
                {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildHtml()}
            </div>
            <div class="modal-footer">
                <button type="button" href="#" data-dismiss="modal" class="btn btn-default pull-left"><i class="{$this->buildCssIconClass(Icons::TIMES)}"></i> {$this->getWorkbench()->getCoreApp()->getTranslator()->translate('ACTION.SHOWDIALOG.CANCEL_BUTTON')}</button>
                <button type="button" href="#" data-dismiss="modal" class="btn btn-primary pull-right" onclick="{$this->buildJsRefresh(false)}"><i class="{$this->buildCssIconClass(Icons::SEARCH)}"></i> {$this->getWorkbench()->getCoreApp()->getTranslator()->translate('ACTION.READDATA.SEARCH')}</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

HTML;
    }

    protected function buildJsTableCustomizerOnShownFunction()
    {
        // Der 1. Tab ist der aktive wenn der Konfigurator angezeigt wird. Von diesem wird
        // beim Anzeigen des Dialogs der Layouter gestartet.
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}tableCustomizerOnShown() {
        {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget()->getTab(0))->buildJsLayouter()}
    }
JS;
        
        return $output;
    }

    protected function buildJsContextMenu()
    {
        return "context.attach('#{$this->getId()} tbody tr', {$this->buildJsContextMenuLevel($this->getWidget()->getButtons())});";
    }
    
    /**
     * 
     * @param Button[] $buttons
     * @return string
     */
    protected function buildJsContextMenuLevel(array $buttons)
    {
        $context_menu_js = '';
        $widget = $this->getWidget();
        
        $last_parent = null;
        foreach ($buttons as $button) {
            if ($button->isHidden()) {
                continue;
            }
            if (! is_null($last_parent) && $button->getParent() !== $last_parent) {
                $context_menu_js .= ($context_menu_js ? ',' : '') . '{divider: true}';
            }
            $last_parent = $button->getParent();
            
            $context_menu_js .= ($context_menu_js ? ',' : '') . $this->buildJsContextMenuItem($button);
        }
            
        return '[' . $context_menu_js . ']';
    }
    
    protected function buildJsContextMenuItem(Button $button)
    {
        $menu_item = '';
        
        /* @var $btn_element \exface\AdminLteTemplate\Templates\Elements\lteButton */
        $btn_element = $this->getTemplate()->getElement($button);
        
        $icon = '<i class=\'' . $btn_element->buildCssIconClass($button->getIcon()) . '\'></i> ';
        
        if ($button instanceof MenuButton){
            if ($button->getParent() instanceof ButtonGroup && $button === $this->getTemplate()->getElement($button->getParent())->getMoreButtonsMenu()){
                $caption = $button->getCaption() ? $button->getCaption() : '...';
            } else {
                $caption = $button->getCaption();
            }
            $menu_item = <<<JS
    {
        text: "{$icon} {$caption}", 
        subMenu: {$this->buildJsContextMenuLevel($button->getButtons())}
    }
JS;
        } else {
            $menu_item = <<<JS
    {
        text: "{$icon} {$button->getCaption()}", 
        action: function(e){e.preventDefault(); {$btn_element->buildJsClickFunctionName()}();}
    }
JS;
        }
        return $menu_item;
    }

    protected function buildJsFilterIndicatorUpdater()
    {
        $filter_checks = '';
        foreach ($this->getWidget()->getFilters() as $fltr) {
            $filter_checks .= 'if(' . $this->getTemplate()->getElement($fltr)->buildJsValueGetter() . ") activeFilters++; \n";
        }
        return <<<JS
                var activeFilters = 0;
                var filterBtn = $('#{$this->getId()}_quickSearch_form .btn-advanced-filtering');
                filterBtn.children('.label').remove();
                {$filter_checks}
                if (activeFilters > 0){
                    filterBtn
                        .removeClass('btn-default')
                        .addClass('btn-info')
                        .append(' <span class="label label-warning">'+activeFilters+'</span>');
                } else {
                    filterBtn
                        .removeClass('btn-info')
                        .addClass('btn-default');
                }
JS;
    }

    /**
     * 
     * @return string
     */
    protected function buildJsNoInitialLoadMessageBefore()
    {
        $widget = $this->getWidget();
        
        if (! $widget->getAutoloadData() && $widget->getLazyLoading()) {
            // Wenn noetig initiales Laden ueberspringen.
            $output = <<<JS

        $("#{$this->getId()}").data("_skipNextLoad", true);
JS;
            
            // Dieses Skript wird nach dem erfolgreichen Laden ausgefuehrt, um die angezeigte
            // Nachricht (s.u.) zu entfernen. Das Skript muss vor $grid_head erzeugt werden.
            $onLoadSuccessSkript = <<<JS

        $("#{$this->getId()}_no_initial_load_message").remove();
JS;
            $this->addOnLoadSuccess($onLoadSuccessSkript);
        } else {
            $output = '';
        }
        
        return $output;
    }

    /**
     * Generates JS code to show a message if the initial load was skipped.
     *
     * @return string
     */
    protected function buildJsNoInitialLoadMessageAfter()
    {
        $widget = $this->getWidget();
        
        if (! $widget->getAutoloadData() && $widget->getLazyLoading()) {
            $output = <<<JS

            $("#{$this->getId()}").closest(".box-body").append("\
                <div id='{$this->getId()}_no_initial_load_message'\
                     class='no-initial-load-message-overlay'>\
                    <table class='no-initial-load-message-overlay-table'>\
                        <tr>\
                            <td style='text-align:center;'>\
                                {$widget->getAutoloadDisabledHint()}\
                            </td>\
                        </tr>\
                    </table>\
                </div>\
            ");
JS;
        } else {
            $output = '';
        }
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight($calculate = true)
    {
        $height = parent::getHeight();
        if (! $calculate) {
            return $height;
        }
        
        if (strtolower(substr($height, 0, 5)) === 'calc(') {
            $height = trim(substr($height, 4), "()");
        }
        
        $widget = $this->getWidget();
        $calc = [];
        $calc[] = '+ 20px'; // box padding at the bottom
        $calc[] = '- 44px'; // height of the table header
        if (! $widget->getHideFooter()){
            $calc[] = '- 55px';
        } 
        if (! $widget->getHideHeader()){
            $calc[] = '- 54px';
        } else {
            $calc[] = '- 40px';
        }
        if (! empty($calc)) {
            $height = 'calc(' . $height . ' ' . implode(' ', $calc) . ')';
        }
        return $height;
    }
}
?>