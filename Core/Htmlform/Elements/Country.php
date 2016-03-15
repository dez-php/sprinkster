<?php

/**
 * @file    country.php
 * @brief   country select input element
 *
 * @author Ben Wallis <benedict_wallis@yahoo.co.uk>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML-multiple-choice country input select .
 *
 * Class for generic country select list.
 *
 * Option values are 2-digit alpha ISO country codes.
 * Country names are run through get text and sorted alphabetically.
 * 
 * An optional 'priority' parameter moves countries to the top of the select based on the DEPAGE_LANG locale:
 * e.g. 'de' => array('de','at'), 'en' => array('au','gb','ca','us')
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML-multiple-choice country input select .
 *
 *
 * Class for generic country select list.
 *
 * Option values are 2-digit alpha ISO country codes.
 * Country names are run through get text and sorted alphabetically.
 *
 * An optional 'priority' parameter moves countries to the top of the select
 * based on the DEPAGE_LANG locale:
 * e.g. 'de' => array('de','at'), 'en' => array('au','gb','ca','us')
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add country
 * $form->addCountry('country');
 *
 * // process form
 * $form->process();
 *
 * // Display the form.
 * echo ($form);
 * ?>
 * @endcode
 */
class Country extends \Core\Htmlform\Elements\Single {
	// {{{ variables
	/**
	 * @brief Contains list of ISO countries.
	 */
	protected $list = array ();
	// }}}
	
