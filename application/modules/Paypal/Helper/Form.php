<?php

namespace Paypal\Helper;

/**
 * Program		: PayPal.class
 * Version		: 1.0
 * Author		: STK
 * Abstract		: Paypal API helper
 * 	(1)
 *	Generate HTML Forms for Paypal AI, including Buy now, Donations, Subscriptions, Shopping carts and Gift certificates. 
 *	Please look at Example folder or examples.
 *	(2)
 *	Process Paypal payments and return transactions from paypal so you can do all processing.
 *
 *https://cms.paypal.com/mx/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables
 *
**/
class Form extends \Payment\Helper\AbstractForm {
	
	/**
	 * Pre-defined constants for all API types
	 */
	const PAYPAL_CART 		= 'PAYPAL_CART';		# Indivitual items with PayPal shopping cart implementation
	
	######################
	# PRIVATE ATTRIBUTES #
	######################
	protected $sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	protected $real_url = 'https://www.paypal.com/cgi-bin/webscr';
	
	/**
	 * Access Public
	 * Define PayPal API type
	 * @param	: $type (string) - see above "Pre-defined constants for all API types"
	 */
	public function setType($type){
		parent::setType($type);
		switch($type){
			case self::BUY_NOW:
				$this->set('cmd', '_xclick'); 
				break;
			case self::DONATE:
				$this->set('cmd', '_donations');	
				break;	
			case self::SUBSCRIBE:	
				$this->set('cmd', '_xclick-subscriptions');
				break;
			case self::GIFT_CERTIFICATE:	
				$this->set('cmd', '_oe-gift-certificate');	
				break;				
			case self::ADD_TO_CART:
				$this->set('cmd', '_cart');	# For shopping cart purchases;
				$this->set('add', 1);		# Add an item to the PayPal Shoppint Cart
				$this->set('display', 1);	# Display the contents of the PayPal Shopping Cart
				break;				
			case self::PAYPAL_CART:
				$this->set('cmd', '_cart');	# For shopping cart purchases;
				$this->set('add', 1);		# Add an item to the PayPal Shoppint Cart
				$this->set('display', 1);	# Display the contents of the PayPal Shopping Cart
				$this->button = $this->getButton(self::ADD_TO_CART);
				break;
			case self::THIRD_PARTY_CART:
				$this->set('cmd', '_cart');		# For shopping cart purchases;
				$this->set('upload', 1);		# Upload the contents of a third party shopping cart or a custom shopping cart.
				break;
		}	
	}		
	/**
	 * Access Public
	 * Get PayPal supported button HTML
	 * @param	: $type (optional) - see above "Pre-defined constants for all API types"
	 */
	public function getButton($type=''){
	
		if($this->button) return $this->button;
		
		if( in_array($type, array(self::BUY_NOW, self::THIRD_PARTY_CART)) ){
			$button = '<input type="image" height="21" style="width:86;border:0px;"';
			$button .= 'src="https://www.paypal.com/en_US/i/btn/btn_paynow_SM.gif" border="0" name="submit" ';
			$button .= 'alt="PayPal - The safer, easier way to pay online!">';
		}
		elseif( $type == self::DONATE ){
			$button = '<input type="image" height="47" style="width:122;border:0px;"';
			$button .= 'src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit"';
			$button .= 'alt="PayPal - The safer, easier way to pay online!">';
		}
		elseif( $type == self::SUBSCRIBE ){
			$button = '<input type="image" height="47" style="width:122;border:0px;"';
			$button .= 'src="https://www.paypal.com/en_US/i/btn/btn_subscribeCC_LG.gif" border="0" name="submit"';
			$button .= 'alt="PayPal - The safer, easier way to pay online!">';
		}
		elseif( $type == self::GIFT_CERTIFICATE ){
			$button = '<input type="image" height="47" style="width:179;border:0px;"';
			$button .= 'src="https://www.paypal.com/en_US/i/btn/btn_giftCC_LG.gif" border="0" name="submit"';
			$button .= 'alt="PayPal - The safer, easier way to pay online!">';
		}
		elseif( in_array($type, array(self::ADD_TO_CART, self::PAYPAL_CART)) ){
			$button = '<input type="image" height="26" style="width:120;border:0px;"';
			$button .= 'src="https://www.paypal.com/en_US/i/btn/btn_cart_LG.gif" border="0" name="submit"';
			$button .= 'alt="PayPal - The safer, easier way to pay online!">';
		}
		
		return $button;
	}
	/**
	 * Access Private
	 * Return HTML regarding with PayPal API cart item	
	 */		
	private function getCartItemsHtml(){
		$html = '';
		if(0 != sizeof($this->cartItems)){
			if(sizeof($this->cartItems) == 1 && in_array($this->type, array(self::BUY_NOW, self::ADD_TO_CART, self::PAYPAL_CART)) ){ # For individual Item Shopping Cart
				$oneItem = $this->cartItems[0];
				$vars = $oneItem->getVars();
				foreach($vars as $key => $value){
					if( $vars[$key] !== ""){
						$id = $this->name . '-' . str_replace('_', '-', $key);
						$html .= '<input type="hidden" id="'.$id.'" name="'.$key.'" value="'.$value.'" />';
					}				
				}
			}else{ # For multiple cart items
				$x = 1;
				foreach($this->cartItems as $oneItem){
					$vars = $oneItem->getVars();
					foreach($vars as $key => $value){
						if( $vars[$key] !== "" ){
							$id = $this->name . '-' . str_replace('_', '-', $key).'-'.$x;
							$html .= '<input type="hidden" id="'.$id.'" name="'.$key.'_'.$x.'" value="'.$value.'" />';
						}				
					}
					$x += 1;
				}
			}
		}
		return $html;
	}
	/**
	 * Access Public
	 * Return entire form HTML for PayPal, but not include form closing tag </form>
	 */		
	public function getHtml(){
		# Check for PayPal ID or an email address associated with PayPal account
		if(!$this->get('business')){
			echo 'Need to set PayPal ID to the variable "business".<br>';
		}
		return parent::getHtml();
	}			
}	

/*
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">

<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="ceo@houston.rr.com">
<input type="hidden" name="item_name" value="Advanced Plans">
<input type="hidden" name="item_number" value="1">
<input type="hidden" name="amount" value="29.95">
<input type="hidden" name="return" value="http://www.treehouseplans.bigstep.com/generic33.html">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="image" src="https://www.paypal.com/images/x-click-but23.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
</form>


<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="ceo@houston.rr.com">
<input type="hidden" name="item_name" value="Basic Plans">
<input type="hidden" name="item_number" value="2">
<input type="hidden" name="amount" value="14.95">
<input type="hidden" name="return" value="http://www.treehouseplans.bigstep.com/generic34.html">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="image" src="https://www.paypal.com/images/x-click-but23.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
</form>
*/
?>