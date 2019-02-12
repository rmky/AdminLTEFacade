<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Widgets\NavTiles;
use exface\Core\Widgets\Tile;

/**
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteNavTiles extends lteWidgetGrid
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.TILES.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteTemplate\Templates\Elements\lteWidgetGrid::buildHtml()
     */
    public function buildHtml()
    {
        if ($this->getWidget()->countWidgets() > 1) {
            foreach ($this->getWidget()->getWidgets() as $tiles) {
                $tiles->setNumberOfColumns(2);
                foreach ($tiles->getTiles() as $tile) {
                    if ($colorClass = $this->getColorClass($tile)) {
                        $this->getTemplate()->getElement($tile)->setCssColorClass($colorClass);
                    }
                }
            }
        } else {
            $this->getWidget()->getWidgetFirst()->setHideCaption(true);
        }
        return parent::buildHtml();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getWidthDefault()
     */
    public function getWidthDefault()
    {
        return $this->getTemplate()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
    }
    
    
    
    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     *
     * @return boolean
     */
    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }
    
    /**
     * 
     * @param Tile $tile
     * @return string
     */
    protected function getColorClass(Tile $tile) : string
    {
        if ($upperTile = $this->getWidget()->getUpperLevelTile($tile)) {
            return $this->getColorClass($upperTile);
        }
        
        $classes = $this->getTemplate()->getConfig()->getOption('WIDGET.TILE.AUTOCOLORS')->toArray();
        $idx = $tile->getParent()->getWidgetIndex($tile);
        return $classes[$idx % count($classes)];
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-navtiles';
    }
}