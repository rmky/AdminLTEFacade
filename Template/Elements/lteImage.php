<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Image;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlImageTrait;

/**
 *
 * @method Image getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteImage extends lteText
{
    use HtmlImageTrait;
    
    protected function buildCssElementClass()
    {
        return 'img-responsive';
    }
}
?>