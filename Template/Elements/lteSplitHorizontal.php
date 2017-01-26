<?php namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class lteSplitHorizontal extends lteSplitVertical {

	function build_html_for_widgets(){
		$panels = $this->get_widget()->get_panels();
		$panel_no = count($panels);
		if ($panel_no == 0) {
			throw new TemplateUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $this->get_widget()->get_id() . ', at least one Panel is required.');
		} elseif ($panel_no <= 12) {
			$col_width = floor(12/$panel_no);
			$col_rest = 12 % $panel_no;
		} else {
			$col_width = 1;
			$col_rest = 0;
		}

		$panels_html = '';
		foreach ($panels as $panel) {
			$panels_html .= '
					<div class="col-xs-12 col-md-' . $col_width . '">
						' . $this->get_template()->get_element($panel)->generate_html() . '
					</div>';
		}
		if ($col_rest != 0) {
			$panels_html .= '
					<div class="hidden-xs col-md-' . $col_rest . '"></div>';
		}
		$panels_html = '<div class="row">
							' . $panels_html . '
						</div>';
		return $panels_html;
	}
}
