<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\AdminLteFacade\Facades\Elements\lteAbstractElement;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContextBarAjaxTrait;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class lteContextBar extends lteAbstractElement 
{
    use JqueryContextBarAjaxTrait;
}
