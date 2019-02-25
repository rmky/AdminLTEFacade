<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Button;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDisableConditionTrait;

/**
 * Generates jQuery Mobile buttons for ExFace
 *
 * @author Andrej Kabachnik
 *        
 */
class lteButton extends lteAbstractElement
{
    use JqueryButtonTrait;
    use JqueryDisableConditionTrait;

    function buildJs()
    {
        $output = '';
        $hotkey_handlers = array();
        $action = $this->getAction();
        
        // Get the java script required for the action itself
        if ($action) {
            // Actions with template scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action->implementsInterface('iRunTemplateScript')) {
                $output .= $this->getAction()->buildScriptHelperFunctions($this->getTemplate());
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
     * @see \exface\Templates\jeasyui\Widgets\abstractWidget::buildHtml()
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

    protected function buildJsClickShowDialog(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $this->getAction()->getPrefillWithDataFromWidgetLink()) {
            if ($prefill_link->getTargetPageAlias() === null || $widget->getPage()->is($prefill_link->getPageAlias())) {
                $prefill = ", prefill: " . $this->getTemplate()->getElement($prefill_link->getTargetWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $js_on_close_dialog = ($this->buildJsInputRefresh($widget, $input_element) ? "$('#ajax-dialogs').children('.modal').last().one('hide.bs.modal', function(){" . $this->buildJsInputRefresh($widget, $input_element) . "});" : "");
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        $output .= <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
							dataType: 'html',
							data: {
								action: '{$widget->getActionAlias()}',
								resource: '{$widget->getPage()->getAliasWithNamespace()}',
								element: '{$widget->getId()}',
								data: requestData
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								{$this->buildJsCloseDialog($widget, $input_element)}
								{$this->buildJsInputRefresh($widget, $input_element)}
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
        return ($widget->getWidgetType() == 'DialogButton' && $widget->getCloseDialogAfterActionSucceeds() ? "$('#" . $input_element->getId() . "').modal('hide');" : "");
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

    /**
     * In AdminLTE the button does not need any extra headers, as all headers needed for whatever the button loads will
     * come with the AJAX-request.
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array();
    }
}
?>