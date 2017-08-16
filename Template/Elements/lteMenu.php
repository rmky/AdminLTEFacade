<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Menu;

/**
 * 
 * @method Menu getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class lteMenu extends lteAbstractElement 
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateHtml()
     */
    public function generateHtml()
    {  
        if ($caption = $this->getWidget()->getCaption()){
            $header = <<<HTML
        <li class="header">
            {$caption}
        </li>
HTML;
        }
        
        return <<<HTML
<ul id="{$this->getId()}" class="exf-menu">
    {$header}
    {$this->buildHtmlButtons()}
</ul>
HTML;
    }
    
    /**
     * Renders buttons as <li> elements
     * 
     * @return string
     */
    public function buildHtmlButtons()
    {
        $buttons_html = '';
        $last_parent = null;
        /* @var $b \exface\Core\Widgets\Button */
        foreach ($this->getWidget()->getButtons() as $b) {
            if (is_null($last_parent)){
                $last_parent = $b->getParent();
            }
            
            // Create a menu entry: a link for actions or a separator for empty buttons
            if (! $b->getCaption() && ! $b->getAction()){
                $buttons_html .= '<li role="separator" class="divider"></li>';
            } else {
                if ($b->getParent() !== $last_parent){
                    $buttons_html .= '<li role="separator" class="divider"></li>';
                    $last_parent = $b->getParent();
                }
                // If there is a caption or an action, create a menu entry
                $disabled_class = $b->isDisabled() ? ' disabled' : '';
                $buttons_html .= '
    					<li class="' . $disabled_class . '">
    						<a id="' . $this->getTemplate()->getElement($b)->getId() . '" data-target="#"' . ($b->isDisabled() ? '' : ' onclick="' . $this->getTemplate()->getElement($b)->buildJsClickFunctionName(). '();"') . '>
    							<i class="' . $this->buildCssIconClass($b->getIconName()) . '"></i>' . $b->getCaption() . '
    						</a>
    					</li>';
            }
        }
        return $buttons_html;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    public function generateJs()
    {
        $buttons_js = '';
        foreach ($this->getWidget()->getButtons() as $btn){
            $buttons_js .= $this->getTemplate()->getElement($btn)->generateJs();
        }
        return $buttons_js;
    }
}