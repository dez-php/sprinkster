<?php

/**
 * @file    state.php
 * @brief   state select input element
 *
 * @author Ben Wallis <benedict_wallis@yahoo.co.uk>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML-multiple-choice States input select .
 *
 * Class for generic states select list.
 *
 * Option values are 4-digit alpha ISO state codes.
 * 
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML-multiple-choice States input select .
 *
 *
 * Class for generic states select list.
 *
 * Option values are 4-digit alpha ISO state codes.
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add states
 * $form->addState('state');
 *
 * // process form
 * $form->process();
 *
 * // Display the form.
 * echo ($form);
 * ?>
 * @endcode
 */
class State extends \Core\Htmlform\Elements\Single {
	// {{{ variables
	/**
	 * @brief Contains list of ISO countries.
	 */
	protected $list = array ();
	// }}}
	
	// {{{ variables
	/**
	 * @brief Gets the Default States list
	 *
	 * @param $iso -
	 *        	list of state iso codes to filter
	 *        	
	 */
	public static function getStates($iso = null) {
		$country_states = array (
				// http://en.wikipedia.org/wiki/ISO_3166-2:DE
				'de' => array (
						'de-bw' => ("Baden-Württemberg"),
						'de-by' => ("Bayern"),
						'de-be' => ("Berlin"),
						'de-bb' => ("Brandenburg"),
						'de-hb' => ("Bremen"),
						'de-hh' => ("Hamburg"),
						'de-he' => ("Hessen"),
						'de-mv' => ("Mecklenburg-Vorpommern"),
						'de-ni' => ("Niedersachsen"),
						'de-nw' => ("Nordrhein-Westfalen"),
						'de-rp' => ("Rheinland-Pfalz"),
						'de-sl' => ("Saarland"),
						'de-sn' => ("Sachsen"),
						'de-st' => ("Sachsen-Anhalt"),
						'de-sh' => ("Schleswig-Holstein"),
						'de-th' => ("Thüringen") 
				),
				// http://en.wikipedia.org/wiki/ISO_3166-2:US
				'us' => array (
						'us-al' => ("Alabama"),
						'us-ak' => ("Alaska"),
						'us-az' => ("Arizona"),
						'us-ar' => ("Arkansas"),
						'us-ca' => ("California"),
						'us-co' => ("Colorado"),
						'us-ct' => ("Connecticut"),
						'us-de' => ("Delaware"),
						'us-fl' => ("Florida"),
						'us-ga' => ("Georgia"),
						'us-hi' => ("Hawaii"),
						'us-id' => ("Idaho"),
						'us-il' => ("Illinois"),
						'us-in' => ("Indiana"),
						'us-ia' => ("Iowa"),
						'us-ks' => ("Kansas"),
						'us-ky' => ("Kentucky"),
						'us-la' => ("Louisiana"),
						'us-me' => ("Maine"),
						'us-md' => ("Maryland"),
						'us-ma' => ("Massachusetts"),
						'us-mi' => ("Michigan"),
						'us-mn' => ("Minnesota"),
						'us-ms' => ("Mississippi"),
						'us-mo' => ("Missouri"),
						'us-mt' => ("Montana"),
						'us-ne' => ("Nebraska"),
						'us-nv' => ("Nevada"),
						'us-nh' => ("New Hampshire"),
						'us-nj' => ("New Jersey"),
						'us-nm' => ("New Mexico"),
						'us-ny' => ("New York"),
						'us-nc' => ("North Carolina"),
						'us-nd' => ("North Dakota"),
						'us-oh' => ("Ohio"),
						'us-ok' => ("Oklahoma"),
						'us-or' => ("Oregon"),
						'us-pa' => ("Pennsylvania"),
						'us-ri' => ("Rhode Island"),
						'us-sc' => ("South Carolina"),
						'us-sd' => ("South Dakota"),
						'us-tn' => ("Tennessee"),
						'us-tx' => ("Texas"),
						'us-ut' => ("Utah"),
						'us-vt' => ("Vermont"),
						'us-va' => ("Virginia"),
						'us-wa' => ("Washington"),
						'us-wv' => ("West Virginia"),
						'us-wi' => ("Wisconsin"),
						'us-wy' => ("Wyoming"),
						'us-dc' => ("District of Columbia"),
						'us-as' => ("American Samoa"),
						'us-gu' => ("Guam"),
						'us-mp' => ("Northern Mariana Islands"),
						'us-pr' => ("Puerto Rico"),
						'us-um' => ("United States Minor Outlying Islands"),
						'us-vi' => ("Virgin Islands, U.S.)") 
				) 
		);
		
		// return a subset
		if ($iso !== null) {
			// search for iso state codes
			foreach ( $country_states as $country => &$states ) {
				if (isset ( $states [$iso] )) {
					return $states [$iso];
				}
			}
			
			return '';
		}
		
		return $country_states;
	}
	// }}}
	
	// {{{ __construct()
	/**
	 * @brief multiple class constructor
	 *
	 * @param $name (string)
	 *        	element name
	 * @param $parameters (array)
	 *        	element parameters, HTML attributes, validator specs etc.
	 * @param $form (object)
	 *        	parent form object
	 * @return void
	 *
	 */
	public function __construct($name, $parameters, $form) {
		parent::__construct ( $name, $parameters, $form );
		
		if (isset ( $parameters ['defaultValue'] )) {
			$this->defaults ['defaultValue'] = $parameters ['defaultValue'];
		}
		
		$this->list = isset ( $parameters ['states'] ) ? $parameters ['states'] : self::getStates ();
		
		// make sure all keys are lower case
		$this->list = array_change_key_case ( $this->list, CASE_LOWER );
		
		// sort alphabetically
		asort ( $this->list );
		
		$this->list = array (
				'' => ("Please Select") 
		) + $this->list;
	}
	// }}}
	
	// {{{ setDefaults()
	/**
	 * @brief collects initial values across subclasses.
	 *
	 * The constructor loops through these and creates settable class
	 * attributes at runtime. It's a compact mechanism for initialising
	 * a lot of variables.
	 *
	 * @return void
	 *
	 */
	protected function setDefaults() {
		parent::setDefaults ();
		
		$this->defaults ['skin'] = 'select';
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
