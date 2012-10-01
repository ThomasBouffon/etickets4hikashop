<?php
/**
 * @package		ETickets4Hikashop
 * @version		0.1
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');

JLoader::register('orderController', JPATH_BASE . '/components/com_hikashop/controllers/order.php');

?>
<?php
class order2Controller extends orderController{
	function __construct($config = array(),$skip=false){

		error_log("construct");
		parent::__construct($config,$skip);
		$this->display[]='getticket';
	}

	function getticket () {
		if($this->_check()){
			JRequest::setVar('layout', 'getticket');
			return parent::display();
		}
	}
}
