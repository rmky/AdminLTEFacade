<?php
namespace exface\AdminLteTemplate\Template\Elements;
abstract class lteAbstractElement {
	protected $element_id_forbidden_chars = array('/', '(', ')', '.');
	protected $function_prefix_forbidden_chars = array('-', '.');
	protected $hint_max_chars_in_line = 60;
	
	protected $exf_widget = null;
	protected $ajax_url = 'exface/exface.php?exftpl=exface.AdminLteTemplate';
	
	protected $width_relative_unit = 400;
	protected $width_default = 1;
	protected $height_relative_unit = 32;
	protected $height_default = 1;
	
	protected $element_type;
	private $template = null;
	
	private $icon_classes = array(
			'edit' => 'fa fa-pencil-square-o',
			'remove' => 'fa fa-times',
			'add' => 'fa fa-plus',
			'save' => 'fa fa-check',
			'cancel' => 'fa fa-times',
			'relaod' => 'fa fa-refresh',
			'copy' => 'fa fa-files-o',
			'more' => 'fa fa-ellipsis-h',
			'link' => 'fa fa-external-link',
			'barcode' => 'fa fa-barcode',
			'back' => 'fa fa-arrow-left',
			'camera' => 'fa fa-camera',
			'search' => 'fa fa-search'
	);
	
	function __construct($exf_widget, $template){		
		$this->exf_widget = $exf_widget;
		$this->template = $template;
	}
	
	/**
	 * IDEA not sure, wheter we need this function... If it does not get used at all, remove it!
	 */
	function init(){
		
	}
	
	/**
	 * Returns the complete JS code needed for the element
	 */
	abstract function generate_js();
	
	/**
	 * Returns the complete HTML code needed for the element
	 */
	abstract function generate_html();
	
	/**
	 * Returns JavaScript headers, needed for the element as an array of lines.
	 * Make sure, it is always an array, as it is quite possible, that multiple elements
	 * require the same include and we will need to make sure, it is included only once.
	 * The array provides an easy way to get rid of identical lines.
	 * 
	 * Note, that the main includes for the core of jEasyUI generally need to be
	 * placed in the template of the CMS. This method ensures, that widgets can
	 * add other includes like plugins, a plotting framework or other JS-resources.
	 * Thus, the abstract widget returns an empty array.
	 * 
	 * @return string[]
	 */
	function generate_headers(){
		$headers = array();
		if ($this->get_widget()->is_container()){
			foreach ($this->get_widget()->get_children() as $child){
				$headers = array_merge($headers, $this->get_template()->get_element($child)->generate_headers());
			}
		} 
		return $headers;
	}
	
	function build_js_init_options(){
		return '';
	}
	
	function build_js_inline_editor_init(){
		return '';
	}
	
	function get_function_prefix(){
		return str_replace($this->function_prefix_forbidden_chars, '_', $this->get_id()) . '_';
	}
	
	/**
	 * TODO add row and column to select a single value from the widgets data, which is generally 
	 * represented by a DataSheet
	 * @return string
	 */
	public function build_js_value_getter(){
		return '$("#' . $this->get_id() . '").val()';
	}
	
	public function build_js_value_setter($escaped_value_to_set){
		return '$("#' . $this->get_id() . '").val(' . $escaped_value_to_set . ')';
	}
	
	/**
	 * In contrast to build_js_value_getter the data_getter returns the entire dataset used by a widget.
	 * This is a big difference for multi-dimensional widgets like a dataGrid, where the data_getter 
	 * will return all rows and columns, while the value_getter only returns the selected row.
	 * Still, the default for this method is just using the value_getter.
	 * @return string
	 */
	public function build_js_data_getter(){
		return $this->build_js_value_getter();
	}
	
	public function build_js_refresh(){
		return '';
	}
	
	public function build_js_busy_icon_show(){
		return '$("#' . $this->get_id() . '").parents(".box").append($(\'<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>\'));';
	}
	
	public function build_js_busy_icon_hide(){
		return '$("#' . $this->get_id() . '").parents(".box").find(".overlay").remove();';
	}
	
	public function build_js_show_error($error_text, $title = null){
		return "swal('" . ($title ? $title : 'Error') . "', '" . $error_text . "', 'error')";
	}
	
	/**
	 * Returns the id of the HTML-element representing the widget
	 * @return string
	 */
	function get_id($exf_widget_id = null){
		global $exface;
		return  $this->clean_id(($exf_widget_id ? $exf_widget_id : $this->get_widget()->get_id())) . '_' . $exface->get_request_id();
	}
	
	/**
	 * Replaces all characters, which are not supported in the ids of DOM-elements (i.e. "/" etc.)
	 * TODO If widgets are used for input, cleaning the id of usupported characters will probably not be enough.
	 * Bidirectional masking needs to be implemented
	 */
	function clean_id($id){
		return str_replace($this->element_id_forbidden_chars, '_', $id);
	}
	
	/**
	 * Returns the template engine
	 * @return \exface\AdminLteTemplate
	 */
	function get_template(){
		/*global $exface;
		return $exface->get_ui()->get_template('jQueryMobile');*/
		return $this->template;
	}
	
	function escape_string($string){
		return htmlentities($string, ENT_QUOTES);
	}
	
	function get_meta_object(){
		return $this->get_widget()->get_meta_object();
	}
	
