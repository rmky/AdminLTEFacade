<?php
namespace exface\AdminLTEFacade\Facades\Elements;

class LteInputTime extends lteInput
{

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
JS;
    }

    public function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<script type="text/javascript" src="exface/vendor/almasaeed2010/adminlte/plugins/timepicker/bootstrap-timepicker.min.js"></script>';
        $headers[] = '<link rel="stylesheet" href="exface/vendor/almasaeed2010/adminlte/plugins/timepicker/bootstrap-timepicker.min.css">';
        return $headers;
    }
}