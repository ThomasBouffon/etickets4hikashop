<?php
/**
 * @package		ETickets4Hikashop
 * @version		0.1
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
error_log("Import");
defined('_JEXEC') or die('Restricted access');

JLoader::register('OrderViewOrder', JPATH_BASE . '/components/com_hikashop/views/order/view.html.php');

/** Override this Model */
class Order2ViewOrder2 extends OrderViewOrder{

	public function display($tpl = null) {
		$this->addTemplatePath(__DIR__ . '/tmpl/');
		parent::display($tpl);
	}
}

