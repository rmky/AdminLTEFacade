<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\NavCrumbs;

/**
 *
 * @method NavCrumbs getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class LteNavCrumbs extends LteAbstractElement
{
    private $currentPage = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\AdminLTEFacade\Facades\Elements\LteAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        $this->currentPage = $this->getWidget()->getPage();
        $breadcrumbs = $this->getWidget()->getBreadcrumbs();
        if (empty($breadcrumbs) === true) {
            return '';
        }
        $items = $this->buildHtmlBreadcrumbs($breadcrumbs);
        $output = <<<HTML

<ol class="breadcrumb">$items</ol>
HTML;
        
        return $output;
    }
    
    /**
     * 
     * @param array $menu
     * @return string
     */
    protected function buildHtmlBreadcrumbs(array $menu) : string
    {
        $output = '';
        foreach($menu as $node) {
            if ($node->isAncestorOf($this->currentPage)) {
                $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
                if ($node->hasParent() === false) {
                    $name = '<i class="fa fa-home"></i><span class="hidden-xs">' . $node->getName() . '</span></a>';
                } else {
                    $name = $node->getName();
                }
                $output .= <<<HTML
                
    <li><a href="{$url}">{$name}</a></li>
HTML;
                if ($node->hasChildNodes()) {
                    $output .= $this->buildHtmlBreadcrumbs($node->getChildNodes());
                }
                break;
            } elseif ($node->isPage($this->currentPage)) {
                // Do not display last crumb (current page)
                break;
            }
        }
        return $output;
    }
    
}
