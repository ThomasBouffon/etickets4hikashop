<?php
/**
 * @package		ETickets4Hikashop
 * @version		0.1
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
#$codeType="barcode";
$codeType="qrcode";
defined('_JEXEC') or die('Restricted access');
?>
<?php
$order_id = hikashop_getCID('order_id');
$class = hikashop_get('class.order');
$order = $class->loadFullOrder($order_id);
header('Content-type: application/pdf');
#error_log(var_export($order,true));
$eTicketID=JRequest::getVar('eTicketID');
header('Content-Disposition: attachment; filename="ETicket-'.$eTicketID.'.pdf"');
echo $class->createTicketFile($eTicketID);
exit;
?>
