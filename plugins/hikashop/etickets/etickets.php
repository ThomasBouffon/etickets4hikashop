<?php
/**
 * @package		ETickets4Hikashop
 * @version		0.1
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
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
		$this->database =& JFactory::getDBO();
		$query = 'SELECT product_id FROM '.hikashop_table('eticket_info');
		$this->database->setQuery($query);

		$this->eTicketProducts= $this->database->loadColumn();
		#error_log("IDs:".var_export($this->eTicketProducts,true));
	}
	function createTickets(&$order_product,&$order_id){
		for ($i=0;$i<$order_product->order_product_quantity;$i++) {
			$idExists=true;
			while ($idExists) {
				$id=uniqid("",false);
				$query = 'select id FROM '.hikashop_table('etickets').' WHERE id='.$id;
				$this->database->setQuery($query);
				$idExists=$this->database->loadResult();
			}
			$query = 'insert into '.hikashop_table('etickets').' (id,product_id,order_product_id,order_id,status) values ('."'$id'".', '.$order_product->product_id.', '.$order_product->order_product_id.', '.$order_id.', 1);';
			#error_log("Insert $i:".$query);
			$this->database->setQuery($query);
			$this->database->query();
		}
	}
	function getTicketListForOrder($order_id) {
		$query = 'select id FROM '.hikashop_table('etickets').' WHERE order_id='.$order_id;
		$this->database->setQuery($query);
		$ret=array();
		$result=$this->database->loadObjectList();
		error_log("xxxx".var_export($result,true));
		if ($result!=null) {
			foreach ($result as $row) {
			error_log("id=".$row->id);
			array_push($ret,$row->id);
			}
		}
		return $ret;
	}



	function onAfterOrderCreate(&$order,&$send_email){
		return $this->onAfterOrderUpdate($order,$send_email);
	}
	function onAfterOrderUpdate(&$order,&$send_email){
		error_log("Order update");
		$class = hikashop_get('class.order');
		$fullOrder= $class->loadFullOrder($order->order_id,true);
		foreach($fullOrder->products as $order_product) {
			// We need to know if the product is an electornic ticket
			$productClass=hikashop_get('class.product');
			if( !in_array($order_product->product_id,$this->eTicketProducts)){
				error_log("not an ETicket");
				continue;
			}
			// We have an electronic ticket !
			// Do the electronic tickets already exist ?
			$query = 'SELECT count(id) FROM '.hikashop_table('etickets').' WHERE order_product_id='.$order_product->order_product_id;
			$this->database->setQuery($query);
			if ($this->database->loadResult()>0) {
				error_log("Tickets found");
				// If they do and the order is not confirmed nor shipped, delete them
				if (!in_array($order->order_status, array( "confirmed","shipped")))  { 
					error_log("Deletion");
					$query = 'update '.hikashop_table('etickets').' set status=0 WHERE order_product_id='.$order_product->order_product_id;
					$this->database->setQuery($query);
					$this->database->query();
				}
				else {
					$query = 'update '.hikashop_table('etickets').' set status=1 WHERE order_product_id='.$order_product->order_product_id;
					$this->database->setQuery($query);
					$this->database->query();
				}
			}
			else {
				error_log("No ticket found");
				// If they dont and the order is confirmed, create some
				if (in_array($order->order_status, array( "confirmed","shipped")))  {
					error_log("Creation");
					$orderProductQty=$order_product->order_product_quantity;
					$this->createTickets($order_product,$order->order_id);
					error_log("ID : ".$order->order_id);


				}
			}



			return true;
		}
	}
	function onAfterOrderDelete($elements) {
		if(!is_array($elements)){
			$elements = array($elements);
		}

		foreach ($elements as $elt) {
			error_log("Deletion :".$elt);
			$query = 'update '.hikashop_table('etickets').' set status=0 WHERE order_id='.$elt;
			$this->database->setQuery($query);
			$this->database->query();
		}
		return true;
	}
	function onProductFormDisplay(&$element,&$html) {
		$query = 'SELECT * FROM '.hikashop_table('eticket_info').' WHERE product_id='.$element->product_id;
		$this->database->setQuery($query);
		error_log($query);
		$eTicketInfo=$this->database->loadObjectList();
		if (!isset($eTicketInfo)) {$eTicketInfo=array(null);}
		$view=new JView();
		$view->eTicketInfo=$eTicketInfo[0];
		$view->addTemplatePath(__DIR__ . '/tmpl/');
		$view->setLayout("eTickets4HikashopForm");
		$html[]=$view->loadTemplate();


		return true;
	}
	function onAfterProductUpdate (&$element) {
		error_log("AfterProductUpdate");
		if (JFactory::getApplication()->getName() != "administrator") {
			return true;
		}
		$query = 'SELECT * FROM '.hikashop_table('eticket_info').' WHERE product_id='.$element->product_id;
		$this->database->setQuery($query);
		$wasEticket=0;
		if(!is_null($this->database->loadResult())) {
			$wasEticket=1;
			$element->eTicketInfo=$this->database->loadObjectList();
			$element->eTicketInfo=$element->eTicketInfo[0];
		}
		if (!is_null($element->eTicketInfo) && JRequest::getVar("et4hproductiseticket",'') != "on" ) {
			$query = 'DELETE FROM '.hikashop_table('eticket_info').' WHERE product_id='.$element->product_id;
			$this->database->setQuery($query);
			$this->database->query();

		}
		if (JRequest::getVar("et4hproductiseticket",'') == "on" ) {

			$action="update";
			if (is_null($element->eTicketInfo)) {
				$element->eTicketInfo=new stdClass(); 
				$action="insert";
				$element->eTicketInfo->product_id=$element->product_id;

			}
			foreach(array('address','eventdate') as $field) {
				$element->eTicketInfo->$field=JRequest::getVar("et4heticket$field",null);
			}
			if ($action=="update") {
				$this->database->updateObject(hikashop_table('eticket_info'),$element->eTicketInfo,'product_id',true);
			}
			else {
				$this->database->insertObject(hikashop_table('eticket_info'),$element->eTicketInfo);
			}

		}
		return true;
	}
	function onBeforeMailSend(&$mail, &$mailer) {
		$order_id=$mail->data->order_id;
		if (! $order_id) {return true;}
		$ticketList=$this->getTicketListForOrder($order_id);
		error_log(var_export($ticketList,true));
		foreach($ticketList as $ticketId) {
			$attachObj=null;
			$attachObj->filename="Ticket-".$ticketId.".pdf";
			$attachObj->contentAsText=$this->createTicketFile($ticketId);
			$mailer->AddStringAttachment($attachObj->contentAsText,$attachObj->filename);
		}
		return true;



	}
	function createTicketFile ($eTicketID) {
		require_once(__DIR__.'/lib/tcpdf/config/lang/fra.php');
		require_once(__DIR__.'/lib/tcpdf/tcpdf.php');
		$pdf=new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		error_log("Ticket : $eTicketID");
		$db = JFactory::getDBO();
		$query='SELECT a.*,order_product_name FROM '.hikashop_table('eticket_info').' AS a, '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE a.product_id=c.product_id AND c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$info=$this->database->loadObjectList();
		$info=$info[0];
		$query='SELECT order_product_price FROM '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$price=$this->database->loadResult();
		$query='SELECT tn FROM '.hikashop_table('etickets').' WHERE id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$tn=$this->database->loadResult();
		$query = "SELECT config_value FROM #__hikashop_eticket_config WHERE config_key='codetype'";
		$db->setQuery($query);
		$codeType=$db->loadResult();
		$view=new JView();
		$view->addTemplatePath(__DIR__ . '/tmpl/');

		$view->setLayout("eticket");
		$html=$view->loadTemplate();
		$html=str_replace('ET4H_ADDRESS',$info->address,$html);
		$html=str_replace('ET4H_EVENTDATE',$info->eventdate,$html);
		$html=str_replace('ET4H_PRICE',sprintf("%.2f",$price),$html);
		$html=str_replace('ET4H_TN',$tn,$html);
		$config =& JFactory::getConfig();
		$html=str_replace('ET4H_SITE_NAME',$config->getValue("config.sitename"),$html);
		$html=str_replace('ET4H_EVENT_NAME',$info->order_product_name,$html);
		if ($codeType == "barcode") {
			$tcpdfParams = $pdf->serializeTCPDFtagParameters(array($eTicketID, 'C128','','','120','30' )); 
			$html=str_replace('ET4H_BARCODE','<tcpdf method="write1DBarcode" params="'.$tcpdfParams.'" />',$html);
		}
		else if ($codeType == "qrcode") {
			$tcpdfParams = $pdf->serializeTCPDFtagParameters(array($eTicketID, 'QRCODE,B','','','50','50' )); 
			$html=str_replace('ET4H_BARCODE','<tcpdf method="write2DBarcode" params="'.$tcpdfParams.'" />',$html);
		}
		$pdf->AddPage();

		$pdf->writeHTML($html, true, false, true, false, '');


		return $pdf->Output('',S);

	}
	function onAfterOrderProductsListingDisplay(&$order, $where) {
		if ($where =='order_front_show') {
			$order_id=$order->order_id;
			if (! $order_id) {return true;}
			$ticketList=$this->getTicketListForOrder($order_id);
			$eTicketID=JRequest::getVar("eTicketID");
			if ($eTicketID && in_array($eTicketID,$ticketList)) {
				ob_clean();
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="ETicket-'.$eTicketID.'.pdf"');
				echo $this->createTicketFile($eTicketID);

				exit;
			}
			error_log(var_export($ticketList,true));
			if ($ticketList) {
				echo "<p>This order contains electronic tickets :";
				echo "<ul>";
				foreach($ticketList as $key=>$ticketId) {
					echo '<li><a target=blank href='.hikashop_completeLink('order&task=show&cid='.$order_id.'&eTicketID='.$ticketId).'>Ticket #'.$key.'</a></li>';
				}
				echo "<ul></p>";
			}
		}

		
		return true;
	}

}
