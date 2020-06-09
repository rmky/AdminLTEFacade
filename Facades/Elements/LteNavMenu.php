<?php
namespace exface\AdminLTEFacade\Facades\Elements;

use exface\Core\Widgets\NavMenu;

/**
 *
 * @method NavMenu getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class LteNavMenu extends LteAbstractElement
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
        $menu = $this->getWidget()->getMenu();
        return $this->buildHtmlMenu($menu);
    }
    
    protected function buildHtmlMenu(array $menu, int $level = 1) : string
    {
        if ($level === 1) {
            $output = "<ul class='sidebar-menu'>";
        } else {
            $output = "<ul class ='treeview-menu'>";
        }
        foreach ($menu as $node) {
            $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
            if ($node->hasChildNodes()) {
                $style = '';
                if ($node->isAncestorOf($this->currentPage)) {
                    $style = " style='font-weight:bold;'";
                }
                if ($node->isPage($this->currentPage)) {
                    $style = " style='text-decoration:underline;'";
                }
                
                $output .= <<<HTML
                <li class='level{$level} treeview active'>
                    <a href='{$url}' {$style}>{$node->getName()}</a>
{$this->buildHtmlMenu($node->getChildNodes(), $level+1)}
                </li>
                
HTML;

            } elseif ($node->isAncestorOf($this->currentPage) || $node->isPage($this->currentPage)) {
                $output .= <<<HTML

                <li class='level{$level} active current'>
                    <a href='{$url}' style='text-decoration:underline;'>{$node->getName()}</a>
                </li>
                
HTML;
            } else {
                $output .= <<<HTML
                
                <li class='level{$level}'>
                    <a href='{$url}'>{$node->getName()}</a>
                </li>
                
HTML;
            }
        }
        $output .= "</ul>";
        return $output;
    }
    
}