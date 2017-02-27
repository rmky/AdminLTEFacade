<?php
namespace exface\AdminLteTemplate\Template\Elements;
class lteInputHidden extends lteInput {
	
	function generate_html(){
		// gibt das ein Problem?, sonst ist naemlich anfaenglich der Referenzstring eingetragen
		// falls es einen gibt
		$output = '<input type="hidden"
								name="' . $this->get_widget()->get_attribute_alias() . '"
								value="' . $this->escape_string($this->get_value_with_defaults()) . '"
								id="' . $this->get_id() . '" />';
		return $output;
	}

	function generate_js() {
		return '';
	}
	
	/**
	 * Erzeugung einer JavaScript-Funktion zum Setzen des Wertes (z.B. bei Live-Referenz).
	 * Es wird unterschieden zwischen numerischen Werten (einer oder eine Komma-separierte)
	 * List und nichtnumerischen Werten (welche auch Kommas beinhalten koennen). Wird
	 * ein einzelner num. Wert oder ein sonstiger Wert uebergeben wird er gesetzt
	 * (eindeutige OID oder Text), werden mehrere num. Werte uebergeben, wird ein leerer
	 * Wert gesetzt (nicht eindeutige Oids).
	 * 
	 * {@inheritDoc}
	 * @see \exface\AdminLteTemplate\Template\Elements\lteInput::build_js_value_setter()
	 */
	/*function build_js_value_setter($value){
		$output = '
				var ' . $this->get_id() . ' = $("#' . $this->get_id() . '");
				var value = ' . $value . ', valueArray;
				if (value) { valueArray = $.map(value.split(","), $.trim); } else { valueArray = []; }
				if ($.map(valueArray, $.isNumeric).every(function(b) { return b; })) {
					if (valueArray.length == 1) {
						' . $this->get_id() . '.val(valueArray[0]);
					} else {
						' . $this->get_id() . '.val("");
					}
				} else {
					' . $this->get_id() . '.val(value);
				}
				' . $this->get_id() . '.trigger("change");';
		
		return $output;
	}*/
	
}