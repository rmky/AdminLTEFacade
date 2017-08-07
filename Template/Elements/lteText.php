<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Widgets\Text;

/**
 *
 * @method Text getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class lteText extends lteAbstractElement
{

    function generateHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        $html = nl2br($widget->getText());
        
        switch ($widget->getSize()) {
            case EXF_TEXT_SIZE_BIG:
                $html = '<big>' . $html . '</big>';
                break;
            case EXF_TEXT_SIZE_SMALL:
                $html = '<small>' . $html . '</small>';
                break;
        }
        
        switch ($widget->getStyle()) {
            case EXF_TEXT_STYLE_BOLD:
                $html = '<strong>' . $html . '</strong>';
                break;
            case EXF_TEXT_STYLE_UNDERLINE:
                $html = '<ins>' . $html . '</ins>';
                break;
            case EXF_TEXT_STYLE_STRIKETHROUGH:
                $html = '<del>' . $html . '</del>';
                break;
        }
        
        $style = '';
        switch ($widget->getAlign()) {
            case 'left':
                $style .= 'text-align: left;';
                break;
            case 'right':
                $style .= 'text-align: right;';
                break;
            case 'center':
                $style .= 'text-align: center;';
                break;
        }
        
        if ($this->getWidget()->getCaption() && ! $this->getWidget()->getHideCaption()) {
            $output .= '<label for="' . $this->getId() . '" class="exf-text-label">' . $this->getWidget()->getCaption() . '</label>';
        }
        
        if (! trim($html) && $this->getWidget()->getEmptyText()) {
            $html = $this->getWidget()->getEmptyText();
        }
        
        $output .= '<p id="' . $this->getId() . '" class="exf-text-content" style="' . $style . '">' . $html . '</p>';
        return $this->buildHtmlWrapper($output);
    }

    public function buildHtmlWrapper($inner_html)
    {
        $output = <<<HTML

                    <div class="fitem {$this->getMasonryItemClass()} {$this->getWidthClasses()} {$this->buildCssClasses()}" title="{$this->buildHintText()}">
                            {$inner_html}
                    </div>
HTML;
        
        return $output;
    }

    public function buildCssClasses()
    {
        $classes = ' ';
        if ($this->getWidget()->isHidden()) {
            $classes .= 'hidden';
        }
        return $classes;
    }

    function generateJs()
    {
        return '';
    }
}
?>