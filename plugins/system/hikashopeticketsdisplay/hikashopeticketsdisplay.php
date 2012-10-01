<?php
/**
 * @package		ETickets4Hikashop
 * @version		0.1
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
class plgSystemHikashopeticketsdisplay extends JPlugin
{
	function onAfterRoute()
	{
		JLoader::register('hikashopOrderClass', __DIR__ . '/com_hikashop/classes/order.php');
		JLoader::register('hikashopMailClass', __DIR__ . '/com_hikashop/classes/mail.php');
		/** Set criteria for the View you want to override */
		if (JFactory::getApplication()->getName() == 'site'
				&& JFactory::getApplication()->input->get('option') == 'com_hikashop'
				&& JFactory::getApplication()->input->get('ctrl') == 'order'
				&& (JFactory::getApplication()->input->get('task') == 'show' || JFactory::getApplication()->input->get('task') == 'getticket')
		   ) {
			JRequest::setVar('ctrl', 'order2');
			JLoader::register('Order2ViewOrder2', __DIR__ . '/com_hikashop/views/order2/Order2ViewOrder2.php');
			JLoader::register('order2Controller', __DIR__ . '/com_hikashop/controllers/order2.php');


		}
	}
}
