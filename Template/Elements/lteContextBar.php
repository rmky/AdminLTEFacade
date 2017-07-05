<?php
namespace exface\AdminLteTemplate\Template\Elements;

use exface\AdminLteTemplate\Template\Elements\lteAbstractElement;

/**
 * The ContextBar widget is rendered by the template itself and is update via response extras.
 * 
 * This implementation makes it possible to fetch context bar data by calling
 * the ShowWidget action via AJAX. This is needed for pages without ajax-based
 * widgets: since these pages do not make ajax requests, the context bar would
 * not get updated, so the template will fetch the data explicitly.
 * 
 * @author Andrej Kabachnik
 *
 */
class lteContextBar extends lteAbstractElement {
    
    public function generateHtml()
    {
        return $this->getTemplate()->encodeData($this->getTemplate()->buildResponseExtraForContextBar(), false);
    }
    
    public function generateJs()
    {
        return '';
    }
}
