<?php

namespace exface\AdminLteTemplate\Template\Elements;

class lteSplitPanel extends ltePanel
{

    function generateJs()
    {
        // FIXME had to override the generate_js() method of lteContainer here, because masonry broke the form for some reason. But masonry
        // layouts are important for forms, so this needs to be fixed. Remove this method from lteForm when done.
        return $this->buildJsForChildren();
    }

    public function getWidthClasses()
    {
        return '';
    }
}
?>
