<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputDateTrait;

class LteInputTime extends lteInput
{
    use JqueryInputDateTrait;
    
    public function buildHtml()
    {
        $requiredScript = $this->getWidget()->isRequired() ? 'required="true" ' : '';
        $disabledScript = $this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML
        
                  <div class="bootstrap-timepicker">
                    <div class="form-group">
                      {$this->buildHtmlLabel()}
    
                      <div class="input-group">
                        <input type="text" 
                            class="form-control timepicker"
                            name="{$this->getWidget()->getAttributeAlias()}"
                            id="{$this->getId()}"
                            value="{$this->getValueWithDefaults()}"
                            {$requiredScript}
                            {$disabledScript} />
    
                        <div class="input-group-addon">
                          <i class="fa fa-clock-o"></i>
                        </div>
                      </div>
                      <!-- /.input group -->
                    </div>
                    <!-- /.form group -->
                  </div>
                
HTML;
                        
        return $this->buildHtmlGridItemWrapper($output);
    }

    public function buildJs()
    {        
        return <<<JS

    $("#{$this->getId()}").timepicker({
        minuteStep: 5,
        showInputs: false,
        showSeconds: false,
        showMeridian: false
    });

    //use own parser when 'tab' or 'enter' is pressed
    $("#{$this->getId()}").timepicker().on('keydown', function(e) {
        //key is 'tab' or 'enter'
        if (e.which === 9 || e.which === 13) {
            var input = $(this).val();
            var value = {$this->getDateFormatter()->buildJsFormatParser('input')};
            $("#{$this->getId()}").timepicker('setTime', value);
        }
    });

    //use own parser when timepicker gets hidden
    $("#{$this->getId()}").timepicker().on('hide.timepicker', function(e) {        
        var input = $(this).val();
        var value = {$this->getDateFormatter()->buildJsFormatParser('input')};
        $("#{$this->getId()}").timepicker('setTime', value);
    });
JS;
    }

    public function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<script type="text/javascript" src="exface/vendor/almasaeed2010/adminlte/plugins/timepicker/bootstrap-timepicker.min.js"></script>';
        $headers[] = '<link rel="stylesheet" href="exface/vendor/almasaeed2010/adminlte/plugins/timepicker/bootstrap-timepicker.min.css">';
        $formatter = $this->getDateFormatter();
        $headers = array_merge($headers, $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
        return $headers;
    }
}