	public function get_page_id() {
		return $this->get_widget()->get_page_id();
	}
	
	/**
	 * 
	 * @return \exface\Core\Widgets\AbstractWidget
	 */
	public function get_widget() {
		return $this->exf_widget;
	}
	
	public function set_exf_widget(\exface\Core\Widgets\AbstractWidget $value) {
		$this->exf_widget = $value;
	}

	public function get_ajax_url() {
		$request_id = $this->get_template()->get_workbench()->get_request_id();
		return $this->ajax_url . ($request_id ? '&exfrid=' . $request_id : '');
	}
	
	public function set_ajax_url($value) {
		$this->ajax_url = $value;
	}
	
	/**
	 * Returns the css classes, that define the grid width for the element (e.g. col-xs-12, etc.)
	 * @return string
	 */
	public function get_width_classes(){
		if ($this->get_widget()->get_width()->is_relative()){
			switch ($this->get_widget()->get_width()->get_value()){
				case 1: $width = 'col-xs-12 col-md-4'; break;
				case 2: $width = 'col-xs-12 col-md-8'; break;
				case 3: case 'max': $width = 'col-xs-12';
			}
		}
		return $width;
	}
	
	/**
	 * Returns the width of the element in CSS notation (e.g. 100px)
	 * @return string
	 */
	public function get_width(){
		$dimension = $this->get_widget()->get_width();
		if ($dimension->is_template_specific() || $dimension->is_percentual()){
			$width = $dimension->get_value();
		} else {
			$width = ($this->get_width_relative_unit() * $this->get_width_default()) . 'px';
		}
		return $width;
	}
	
	/**
	 * Returns the height of the element in CSS notation (e.g. 100px)
	 * @return string
	 */
	public function get_height(){
		$dimension = $this->get_widget()->get_height();
		if ($dimension->is_relative()){
			$height = $this->get_height_relative_unit() * $dimension->get_value() . 'px';
		} elseif ($dimension->is_template_specific() || $dimension->is_percentual()){
			$height = $dimension->get_value();
		} else {
			$height = ($this->get_height_relative_unit() * $this->get_height_default()) . 'px';
		}
		return $height;
	}
	
	public function get_height_default() {
		return $this->height_default;
	}
	
	public function set_height_default($value) {
		$this->height_default = $value;
		return $this;
	}
	
	public function get_width_default() {
		return $this->width_default;
	}
	
	public function set_width_default($value) {
		$this->width_default = $value;
		return $this;
	}
	
	public function get_width_relative_unit(){
		return $this->width_relative_unit;
	}
	
	public function get_height_relative_unit(){
		return $this->height_relative_unit;
	}
	
	public function get_element_type() {
		return $this->element_type;
	}
	
	public function set_element_type($value) {
		$this->element_type = $value;
	}
	
	public function get_hint_max_chars_in_line() {
		return $this->hint_max_chars_in_line;
	}
	
	public function set_hint_max_chars_in_line($value) {
		$this->hint_max_chars_in_line = $value;
	}  
	
	public function get_hint(){
		$max_hint_len = $this->get_hint_max_chars_in_line();
		$hint = $this->get_widget()->get_hint();
		$hint = str_replace('"', '\"', $hint);
		$parts = explode("\n", $hint);
		$hint = '';
		foreach ($parts as $part){
			if (strlen($part) > $max_hint_len){
				$words = explode(' ', $part);
				$line = '';
				foreach ($words as $word){
					if (strlen($line)+strlen($word)+1 > $max_hint_len){
						$hint .= $line . "\n";
						$line = $word . ' ';
					} else {
						$line .= $word . ' ';
					}
				}
				$hint .= $line . "\n";
			} else {
				$hint .= $part . "\n";
			}
		}
		return $hint;
	}
	
	public function prepare_data(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet){
		// apply the formatters
		foreach ($data_sheet->get_columns() as $name => $col){
			if ($formatter = $col->get_formatter()) {
				$expr = $formatter->to_string();
				$function = substr($expr, 1, strpos($expr, '(')-1);
				// FIXME the next three lines seem obsolete... Not sure though, since everything works fine right now
				$formatter_class_name = 'formatters\'' . $function;
				if (class_exists($class_name)){
					$formatter = new $class_name($y);
				}
				// See if the formatter returned more results, than there were rows. If so, it was also performed on
				// the total rows. In this case, we need to slice them off and pass to set_column_values() separately.
				// This only works, because evaluating an expression cannot change the number of data rows! This justifies
				// the assumption, that any values after count_rows() must be total values.
				$vals = $formatter->evaluate($data_sheet, $name);
				if ($data_sheet->count_rows() < count($vals)) {
					$totals = array_slice($vals, $data_sheet->count_rows());
					$vals = array_slice($vals, 0, $data_sheet->count_rows());
				}
				$data_sheet->set_column_values($name, $vals, $totals);
			}
		}
		
		$data = array();
		$data['data'] = $data_sheet->get_rows();
		$data['recordsFiltered'] = $data_sheet->count_rows_all();
		$data['recordsTotal'] = $data_sheet->count_rows_all();
		$data['footer'] = $data_sheet->get_totals_rows();
		return $data;
	} 

	public function get_icon_class($exf_icon_name){
	if ($this->icon_classes[$exf_icon_name]){
			return $this->icon_classes[$exf_icon_name];
		} else {
			return $exf_icon_name;
		}
	}
}
?>