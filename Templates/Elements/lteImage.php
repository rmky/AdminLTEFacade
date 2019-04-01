<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Widgets\Image;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlImageTrait;

/**
 *
 * @method Image getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteImage extends lteDisplay
{
    use HtmlImageTrait;
    
    public function buildCssElementClass()
    {
        return 'img-responsive';
    }
}
?>