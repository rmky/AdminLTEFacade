<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonGroupTrait;

/**
 * The AdminLTE implementation of the ButtonGroup widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class lteButtonGroup extends lteAbstractElement
{
    use JqueryButtonGroupTrait;
}
?>