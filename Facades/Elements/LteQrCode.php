<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Widgets\QrCode;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryQrCodeTrait;

/**
 *
 * @method QrCode getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteQrCode extends lteDisplay
{
    use JqueryQrCodeTrait;
}
?>