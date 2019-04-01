<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\NavTiles;
use exface\Core\Widgets\Tile;

/**
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class LteNavTiles extends lteWidgetGrid
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.TILES.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteWidgetGrid::buildHtml()
     */
    public function buildHtml()
    {
        if ($this->getWidget()->countWidgets() > 1) {
            foreach ($this->getWidget()->getWidgets() as $tiles) {
                $tiles->setNumberOfColumns(2);
                foreach ($tiles->getTiles() as $tile) {
                    if ($colorClass = $this->getColorClass($tile)) {
                        $this->getFacade()->getElement($tile)->setCssColorClass($colorClass);
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getWidthDefault()
     */
    public function getWidthDefault()
    {
        return $this->getFacade()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
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
        
        $classes = $this->getFacade()->getConfig()->getOption('WIDGET.TILE.AUTOCOLORS')->toArray();
        $idx = $tile->getParent()->getWidgetIndex($tile);
        return $classes[$idx % count($classes)];
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-navtiles';
    }
}