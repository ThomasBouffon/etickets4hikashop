<?php
/**
 * @package		HikaShop for Joomla!
 * @version		1.5.8
 * @author		hikashop.com
 * @copyright	(C) 2010-2012 HIKARI SOFTWARE. All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?>
<?php
class plgHikashopEtickets extends JPlugin
{
	function plgHikashopEtickets(&$subject, $config){
		parent::__construct($subject, $config);
		if(!isset($this->params)){
			$plugin =& JPluginHelper::getPlugin('hikashop', 'etickets');
			jimport('joomla.html.parameter');
			$this->params = new JParameter( $plugin->params );
		}
    }
    function onAfterOrderUpdate(&$order,&$send_email){
             error_log($order->order_status);
             //if ($order->order_status = "confirmed") {
		     $class = hikashop_get('class.order');
		     $fullOrder= $class->loadFullOrder($order->order_id,true);
		     //error_log(var_export($fullOrder->products,true));
		     foreach($fullOrder->products as $product) {
			     //error_log(var_dump($product,true));
                             error_log($product->product_id);
                             $productClass = hikashop_get('class.product');
			     /*$catalogueProduct = $productClass->get($product->product_id);

			     error_log("#######################azezazeze");
			     error_log(var_export($catalogueProduct,true));$/
		     }

		
		//}
		return true;
    }
    function onAfterOrderDelete($elements){
    /*	if(!is_array($elements)){
			$elements = array($elements);
		}
		$database =& JFactory::getDBO();
		foreach($elements as $key => $val){
			$elements[$key] = $database->Quote($val);
		}
		$query='DELETE FROM '.hikashop_table('etickets').' WHERE etickets_order_id IN ('.implode(',',$elements).')';
		$database->setQuery($query);
		$database->query();*/
		return true;
    }
}
