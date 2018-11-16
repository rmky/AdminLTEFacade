<?php
namespace exface\AdminLteTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\AdminLteTemplate\Templates\AdminLteTemplate;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;

/**
 *
 * @method AdminLteTemplate getTemplate()
 *        
 * @author Andrej Kabachnik
 *        
 */
abstract class lteAbstractElement extends AbstractJqueryElement
{

    public function buildJsInitOptions()
    {
        return '';
    }

    public function buildJsInlineEditorInit()
    {
        return '';
    }

    public function buildJsBusyIconShow()
    {
        return '$("#' . $this->getId() . '").parents(".box").append($(\'<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>\'));';
    }

    public function buildJsBusyIconHide()
    {
        return '$("#' . $this->getId() . '").parents(".box").find(".overlay").remove();';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageError()
     */
    public function buildJsShowMessageError($message_body_js, $title = null)
    {
        return '
			swal(' . ($title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"') . ', ' . $message_body_js . ', "error");';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowError()
     */
    public function buildJsShowError($message_body_js, $title = null)
    {
        $titleScript = $title ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"';
        
        return <<<JS

            adminLteCreateDialog($("#ajax-dialogs").append("<div class='ajax-wrapper'></div>").children(".ajax-wrapper").last(), "error", {$titleScript}, {$message_body_js}, "error_tab_layouter()");
JS;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageSuccess()
     */
    public function buildJsShowMessageSuccess($message_body_js, $title = null)
    {
        $title = ! is_null($title) ? $title : '"' . $this->translate('MESSAGE.SUCCESS_TITLE') . '"';
        return '$.notify({
					title: ' . $title . ',
					message: ' . $message_body_js . ',
				}, {
					type: "success",
					placement: {
						from: "bottom",
						align: "right"
					},
					animate: {
						enter: "animated fadeInUp",
						exit: "animated fadeOutDown"
					},
					mouse_over: "pause",
					template: "<div data-notify=\"container\" class=\"col-xs-11 col-sm-3 alert alert-{0}\" role=\"alert\">" +
						"<button type=\"button\" aria-hidden=\"true\" class=\"close\" data-notify=\"dismiss\">×</button>" +
						"<div data-notify=\"icon\"></div> " +
						"<div data-notify=\"title\">{1}</div> " +
						"<div data-notify=\"message\">{2}</div>" +
						"<div class=\"progress\" data-notify=\"progressbar\">" +
							"<div class=\"progress-bar progress-bar-{0}\" role=\"progressbar\" aria-valuenow=\"0\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width: 0%;\"></div>" +
						"</div>" +
						"<a href=\"{3}\" target=\"{4}\" data-notify=\"url\"></a>" +
					"</div>"
				});';
    }

    public function escapeString($string)
    {
        return htmlentities($string, ENT_QUOTES);
    }

    /**
     * Returns the masonry-item class name of this widget.
     *
     * This class name is generated from the id of the layout-widget of this widget. Like this
     * nested masonry layouts are possible, because each masonry-container only layouts the
     * widgets assigned to it.
     *
     * @return string
     */
    public function getMasonryItemClass()
    {
        $output = '';
        if (($containerWidget = $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget instanceof iLayoutWidgets)) {
            $output = $this->getTemplate()->getElement($containerWidget)->getId() . '_masonry_exf-grid-item';
        }
        return $output;
    }

    /**
     * Returns the css classes, that define the grid width for the element (e.g.
     * col-xs-12, etc.)
     *
     * @return string
     */
    public function getWidthClasses()
    {
        $widget = $this->getWidget();
        
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $columnNumber = $this->getTemplate()->getElement($layoutWidget)->getNumberOfColumns();
        } else {
            $columnNumber = $this->getTemplate()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
        }
        
        $dimension = $widget->getWidth();
        if ($dimension->isRelative()) {
            $width = $dimension->getValue();
            if ($width === 'max') {
                $width = $columnNumber;
            }
            if (is_numeric($width)) {
                if ($width < 1) {
                    $width = 1;
                } else if ($width > $columnNumber) {
                    $width = $columnNumber;
                }
                
                if ($width == $columnNumber) {
                    $output = 'col-xs-12';
                } else {
                    $widthClass = floor($width / $columnNumber * 12);
                    if ($widthClass < 1) {
                        $widthClass = 1;
                    }
                    $output = 'col-xs-12 col-md-' . $widthClass . ' col-sm-6';
                }
            } else {
                $widthClass = floor($this->getWidthDefault() / $columnNumber * 12);
                if ($widthClass < 1) {
                    $widthClass = 1;
                }
                $output = 'col-xs-12 col-md-' . $widthClass . ' col-sm-6';
            }
        } elseif ($widget instanceof iFillEntireContainer) {
            // Ein "grosses" Widget ohne angegebene Breite fuellt die gesamte Breite des
            // Containers aus.
            $output = 'col-xs-12';
            if (! $widget->hasParent() || (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() == 1))) {
                $output = '';
            }
        } else {
            // Ein "kleines" Widget ohne angegebene Breite hat ist widthDefault Spalten breit.
            $widthClass = floor($this->getWidthDefault() / $columnNumber * 12);
            if ($widthClass < 1) {
                $widthClass = 1;
            }
            $output = 'col-xs-12 col-md-' . $widthClass;
        }
        return $output;
    }

    /**
     * Returns the column-width of the masonry sizer-element.
     *
     * Masonry needs to know the column-width to calculate the layout. For this reason a
     * id_sizer element is added to all masonry-containers, which defines the column-width.
     * This function returns the css class, that defines the width for the sizer-element.
     *
     * @return string
     */
    public function getColumnWidthClasses()
    {
        if ($this->getWidget() instanceof iLayoutWidgets) {
            $columnNumber = $this->getNumberOfColumns();
        } else {
            $columnNumber = $this->getTemplate()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
        }
        
        $col_no = floor(12 / $columnNumber);
        if ($col_no < 1) {
            $col_no = 1;
        }
        return ($columnNumber > 2 ? 'col-sm-6 col-md-' . $col_no : 'col-xs-' . $col_no);
    }

    public function prepareData(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet)
    {
        // apply the formatters
        foreach ($data_sheet->getColumns() as $name => $col) {
            if ($formatter = $col->getFormatter()) {
                $expr = $formatter->toString();
                $function = substr($expr, 1, strpos($expr, '(') - 1);
                // FIXME the next three lines seem obsolete... Not sure though, since everything works fine right now
                $formatter_class_name = 'formatters\'' . $function;
                if (class_exists($class_name)) {
                    $formatter = new $class_name($y);
                }
                // See if the formatter returned more results, than there were rows. If so, it was also performed on
                // the total rows. In this case, we need to slice them off and pass to setColumnValues() separately.
                // This only works, because evaluating an expression cannot change the number of data rows! This justifies
                // the assumption, that any values after count_rows() must be total values.
                $vals = $formatter->evaluate($data_sheet, $name);
                if ($data_sheet->countRows() < count($vals)) {
                    $totals = array_slice($vals, $data_sheet->countRows());
                    $vals = array_slice($vals, 0, $data_sheet->countRows());
                }
                $data_sheet->setColumnValues($name, $vals, $totals);
            }
        }
        
        $data = array();
        $data['data'] = $data_sheet->getRows();
        $data['recordsFiltered'] = $data_sheet->countRowsInDataSource();
        $data['recordsTotal'] = $data_sheet->countRowsInDataSource();
        $data['footer'] = $data_sheet->getTotalsRows();
        return $data;
    }
    
    /**
     * Returns the CSS class, that represents the visibility of the widget.
     * @return string
     */
    public function buildCssVisibilityClass()
    {
        switch ($this->getWidget()->getVisibility()){
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                return 'hidden';
        }
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return '';
    }
}
?>