<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Button;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisableConditionTrait;
use exface\Core\Interfaces\Actions\iRunFacadeScript;

/**
 * Generates jQuery Mobile buttons for ExFace
 *
 * @author Andrej Kabachnik
 *        
 */
class LteButton extends lteAbstractElement
{
    use JqueryButtonTrait;

    function buildJs()
    {
        $output = '';
        $hotkey_handlers = array();
        $action = $this->getAction();
        
        // Get the java script required for the action itself
        if ($action) {
            // Actions with facade scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action->implementsInterface('iRunFacadeScript')) {
                $output .= $this->getAction()->buildScriptHelperFunctions($this->getFacade());
            }
        }
        
        if ($click = $this->buildJsClickFunction()) {
            
            // Generate the function to be called, when the button is clicked
            $output .= "
				function " . $this->buildJsClickFunctionName() . "(input){
					" . $click . "
				}
				";
            
            // Handle hotkeys
            if ($this->getWidget()->getHotkey()) {
                $hotkey_handlers[$this->getWidget()->getHotkey()][] = $this->buildJsClickFunctionName();
            }
        }
        
        foreach ($hotkey_handlers as $hotkey => $handlers) {
            // TODO add hotkey detection here
        }
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        return $output;
    }

    /**
     *
     * @see \exface\Facades\jeasyui\Widgets\abstractWidget::buildHtml()
     */
    function buildHtml()
    {
        $output = '';
        /* @var $widget \exface\Core\Widgets\Button */
        $widget = $this->getWidget();
        
        // In any case, create a button
        $button_class = $widget->getVisibility() == EXF_WIDGET_VISIBILITY_PROMOTED ? ' btn-primary' : ' btn-default';
        $icon_class = $widget->getIcon() && $widget->getShowIcon(true) ? ' ' . $this->buildCssIconClass($widget->getIcon()) : '';
        $hidden_class = $widget->isHidden() ? ' exfHidden' : '';
        $disabled_class = $widget->isDisabled() ? ' disabled' : '';
        $align_class = $this->getAlignClass();
        $output .= '
				<button id="' . $this->getId() . '" type="button" class="btn' . $button_class . $hidden_class . $disabled_class . $align_class . '"' . ($widget->isDisabled() ? '' : ' onclick="' . $this->buildJsClickFunctionName() . '();"') . '>
						<i class="' . $icon_class . '"></i> ' . ($widget->getCaption() && ! $widget->getHideCaption() ? $widget->getCaption() : '') . '
				</button>';
        return $output;
    }
    
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        // IMPORTANT: do not include head tags from children! Children of a button are widgets inside
        // it's action. Including them here would require rendering them, which severely impacts
        // performanc for complex UIs like the metamodel object editor.
        // Since this facade renders action-widgets by asking the server when the button is pressed
        // (see buildJsClickShowWidget() and buildJsClickShowDialog()) it is enough, to get the head
        // tags for the custom-script actions only.
        return $this->buildHtmlHeadTagsForCustomScriptIncludes();
    }

    protected function buildJsClickShowDialog(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $this->getAction()->getPrefillWithDataFromWidgetLink()) {
            if ($prefill_link->getTargetPageAlias() === null || $widget->getPage()->is($prefill_link->getPageAlias())) {
                $prefill = ", prefill: " . $this->getFacade()->getElement($prefill_link->getTargetWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $js_on_close_dialog = ($this->buildJsInputRefresh($widget) ? "$('#ajax-dialogs').children('.modal').last().one('hide.bs.modal', function(){" . $this->buildJsInputRefresh($widget) . $this->buildJsRefreshCascade($widget) . "});" : "");
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        $output .= <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
							dataType: 'html',
							data: {
								{$this->buildJsRequestCommonParams($widget, $action)}
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								{$this->buildJsCloseDialog($widget, $input_element)}
								{$this->buildJsInputRefresh($widget)}
		                       	{$this->buildJsBusyIconHide()}
		                       	if ($('#ajax-dialogs').length < 1){
		                       		$('body').append('<div id=\"ajax-dialogs\"></div>');
                       			}
		                       	$('#ajax-dialogs').append('<div class=\"ajax-wrapper\">'+data+'</div>');
                                $('#ajax-dialogs').children().last().children('.modal').last().modal('show');
                       			$(document).trigger('{$action->getAliasWithNamespace()}.action.performed', [requestData]);
                       			
								// Make sure, the input widget of the button is always refreshed, once the dialog is closed again
								{$js_on_close_dialog}
							},
							error: function(jqXHR, textStatus, errorThrown){
								{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
								{$this->buildJsBusyIconHide()}
							}
						});
JS;
        
        return $output;
    }

    protected function buildJsCloseDialog($widget, $input_element)
    {
        if ($widget instanceof DialogButton && $widget->getCloseDialogAfterActionSucceeds()) {
            return "$('#" . $this->getFacade()->getElement($widget->getDialog())->getId() . "').modal('hide');";
        }
        return '';
    }

    /**
     * Returns javascript code with global variables and functions needed for certain button types
     */
    protected function buildJsGlobals()
    {
        $output = '';
        /*
         * Commented out because moved to generate_js()
         * // If the button reacts to any hotkey, we need to declare a global variable to collect keys pressed
         * if ($this->getWidget()->getHotkey() == 'any'){
         * $output .= 'var exfHotkeys = [];';
         * }
         */
        return $output;
    }

    function getAlignClass()
    {
        $align = $this->getWidget()->getAlign();
        if ($align == 'left') {
            $align_class = ' pull-left';
        } elseif ($align == 'right') {
            $align_class = ' pull-right';
        } else {
            $align_class = '';
        }
        return $align_class;
    }
}
?>