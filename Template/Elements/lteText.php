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
class lteText extends lteDisplay
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
        
        
        if (! trim($html) && $this->getWidget()->getEmptyText()) {
            $html = $this->getWidget()->getEmptyText();
        }
        
        $output = <<<HTML
            
            {$this->buildHtmlLabel()}
            <p id="{$this->getId()}" class="exf-control" style="{$style}">{$html}</p>

HTML;
        return $this->buildHtmlGridItemWrapper($output);
    }
}
?>