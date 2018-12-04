<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\NavTiles;

/**
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteTiles extends lteWidgetGrid
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteWidgetGrid::getDefaultColumnNumber()
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.NAVTILES.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getWidthDefault()
     */
    public function getWidthDefault()
    {
        return 4;
    }
}
?>