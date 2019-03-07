<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\QrCode;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryQrCodeTrait;

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