	// {{{ getCountries
	/**
	 * @brief Gets the Default Countries list
	 *
	 * @param $iso -
	 *        	list of country iso codes to filter
	 *        	
	 */
	public static function getCountries($iso = null) {
		$countries = array (
				'ad' => ("Andorra"),
				'ae' => ("United Arab Emirates"),
				'af' => ("Afghanistan"),
				'ag' => ("Antigua &amp; Barbuda"),
				'ai' => ("Anguilla"),
				'al' => ("Albania"),
				'am' => ("Armenia"),
				'an' => ("Netherlands Antilles"),
				'ao' => ("Angola"),
				'aq' => ("Antarctica"),
				'ar' => ("Argentina"),
				'as' => ("American Samoa"),
				'at' => ("Austria"),
				'au' => ("Australia"),
				'aw' => ("Aruba"),
				'az' => ("Azerbaijan"),
				'ba' => ("Bosnia and Herzegovina"),
				'bb' => ("Barbados"),
				'bd' => ("Bangladesh"),
				'be' => ("Belgium"),
				'bf' => ("Burkina Faso"),
				'bg' => ("Bulgaria"),
				'bh' => ("Bahrain"),
				'bi' => ("Burundi"),
				'bj' => ("Benin"),
				'bm' => ("Bermuda"),
				'bn' => ("Brunei Darussalam"),
				'bo' => ("Bolivia"),
				'br' => ("Brazil"),
				'bs' => ("Bahama"),
				'bt' => ("Bhutan"),
				'bv' => ("Bouvet Island"),
				'bw' => ("Botswana"),
				'by' => ("Belarus"),
				'bz' => ("Belize"),
				'ca' => ("Canada"),
				'cc' => ("Cocos (Keeling) Islands"),
				'cf' => ("Central African Republic"),
				'cg' => ("Congo"),
				'ch' => ("Switzerland"),
				'ci' => ("Côte D\'ivoire (Ivory Coast)"),
				'ck' => ("Cook Iislands"),
				'cl' => ("Chile"),
				'cm' => ("Cameroon"),
				'cn' => ("China"),
				'co' => ("Colombia"),
				'cr' => ("Costa Rica"),
				'cu' => ("Cuba"),
				'cv' => ("Cape Verde"),
				'cx' => ("Christmas Island"),
				'cy' => ("Cyprus"),
				'cz' => ("Czech Republic"),
				'de' => ("Germany"),
				'dj' => ("Djibouti"),
				'dk' => ("Denmark"),
				'dm' => ("Dominica"),
				'do' => ("Dominican Republic"),
				'dz' => ("Algeria"),
				'ec' => ("Ecuador"),
				'ee' => ("Estonia"),
				'eg' => ("Egypt"),
				'eh' => ("Western Sahara"),
				'er' => ("Eritrea"),
				'es' => ("Spain"),
				'et' => ("Ethiopia"),
				'fi' => ("Finland"),
				'fj' => ("Fiji"),
				'fk' => ("Falkland Islands"),
				'fm' => ("Micronesia"),
				'fo' => ("Faroe Islands"),
				'fr' => ("France"),
				'fx' => ("France, Metropolitan"),
				'ga' => ("Gabon"),
				'gb' => ("United Kingdom"),
				'gd' => ("Grenada"),
				'ge' => ("Georgia"),
				'gf' => ("French Guiana"),
				'gh' => ("Ghana"),
				'gi' => ("Gibraltar"),
				'gl' => ("Greenland"),
				'gm' => ("Gambia"),
				'gn' => ("Guinea"),
				'gp' => ("Guadeloupe"),
				'gq' => ("Equatorial Guinea"),
				'gr' => ("Greece"),
				'gs' => ("South Georgia and the South Sandwich Islands"),
				'gt' => ("Guatemala"),
				'gu' => ("Guam"),
				'gw' => ("Guinea-Bissau"),
				'gy' => ("Guyana"),
				'hk' => ("Hong Kong"),
				'hm' => ("Heard &amp; McDonald Islands"),
				'hn' => ("Honduras"),
				'hr' => ("Croatia"),
				'ht' => ("Haiti"),
				'hu' => ("Hungary"),
				'id' => ("Indonesia"),
				'ie' => ("Ireland"),
				'il' => ("Israel"),
				'in' => ("India"),
				'io' => ("British Indian Ocean Territory"),
				'iq' => ("Iraq"),
				'ir' => ("Islamic Republic of Iran"),
				'is' => ("Iceland"),
				'it' => ("Italy"),
				'jm' => ("Jamaica"),
				'jo' => ("Jordan"),
				'jp' => ("Japan"),
				'ke' => ("Kenya"),
				'kg' => ("Kyrgyzstan"),
				'kh' => ("Cambodia"),
				'ki' => ("Kiribati"),
				'km' => ("Comoros"),
				'kn' => ("St. Kitts and Nevis"),
				'kp' => ("Korea, Democratic People\'s Republic of"),
				'kr' => ("Korea, Republic of"),
				'kw' => ("Kuwait"),
				'ky' => ("Cayman Islands"),
				'kz' => ("Kazakhstan"),
				'la' => ("Lao People\'s Democratic Republic"),
				'lb' => ("Lebanon"),
				'lc' => ("Saint Lucia"),
				'li' => ("Liechtenstein"),
				'lk' => ("Sri Lanka"),
				'lr' => ("Liberia"),
				'ls' => ("Lesotho"),
				'lt' => ("Lithuania"),
				'lu' => ("Luxembourg"),
				'lv' => ("Latvia"),
				'ly' => ("Libyan Arab Jamahiriya"),
				'ma' => ("Morocco"),
				'mc' => ("Monaco"),
				'md' => ("Moldova, Republic of"),
				'mg' => ("Madagascar"),
				'mh' => ("Marshall Islands"),
				'ml' => ("Mali"),
				'mn' => ("Mongolia"),
				'mm' => ("Myanmar"),
				'mo' => ("Macau"),
				'mp' => ("Northern Mariana Islands"),
				'mq' => ("Martinique"),
				'mr' => ("Mauritania"),
				'ms' => ("Monserrat"),
				'mt' => ("Malta"),
				'mu' => ("Mauritius"),
				'mv' => ("Maldives"),
				'mw' => ("Malawi"),
				'mx' => ("Mexico"),
				'my' => ("Malaysia"),
				'mz' => ("Mozambique"),
				'na' => ("Namibia"),
				'nc' => ("New Caledonia"),
				'ne' => ("Niger"),
				'nf' => ("Norfolk Island"),
				'ng' => ("Nigeria"),
				'ni' => ("Nicaragua"),
				'nl' => ("Netherlands"),
				'no' => ("Norway"),
				'np' => ("Nepal"),
				'nr' => ("Nauru"),
				'nu' => ("Niue"),
				'nz' => ("New Zealand"),
				'om' => ("Oman"),
				'pa' => ("Panama"),
				'pe' => ("Peru"),
				'pf' => ("French Polynesia"),
				'pg' => ("Papua New Guinea"),
				'ph' => ("Philippines"),
				'pk' => ("Pakistan"),
				'pl' => ("Poland"),
				'pm' => ("St. Pierre &amp; Miquelon"),
				'pn' => ("Pitcairn"),
				'pr' => ("Puerto Rico"),
				'pt' => ("Portugal"),
				'pw' => ("Palau"),
				'py' => ("Paraguay"),
				'qa' => ("Qatar"),
				're' => ("Réunion"),
				'ro' => ("Romania"),
				'ru' => ("Russian Federation"),
				'rw' => ("Rwanda"),
				'sa' => ("Saudi Arabia"),
				'sb' => ("Solomon Islands"),
				'sc' => ("Seychelles"),
				'sd' => ("Sudan"),
				'se' => ("Sweden"),
				'sg' => ("Singapore"),
				'sh' => ("St. Helena"),
				'si' => ("Slovenia"),
				'sj' => ("Svalbard &amp; Jan Mayen Islands"),
				'sk' => ("Slovakia"),
				'sl' => ("Sierra Leone"),
				'sm' => ("San Marino"),
				'sn' => ("Senegal"),
				'so' => ("Somalia"),
				'sr' => ("Suriname"),
				'st' => ("Sao Tome &amp; Principe"),
				'sv' => ("El Salvador"),
				'sy' => ("Syrian Arab Republic"),
				'sz' => ("Swaziland"),
				'tc' => ("Turks &amp; Caicos Islands"),
				'td' => ("Chad"),
				'tf' => ("French Southern Territories"),
				'tg' => ("Togo"),
				'th' => ("Thailand"),
				'tj' => ("Tajikistan"),
				'tk' => ("Tokelau"),
				'tm' => ("Turkmenistan"),
				'tn' => ("Tunisia"),
				'to' => ("Tonga"),
				'tp' => ("East Timor"),
				'tr' => ("Turkey"),
				'tt' => ("Trinidad &amp; Tobago"),
				'tv' => ("Tuvalu"),
				'tw' => ("Taiwan, Province of China"),
				'tz' => ("Tanzania, United Republic of"),
				'ua' => ("Ukraine"),
				'ug' => ("Uganda"),
				'um' => ("United States Minor Outlying Islands"),
				'us' => ("United States of America"),
				'uy' => ("Uruguay"),
				'uz' => ("Uzbekistan"),
				'va' => ("Vatican City State (Holy See)"),
				'vc' => ("St. Vincent &amp; the Grenadines"),
				've' => ("Venezuela"),
				'vg' => ("British Virgin Islands"),
				'vi' => ("United States Virgin Islands"),
				'vn' => ("Viet Nam"),
				'vu' => ("Vanuatu"),
				'wf' => ("Wallis &amp; Futuna Islands"),
				'ws' => ("Samoa"),
				'ye' => ("Yemen"),
				'yt' => ("Mayotte"),
				'yu' => ("Yugoslavia"),
				'za' => ("South Africa"),
				'zm' => ("Zambia"),
				'zr' => ("Zaire"),
				'zw' => ("Zimbabwe") 
		);
		
		// return a subset
		if ($iso !== null) {
			if (is_array ( $iso )) {
				return array_intersect_key ( $countries, $iso );
			} else {
				return isset ( $countries [$iso] ) ? $countries [$iso] : '';
			}
		}
		
		return $countries;
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
		
		if (isset ( $parameters ['priorityCountries'] )) {
			$this->defaults ['priorityCountries'] = $parameters ['priorityCountries'];
		}
		
		$this->list = isset ( $parameters ['countries'] ) ? $parameters ['countries'] : self::getCountries ();
		
		// make sure all keys are lower case
		$this->list = array_change_key_case ( $this->list, CASE_LOWER );
		
		// sort alphabetically
		asort ( $this->list );
		
		// move priority countries to the top based on options and language
		if (defined ( "DEPAGE_LANG" ) && isset ( $parameters ['priorityCountries'] [DEPAGE_LANG] )) {
			$priorityCountries = $parameters ['priorityCountries'] [DEPAGE_LANG];
			// make sure all keys are lower case to match countries
			$priorityCountries = array_change_key_case ( $priorityCountries, CASE_LOWER );
			
			foreach ( $priorityCountries as &$country_code ) {
				if (isset ( $this->list [$country_code] )) {
					$top = array (
							$country_code => $this->list [$country_code] 
					);
					unset ( $this->list [$country_code] );
					$this->list = $top + $this->list;
				}
			}
		}
		
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
