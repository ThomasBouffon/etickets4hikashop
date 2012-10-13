<?php
/**
 * @package          ETickets4Hikashop
 * @version          0.1
 * @author           thomas.bouffon@gmail.com
 * @from-package     HikaShop for Joomla!
 * @from-version     1.5.8
 * @from-author      hikashop.com
 * @copyright   (C) 2010-2012 HIKARI SOFTWARE. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?>
<?php
class hikashopOrderClass extends hikashopClass{
	var $tables = array('order_product','order');
	var $pkeys = array('order_id','order_id');
	var $mail_success = true;
	var $sendEmailAfterOrderCreation = true;
	function addressUsed($address_id,$order_id=0,$type=''){
		$filter = ' WHERE (order_billing_address_id='.(int)$address_id.' OR order_shipping_address_id='.(int)$address_id.')';
		if(!empty($order_id)&&!empty($type)&&in_array($type,array('shipping','billing'))){
			$filter .= ' AND (order_id!='.$order_id.' OR order_'.$type.'_address_id!='.(int)$address_id.')';
		}
		$query = 'SELECT order_id FROM '.hikashop_table('order').$filter.' LIMIT 1';
		$this->database->setQuery($query);
		return (bool)$this->database->loadResult();
	}
	function save(&$order){
		$new = false;
		if(empty($order->order_id)){
			$order->order_created = time();
			$order->order_ip = hikashop_getIP();
			if(empty($order->order_status)){
				$config =& hikashop_config();
				$order->order_status = $config->get('order_created_status','pending');
			}
			if(empty($order->order_currency_id)){
				$order->order_currency_id = hikashop_getCurrency();
			}
			$new = true;
		}
		$order->order_modified = time();
		$recalculate=false;
		if(!empty($order->product)){
			$productClass = hikashop_get('class.order_product');
			$productClass->update($order->product);
			$recalculate=true;
		}
		if(!$new && (isset($order->order_shipping_price) || isset($order->order_payment_price) || isset($order->order_discount_price))){
			if(isset($order->order_shipping_tax_namekey) || isset($order->order_discount_tax_namekey)){
				$old = $this->get($order->order_id);
				if(!empty($old->order_tax_info)){
					$order->order_tax_info = $old->order_tax_info;
					foreach($order->order_tax_info as $k => $tax){
						if(isset($order->order_shipping_tax_namekey) && $tax->tax_namekey==$order->order_shipping_tax_namekey){
							$order->order_tax_info[$k]->tax_amount_for_shipping = @$order->order_shipping_tax;
							unset($order->order_shipping_tax_namekey);
						}
						if(isset($order->order_discount_tax_namekey) && $tax->tax_namekey==$order->order_discount_tax_namekey){
							$order->order_tax_info[$k]->tax_amount_for_coupon = @$order->order_discount_tax;
							unset($order->order_discount_tax_namekey);
						}
					}
				}
				if(isset($order->order_shipping_tax_namekey)){
					$order->order_tax_info[$order->order_shipping_tax_namekey]=null;
					$order->order_tax_info[$order->order_shipping_tax_namekey]->tax_namekey = $order->order_shipping_tax_namekey;
					$order->order_tax_info[$order->order_shipping_tax_namekey]->tax_amount_for_shipping = @$order->order_shipping_tax;
					unset($order->order_shipping_tax_namekey);
				}
				if(isset($order->order_discount_tax_namekey)){
					$order->order_tax_info[$order->order_discount_tax_namekey]=null;
					$order->order_tax_info[$order->order_discount_tax_namekey]->tax_namekey = $order->order_discount_tax_namekey;
					$order->order_tax_info[$order->order_discount_tax_namekey]->tax_amount_for_coupon = @$order->order_discount_tax;
					unset($order->order_discount_tax_namekey);
				}
			}
			$recalculate = true;
		}
		if($recalculate){
			$this->recalculateFullPrice($order);
		}
		JPluginHelper::importPlugin( 'hikashop' );
		JPluginHelper::importPlugin( 'hikashoppayment' );
		JPluginHelper::importPlugin( 'hikashopshipping' );
		$dispatcher =& JDispatcher::getInstance();
		$do = true;
		if($new){
			$dispatcher->trigger( 'onBeforeOrderCreate', array( & $order, & $do) );
		}else{
			$dispatcher->trigger( 'onBeforeOrderUpdate', array( & $order, & $do) );
		}
		if($do){
			if(isset($order->value))unset($order->value);
			if(isset($order->order_current_lgid))unset($order->order_current_lgid);
			if(isset($order->order_current_locale))unset($order->order_current_locale);
			if(isset($order->mail_status))unset($order->mail_status);
			if(isset($order->order_tax_info) && !is_string($order->order_tax_info)){
				$order->order_tax_info = serialize($order->order_tax_info);
			}
			$order->order_id = parent::save($order);
			if(isset($order->order_tax_info) && is_string($order->order_tax_info)){
				$order->order_tax_info = unserialize($order->order_tax_info);
			}
			if(!empty($order->order_id)){
				if($new && empty($order->order_number)){
					$order->order_number = hikashop_encode($order);
					parent::save($order);
				}
				if(!empty($order->cart->products)){
					foreach($order->cart->products as $k => $p){
						$order->cart->products[$k]->order_id = $order->order_id;
					}
					$productClass = hikashop_get('class.order_product');
					$productClass->save($order->cart->products);
					if(!empty($order->order_discount_code)){
						$query = 'UPDATE '.hikashop_table('discount').' SET discount_used_times=discount_used_times+1 WHERE discount_code='.$this->database->Quote($order->order_discount_code).' AND discount_type=\'coupon\' LIMIT 1';
						$this->database->setQuery($query);
						$this->database->query();
					}
					if(!empty($order->cart->additional)) {
						foreach($order->cart->additional as $k => $p){
							$order->cart->additional[$k]->product_id = 0;
							$order->cart->additional[$k]->order_product_quantity = 0;
							$order->cart->additional[$k]->order_product_name = $p->name;
							$order->cart->additional[$k]->order_product_code = 'order additional';
							$order->cart->additional[$k]->order_product_options = $p->value;
							$order->cart->additional[$k]->order_product_price = $p->price_value;
							$order->cart->additional[$k]->order_id = $order->order_id;
						}
						$productClass->save($order->cart->additional);
					}
				}elseif(!empty($order->order_status)){
					$config =& hikashop_config();
					$cancelled_order_status = explode(',',$config->get('cancelled_order_status'));
					if(in_array($order->order_status,$cancelled_order_status)){
						$productClass = hikashop_get('class.order_product');
						$productClass->cancelProductReservation($order->order_id);
						if(!isset($order->order_discount_code)){
							$oldOrder = $this->get($order->order_id);
							$code = $oldOrder->order_discount_code;
						}else{
							$code = $order->order_discount_code;
						}
						if(!empty($code)){
							$query = 'UPDATE '.hikashop_table('discount').' SET discount_used_times=discount_used_times-1 WHERE discount_code='.$this->database->Quote($order->order_discount_code).' AND discount_type=\'coupon\' LIMIT 1';
							$this->database->setQuery($query);
							$this->database->query();
						}
					}
				}
				if($new){
					$send_email = $this->sendEmailAfterOrderCreation;
					$dispatcher->trigger( 'onAfterOrderCreate', array( & $order,&$send_email) );
					if($send_email){
						$this->getOrderAdditionalInfo($order);
						$query = 'SELECT * FROM '.hikashop_table('address').' WHERE address_id IN ('.(int)@$order->cart->shipping_address->address_id.','.(int)@$order->cart->billing_address->address_id.')';
						$this->database->setQuery($query);
						$order->order_addresses = $this->database->loadObjectList('address_id');
						JPluginHelper::importPlugin( 'hikashop' );
						$dispatcher =& JDispatcher::getInstance();
						foreach($order->cart->products as $k => $product){
							$dispatcher->trigger( 'onBeforeCalculateProductPriceForQuantityInOrder', array( &$product) );
							if(function_exists('hikashop_product_price_for_quantity_in_order')){
								hikashop_product_price_for_quantity_in_order($order->cart->products[$k]);
							}else{
								$order->cart->products[$k]->order_product_total_price_no_vat = $product->order_product_price*$product->order_product_quantity;
								$order->cart->products[$k]->order_product_total_price = ($product->order_product_price+$product->order_product_tax)*$product->order_product_quantity;
							}
							$dispatcher->trigger( 'onBeforeCalculateProductPriceForQuantityInOrder', array( &$product) );
						}
						$addressClass = hikashop_get('class.address');
						$addressClass->loadZone($order->order_addresses);
						$order->order_addresses_fields =& $addressClass->fields;
						$this->loadOrderNotification($order,'order_creation_notification');
						$mail = hikashop_get('class.mail');
						$this->loadProducts($order);
$attach=array();
						$attachs=array();
						foreach ($order->products[$k]->etickets as $l => $eticket) {
							$order->products[$k]->etickets[$l]->eTicketFile=$this->createTicketFile($eticket->id);
						}
$z=0;
						foreach ($order->products as $key => $product) {
							foreach ($product->etickets as $l => $eticket) {
								$attachObj = null;
$z++;
								$attachObj->filename = "ETicket$l.html";
								$attachObj->contentAsText = $eticket->eTicketFile;
								$attachObj->url = "http://localhost";
								$attachs[] = $attachObj;

							}
						}
						$order->mail->attachments=$attachs;

						if(!empty($order->mail->dst_email)){
							$mail->sendMail($order->mail);
						}
						$this->mail_success =& $mail->mail_success;
						$config =& hikashop_config();
						$emails = $config->get('order_creation_notification_email');
						if(!empty($emails)){
							$mail = hikashop_get('class.mail');
							if(!empty($order->customer)){
								$user_email = $order->customer->user_email;
								$user_name = $order->customer->name;
							}
							$order->customer->user_email = explode(',',$emails);
							$order->customer->name= ' ';
							$this->loadOrderNotification($order,'order_admin_notification');
							$order->mail->subject = trim($order->mail->subject);
							if(empty($order->mail->subject)){
								$order->mail->subject = JText::sprintf('NEW_ORDER_SUBJECT',$order->order_number,HIKASHOP_LIVE);
							}
							if(!empty($user_email)){
								$mail->mailer->addReplyTo(array($user_email,$user_name));
							}
							if(!empty($order->mail->dst_email)){
								$mail->sendMail($order->mail);
							}
							if(!empty($user_email)){
								$order->customer->user_email = $user_email;
								$order->customer->name = $user_name;
							}
						}
					}
				}else{
					$send_email = @$order->history->history_notified;
					$dispatcher->trigger( 'onAfterOrderUpdate', array( & $order,&$send_email) );
					if($send_email){
						if(empty($order->mail) && isset($order->order_status)){
							$this->loadOrderNotification($order,'order_status_notification');
						}else{
							$order->mail->data = &$order;
							$order->mail->mail_name = 'order_status_notification';
						}
						if(!empty($order->mail)){
							$mail = hikashop_get('class.mail');
							if(!empty($order->mail->dst_email)){
								$mail->sendMail($order->mail);
							}
							$this->mail_success =& $mail->mail_success;
						}
					}
				}
			}
			return $order->order_id;
		}
		return false;
	}
	function recalculateFullPrice(&$order){
		$query = 'SELECT * FROM '.hikashop_table('order_product').' WHERE order_id='.$order->order_id;
		$this->database->setQuery($query);
		$products = $this->database->loadObjectList();
		$total = 0.0;
		$taxes = array();
		JPluginHelper::importPlugin( 'hikashop' );
		$dispatcher =& JDispatcher::getInstance();
		foreach($products as $product){
			if($product->order_product_code != 'order additional') {
				$dispatcher->trigger( 'onBeforeCalculateProductPriceForQuantityInOrder', array( &$product) );
				if(function_exists('hikashop_product_price_for_quantity_in_order')){
					hikashop_product_price_for_quantity_in_order($product);
				}else{
					$product->order_product_total_price=($product->order_product_price+$product->order_product_tax)*$product->order_product_quantity;
				}
				$dispatcher->trigger( 'onAfterCalculateProductPriceForQuantityInOrder', array( &$product) );
			} else {
				$product->order_product_total_price = ($product->order_product_price + $product->order_product_tax);
			}
			$total+=$product->order_product_total_price;
			if(!empty($product->order_product_tax_info)){
				$product_taxes = unserialize($product->order_product_tax_info);
				foreach($product_taxes as $tax){
					if(!isset($taxes[$tax->tax_namekey])){
						$taxes[$tax->tax_namekey]=0;
					}
					$taxes[$tax->tax_namekey]+=$tax->tax_amount*$product->order_product_quantity;
				}
			}
		}
		$old = $this->get($order->order_id);
		if(!isset($order->order_discount_price)){
			$order->order_discount_price = $old->order_discount_price;
		}
		if(!isset($order->order_shipping_price)){
			$order->order_shipping_price = $old->order_shipping_price;
		}
		if(!isset($order->order_payment_price)){
			$order->order_payment_price = $old->order_payment_price;
		}
		$order->order_full_price = $total-$order->order_discount_price+$order->order_shipping_price+$order->order_payment_price;
		if(!isset($order->order_tax_info)){
			$old = $this->get($order->order_id);
			if(!empty($old->order_tax_info)){
				$order->order_tax_info = $old->order_tax_info;
			}
		}
		if(isset($order->order_tax_info)){
			foreach($order->order_tax_info as $k => $tax){
				$order->order_tax_info[$k]->todo = true;
			}
			if(!empty($taxes)){
				foreach($taxes as $namekey => $amount){
					$found = false;
					foreach($order->order_tax_info as $k => $tax){
						if($tax->tax_namekey==$namekey){
							$order->order_tax_info[$k]->tax_amount = $amount+@$tax->tax_amount_for_coupon+@$tax->tax_amount_for_shipping;
							unset($order->order_tax_info[$k]->todo);
							$found = true;
							break;
						}
					}
					if(!$found){
						$obj = null;
						$obj->tax_namekey = $namekey;
						$obj->tax_amount = $amount;
						$order->order_tax_info[$namekey] = $obj;
					}
				}
			}
			$unset = array();
			foreach($order->order_tax_info as $k => $tax){
				if(isset($tax->todo)){
					$order->order_tax_info[$k]->tax_amount = @$tax->tax_amount_for_coupon+@$tax->tax_amount_for_shipping;
					if(!bccomp($order->order_tax_info[$k]->tax_amount,0,5)){
						$unset[]=$k;
					}else{
						unset($order->order_tax_info[$k]->todo);
					}
				}
			}
			if(!empty($unset)){
				foreach($unset as $u){
					unset($order->order_tax_info[$u]);
				}
			}
		}
	}
	function loadFullOrder($order_id,$additionalData=false,$checkUser=true){
		$order = $this->get($order_id);
		$app = JFactory::getApplication();
		$type='frontcomp';
		if(empty($order)){
			return null;
		}
		$userClass = hikashop_get('class.user');
		$order->customer = $userClass->get($order->order_user_id);
		if($app->isAdmin()){
			if(hikashop_level(1)){
				$query='SELECT * FROM '.hikashop_table('geolocation').' WHERE geolocation_type=\'order\' AND geolocation_ref_id='.$order_id;
				$this->database->setQuery($query);
				$order->geolocation = $this->database->loadObject();
			}
			$query='SELECT * FROM '.hikashop_table('history').' WHERE history_order_id='.$order_id.' ORDER BY history_created DESC';
			$this->database->setQuery($query);
			$order->history = $this->database->loadObjectList();
			if(!empty($order->order_partner_id)){
				$order->partner = $userClass->get($order->order_partner_id);
			}
			$type='backend';
		}elseif($checkUser && hikashop_loadUser() != $order->order_user_id){
			return null;
		}
		$this->orderNumber($order);
		$order->order_subtotal = $order->order_full_price + $order->order_discount_price - $order->order_shipping_price - $order->order_payment_price;
		$this->loadAddress($order->order_shipping_address_id,$order,'shipping','name',$type);
		$this->loadAddress($order->order_billing_address_id,$order,'billing','name',$type);
		if(!empty($order->order_shipping_method)) {
			$currentShipping = hikashop_import('hikashopshipping',$order->order_shipping_method);
			if(method_exists($currentShipping, 'getShippingAddress')) {
				$override = $currentShipping->getShippingAddress($order->order_shipping_id);
				if($override !== false) {
					$order->override_shipping_address = $override;
				}
			}
		}
		$this->loadProducts($order);
		if(!empty($order->additional)) {
			foreach($order->additional as $additional) {
				$order->order_subtotal -= $additional->order_product_price - $additional->order_product_tax;
			}
		}
		$order->order_subtotal_no_vat = 0;
		JPluginHelper::importPlugin( 'hikashop' );
		$dispatcher =& JDispatcher::getInstance();
		foreach($order->products as $k => $product){
			$dispatcher->trigger( 'onBeforeCalculateProductPriceForQuantityInOrder', array( &$order->products[$k]) );
			if(function_exists('hikashop_product_price_for_quantity_in_order')){
				hikashop_product_price_for_quantity_in_order($order->products[$k]);
			}else{
				$order->products[$k]->order_product_total_price_no_vat = $product->order_product_price*$product->order_product_quantity;
				$order->products[$k]->order_product_total_price = ($product->order_product_price+$product->order_product_tax)*$product->order_product_quantity;
			}
			$dispatcher->trigger( 'onAfterCalculateProductPriceForQuantityInOrder', array( &$order->products[$k]) );
			$order->order_subtotal_no_vat += $order->products[$k]->order_product_total_price_no_vat;
			if(!empty($product->order_product_options)){
				$order->products[$k]->order_product_options=unserialize($product->order_product_options);
			}
		}
		if($additionalData){
			$this->getOrderAdditionalInfo($order);
		}
		// Get Electronic tickets
		return $order;
	}
	function getOrderAdditionalInfo(&$order){
		if(hikashop_level(2)){
			$query='SELECT * FROM '.hikashop_table('entry').' WHERE order_id='.$order->order_id;
			$this->database->setQuery($query);
			$order->entries = $this->database->loadObjectList();
		}
		$product_ids = array();
		if(isset($order->cart->products)){
			$products =& $order->cart->products;
		}else{
			$products =& $order->products;
		}
		foreach($products as $product){
			$product_ids[]=$product->product_id;
		}
		if(count($product_ids)){
			$query = 'SELECT * FROM '.hikashop_table('product').' WHERE product_id IN ('.implode(',',$product_ids).') AND product_type=\'variant\'';
			$this->database->setQuery($query);
			$productInfos = $this->database->loadObjectList();
			if(!empty($productInfos)){
				foreach($productInfos as $product){
					foreach($products as $item){
						if($product->product_id == $item->product_id && !empty($product->product_parent_id)){
							$item->product_parent_id = $product->product_parent_id;
							$product_ids[]=$product->product_parent_id;
						}
					}
				}
			}
			$filters = array('a.file_ref_id IN ('.implode(',',$product_ids).')','a.file_type=\'file\'');
			$query = 'SELECT b.*,a.* FROM '.hikashop_table('file').' AS a LEFT JOIN '.hikashop_table('download').' AS b ON b.order_id='.$order->order_id.' AND a.file_id = b.file_id WHERE '.implode(' AND ',$filters);
			$this->database->setQuery($query);
			$files = $this->database->loadObjectList();
			if(!empty($files)){
				foreach($products as $k => $product){
					$products[$k]->files=array();
					foreach($files as $file){
						if($product->product_id==$file->file_ref_id){
							$products[$k]->files[]=$file;
						}
					}
					if(empty($products[$k]->files)&&!empty($product->product_parent_id)){
						foreach($files as $file){
							if($product->product_parent_id==$file->file_ref_id){
								$products[$k]->files[]=$file;
							}
						}
					}
				}
			}
		}
	}
	function loadProducts(&$order){
		$query = 'SELECT a.* FROM '.hikashop_table('order_product').' AS a WHERE a.order_id = '.$order->order_id;
		$this->database->setQuery($query);
		$order->products = $this->database->loadObjectList();
		$order->additional = array();
		foreach($order->products as $k => $product){
			if(!empty($product->order_product_tax_info)){
				$order->products[$k]->order_product_tax_info = unserialize($order->products[$k]->order_product_tax_info);
			}
			if($product->order_product_code == 'order additional') {
				unset($order->products[$k]);
				$order->additional[] = $product;
			}
			if($product->order_product_quantity == 0) {
				unset($order->products[$k]);
			}
			$query='SELECT b.* FROM '.hikashop_table('etickets').' AS b WHERE b.order_product_id='.$product->order_product_id.';';
			$this->database->setQuery($query);
			$order->products[$k]->etickets=$this->database->loadObjectList();
		}
	}
	function loadAddress($address,&$order,$address_type='shipping',$display='name',$type='frontcomp'){
		$addressClass=hikashop_get('class.address');
		$name = $address_type.'_address';
		$order->$name=$addressClass->get($address);
		if(!empty($order->$name)){
			$data =&$order->$name;
			$array = array(&$data);
			$addressClass->loadZone($array,$display,$type);
			if(!empty($addressClass->fields)){
				$order->fields =& $addressClass->fields;
			}
		}
	}
	function orderNumber(&$order){
		return true;
	}
	function get($order_id,$trans=true){
		$order = parent::get($order_id);
		if(!empty($order)){
			$app = JFactory::getApplication();
			$translationHelper = hikashop_get('helper.translation');
			$locale='';
			$lgid=0;
			if($app->isAdmin() && $translationHelper->isMulti()){
				$user =& JFactory::getUser();
				$locale = $user->getParam('language');
				if(empty($locale)){
					$params   = JComponentHelper::getParams('com_languages');
					$locale = $params->get('site', 'en-GB');
				}
				$lgid = $translationHelper->getId($locale);
				if(is_string($trans)){
					$status = $trans;
				}else{
					$status = $order->order_status;
				}
				$query = 'SELECT category_id FROM '.hikashop_table('category').' WHERE category_name='.$this->database->Quote($status).' LIMIT 1';
				$this->database->setQuery($query);
				$id = $this->database->loadResult();
				$trans_table = 'jf_content';
				if($translationHelper->falang){
					$trans_table = 'falang_content';
				}
				$query = 'SELECT value FROM '.hikashop_table($trans_table,false).' AS b WHERE b.reference_id='.(int)$id.' AND b.reference_table=\'hikashop_category\' AND b.reference_field=\'category_name\' AND b.published=1 AND b.language_id='.$lgid.' LIMIT 1';
				$this->database->setQuery($query);
				$order->value = $this->database->loadResult();
				if(empty($order->value)){
					$val = str_replace(' ','_',strtoupper($status));
					$trans = JText::_($val);
					if($val==$trans){
						$order->value = $status;
					}else{
						$order->value = $trans;
					}
				}
			}
			if(!empty($lgid)){
				$order->order_current_lgid = $lgid;
				$order->order_current_locale = $locale;
			}
			if(!empty($order->order_tax_info) && is_string($order->order_tax_info)){
				$order->order_tax_info = unserialize($order->order_tax_info);
			}
		}
		return $order;
	}
	function loadMail(&$product){
		if(!empty($product)){
			$product->order = parent::get($product->order_id);
			$userClass = hikashop_get('class.user');
			$product->customer = $userClass->get($product->order->order_user_id);
			$this->orderNumber($product->order);
			$this->loadMailNotif($product);
		}
		return $product;
	}
	function loadMailNotif(&$element){
		$this->loadLocale($element);
		$mailClass = hikashop_get('class.mail');
		$element->mail = $mailClass->get('order_notification',$element);
		$element->mail->subject = JText::sprintf($element->mail->subject,$element->order->order_number,HIKASHOP_LIVE);
		if(!empty($element->customer->user_email)){
			$element->mail->dst_email =& $element->customer->user_email;
		}else{
			$element->mail->dst_email = '';
		}
		if(!empty($element->customer->name)){
			$element->mail->dst_name =& $element->customer->name;
		}else{
			$element->mail->dst_name = '';
		}
		$lang = &JFactory::getLanguage();
		$override_path = JLanguage::getLanguagePath(JPATH_ROOT).DS.'overrides'.$lang->getTag().'.override.ini';
		if(version_compare(JVERSION,'1.6','>=')&& file_exists($override_path)){
			$lang->override = $lang->parse($override_path);
		}
		$lang->load(HIKASHOP_COMPONENT, JPATH_SITE, null, true );
		if(version_compare(JVERSION,'1.6','<') && file_exists($override_path)){
			$lang->_load($override_path,'override');
		}
	}
	function loadNotification($order_id,$type='order_status_notification'){
		$order = $this->get($order_id);
		$this->loadOrderNotification($order,$type);
		return $order;
	}
	function loadOrderNotification(&$order,$type='order_status_notification'){
		if(empty($order->order_user_id)){
			$dbOrder = parent::get($order->order_id);
			$order->order_user_id = @$dbOrder->order_user_id;
		}
		if(empty($order->customer)){
			$userClass = hikashop_get('class.user');
			$order->customer = $userClass->get($order->order_user_id);
		}
		$this->orderNumber($order);
		global $Itemid;
		$url = '';
		if(!empty($Itemid)){
			$url='&Itemid='.$Itemid;
		}
		$order->order_url = hikashop_frontendLink('index.php?option=com_hikashop&ctrl=order&task=show&cid[]='.$order->order_id.$url);
		$app =& JFactory::getApplication();
		if(!isset($order->mail_status)){
			if(isset($order->order_status)){
				if($app->isAdmin()){
					$locale = $this->loadLocale($order);
					if(!empty($order->order_current_locale) && $order->order_current_locale!=$locale){
						$translationHelper = hikashop_get('helper.translation');
						if($translationHelper->isMulti(true, false)){
							$lgid = $translationHelper->getId($locale);
							$trans_table = 'jf_content';
							if($translationHelper->falang){
								$trans_table = 'falang_content';
							}
							$query = 'SELECT b.value FROM '.hikashop_table('category').' AS a LEFT JOIN '.hikashop_table($trans_table,false).' AS b ON a.category_id=b.reference_id AND b.reference_table=\'hikashop_category\' AND b.reference_field=\'category_name\' AND b.published=1 AND language_id='.$lgid.' WHERE a.category_type=\'status\' AND a.category_name='.$this->database->Quote($order->order_status);
							$this->database->setQuery($query);
							$result = $this->database->loadResult();
							if(!empty($result)){
								$order->mail_status = $result;
							}
						}
					}elseif(!empty($order->value)){
						$order->mail_status = $order->value;
					}
				}else{
					$query = 'SELECT * FROM '.hikashop_table('category').' WHERE category_type=\'status\' AND category_name='.$this->database->Quote($order->order_status);
					$this->database->setQuery($query);
					$status = $this->database->loadObject();
					if(!empty($status->category_name)&&$status->category_name!=$order->order_status){
						$order->mail_status = $status->category_name;
					}
				}
				if(empty($order->mail_status)){
					$val = str_replace(' ','_',strtoupper($order->order_status));
					$trans = JText::_($val);
					if($val==$trans){
						$order->mail_status = $order->order_status;
					}else{
						$order->mail_status = $trans;
					}
				}
			}else{
				$order->mail_status = '';
			}
		}
		$mail_status = $order->mail_status;
		$mailClass = hikashop_get('class.mail');
		$order->mail = $mailClass->get($type,$order);
		$order->mail_status = $mail_status;
		$order->mail->subject = JText::sprintf($order->mail->subject,$order->order_number,$mail_status,HIKASHOP_LIVE);
		if(!empty($order->customer->user_email)){
			$order->mail->dst_email =& $order->customer->user_email;
		}else{
			$order->mail->dst_email = '';
		}
		if(!empty($order->customer->name)){
			$order->mail->dst_name =& $order->customer->name;
		}else{
			$order->mail->dst_name = '';
		}
		if($app->isAdmin()){
			$lang = &JFactory::getLanguage();
			$override_path = JLanguage::getLanguagePath(JPATH_ROOT).DS.'overrides'.$lang->getTag().'.override.ini';
			if(version_compare(JVERSION,'1.6','>=')&& file_exists($override_path)){
				$lang->override = $lang->parse($override_path);
			}
			$lang->load(HIKASHOP_COMPONENT, JPATH_SITE, null, true );
			if(version_compare(JVERSION,'1.6','<') && file_exists($override_path)){
				$lang->_load($override_path,'override');
			}
		}
	}
	function loadLocale(&$order){
		$locale = '';
		if(!empty($order->customer->user_cms_id)){
			$user =& JFactory::getUser($order->customer->user_cms_id);
			$locale = $user->getParam('language');
			if(empty($locale)){
				$locale = $user->getParam('admin_language');
			}
		}
		if(empty($locale)){
			$params   = JComponentHelper::getParams('com_languages');
			$locale = $params->get('site', 'en-GB');
		}
		$lang = &JFactory::getLanguage();
		$override_path = JLanguage::getLanguagePath(JPATH_ROOT).DS.'overrides'.$locale.'.override.ini';
		if(version_compare(JVERSION,'1.6','>=')&& file_exists($override_path)){
			$lang->override = $lang->parse($override_path);
		}
		$lang->load(HIKASHOP_COMPONENT, JPATH_SITE, $locale, true );
		if(version_compare(JVERSION,'1.6','<') && file_exists($override_path)){
			$lang->_load($override_path,'override');
		}
		return $locale;
	}
	function delete(&$elements){
		if(!is_array($elements)){
			$elements = array($elements);
		}
		JPluginHelper::importPlugin( 'hikashop' );
		JPluginHelper::importPlugin( 'hikashoppayment' );
		JPluginHelper::importPlugin( 'hikashopshipping' );
		$dispatcher =& JDispatcher::getInstance();
		$do=true;
		$dispatcher->trigger( 'onBeforeOrderDelete', array( & $elements, &$do) );
		if(!$do){
			return false;
		}
		$string=array();
		foreach($elements as $key => $val){
			$string[$val] = $this->database->Quote($val);
		}
		$query='SELECT order_billing_address_id,order_shipping_address_id FROM '.hikashop_table('order').' WHERE order_id IN ('.implode(',',$string).')';
		$this->database->setQuery($query);
		$orders = $this->database->loadObjectList();
		$result=parent::delete($elements);
		if($result){
			if(!empty($orders)){
				$addresses=array();
				foreach($orders as $order){
					$addresses[$order->order_billing_address_id]=$order->order_billing_address_id;
					$addresses[$order->order_shipping_address_id]=$order->order_shipping_address_id;
				}
				$addressClass=hikashop_get('class.address');
				foreach($addresses as $address){
					$addressClass->delete($address,true);
				}
			}
			$dispatcher->trigger( 'onAfterOrderDelete', array( & $elements) );
		}
		return $result;
	}
	function createTicketFile ($eTicketID) {
		error_log("Ticket : $eTicketID");
		$codeType="barcode";
		ob_start (); 
		if ($codeType == "barcode") {
			require_once __DIR__ . '/../../lib/Image/Barcode2.php';
			$enc=new Image_Barcode2();
			$img=$enc->draw($eTicketID, Image_Barcode2::BARCODE_INT25,Image_Barcode2::IMAGE_PNG,false);

		}
		else if ($codeType == "qrcode") {
			require_once __DIR__ . '/../../lib/phpqrcode.php';
			$enc=new QRencode();
			$tab=$enc->encode($eTicketID,false);
			$img=QRimage::image($tab,4,4);
			}
		ImagePng($img);
		$image_data = '"data:image/png;base64,'.base64_encode(ob_get_clean ()).'"'; 
		// Get ETicket info
		
		//ob_end_clean();
		$query='SELECT a.* FROM '.hikashop_table('eticket_info').' AS a, '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE a.product_id=c.product_id AND c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$info=$this->database->loadObjectList();
		$info=$info[0];
		$query='SELECT order_product_price FROM '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$price=$this->database->loadResult();
		$HTML="<html>";
		$HTML.="<head>\n <title>Your ETicket</title>\n <style type=\"text/css\">\n \n html { height: 100%; }\n body {\n margin: 20px; padding: 12px;\n direction: ltr;\n border:solid 1px;\n }\n .clear { clear:both;}\n #et4h_infos {\n float:left; \n }\n #et4h_codediv {\n float:right; \n }\n #et4h_barcode {\n width:250px;\n height:50px;\n border: 0px solid;\n }#et4h_qrcode {\n width:150px;\n height:150px;\n border: 0px solid;\n }\n </style>\n </head>\n";
		$HTML.="<body>\n <h1>Organizer Name</h1>\n <h2>Event Name</h2>\n <div id=\"et4h_infos\">\n <ul>\n";
		$HTML.="<li>Place : $info->address\n";
		$HTML.="<li>Date : $info->eventdate\n";
		$HTML.="<li>Price : ".sprintf("%.2f",$price)." &euro;\n";
		$HTML.="<li>Ticket #\n </ul>\n </div>\n <div id=et4h_codediv>\n <img id=et4h_$codeType src=";
		$HTML.=$image_data;
		$HTML.="></img>\n </div>\n <div class=\"clear\"></div>\n";
		$HTML.="</body>\n";
		$HTML.="</html>";

		return $HTML;


		}
	}
