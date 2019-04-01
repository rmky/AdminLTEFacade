<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\QrCode;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryQrCodeTrait;

/**
 *
 * @method QrCode getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class LteQrCode extends lteDisplay
{
    use JqueryQrCodeTrait;
}
?>