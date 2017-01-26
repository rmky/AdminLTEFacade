<?php namespace exface\AdminLteTemplate\Template\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class lteSplitVertical extends lteContainer {

	function generate_html(){
		$output = '
				<div class="container" id="' . $this->get_id() . '" style="width:100%;">
					' . $this->build_html_for_widgets() . '
				</div>
				';
		return $output;
	}

	function build_html_for_widgets(){
		$panels = $this->get_widget()->get_panels();
		$panel_no = count($panels);
		if ($panel_no == 0) {
			throw new TemplateUnsupportedWidgetPropertyWarning('No Panels have been defined for ' . $this->get_widget()->get_id() . ', at least one Panel is required.');
		}
		$panels_html = '';
		foreach ($panels as $panel) {
			$panels_html .= '
					<div class="row">
						<div class="col-xs-12">
							' . $this->get_template()->get_element($panel)->generate_html() . '
						</div>
					</div>';
		}
		return $panels_html;
	}
}
