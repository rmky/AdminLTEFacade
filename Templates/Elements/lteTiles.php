<?php
namespace exface\AdminLteFacade\Facades\Elements;

use exface\Core\Widgets\Tiles;

/**
 * @method Tiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteTiles extends lteWidgetGrid
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.TILES.COLUMNS_BY_DEFAULT");
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
     * Tiles do not actually need a masonry grid as they are all of equal height!
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteWidgetGrid::buildHtmlChildrenWrapperGrid()
     */
    protected function buildHtmlChildrenWrapperGrid($contents_html)
    {
        if ($caption = $this->getCaption()) {
            $heading = '<h2 class="page-header">' . $caption . '</h2>';
        }
        return $heading . $contents_html;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-tiles row';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow()
    {
        return '$("#' . $this->getId() . '").find(".small-box").append($(\'<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>\'));';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLteFacade\Facades\Elements\lteAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        return '$("#' . $this->getId() . '").find(".overlay").remove();';
    }
}