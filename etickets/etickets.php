<?php
/**
 * @package		ETickets4Hikashop
 * @version		1.0.1
 * @hikashopVersion	1.5.8-2.0
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
		$this->debug=1;
		$this->database =& JFactory::getDBO();
		$query = 'SELECT product_id FROM '.hikashop_table('eticket_info');
		$this->database->setQuery($query);

		$this->eTicketProducts= $this->database->loadColumn();
		$lang = JFactory::getLanguage();
                $lang->load('plg_hikashop_etickets', dirname(__FILE__));

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
			$this->database->setQuery($query);
			$this->database->query();
		}
	}
	function getTicketListForOrder($order_id) {
		$query = 'select id FROM '.hikashop_table('etickets').' WHERE order_id='.$order_id;
		if ($this->debug) {error_log(var_export($query,true));}
		$this->database->setQuery($query);
		$ret=array();
		$result=$this->database->loadObjectList();
		if ($this->debug) {error_log("List:".var_export($result,true));}
		if ($result!=null) {
			foreach ($result as $row) {
			if ($this->debug) {error_log("id=".$row->id);}
			array_push($ret,$row->id);
			}
		}
		return $ret;
	}



	function onAfterOrderCreate(&$order,&$send_email){
		return $this->onAfterOrderUpdate($order,$send_email);
	}
	function onAfterOrderUpdate(&$order,&$send_email){
		if ($this->debug) {error_log("Order update");}
		$class = hikashop_get('class.order');
		$fullOrder= $class->loadFullOrder($order->order_id,true);
		if ($this->debug) {error_log(var_export($fullOrder->products,true));}

		foreach($fullOrder->products as $order_product) {
			// We need to know if the product is an electornic ticket
			$productClass=hikashop_get('class.product');
			if ($this->debug) {error_log("ProdID: $order_product->product_id, Products:$this->eTicketProducts");}
			if( !in_array($order_product->product_id,$this->eTicketProducts)){
				if ($this->debug) {error_log("not an ETicket");}
				continue;
			}
			// We have an electronic ticket !
			// Do the electronic tickets already exist ?
			$query = 'SELECT count(id) FROM '.hikashop_table('etickets').' WHERE order_product_id='.$order_product->order_product_id;
			$this->database->setQuery($query);
			if ($this->database->loadResult()>0) {
				if ($this->debug) {error_log("Tickets found");}
				// If they do and the order is not confirmed nor shipped, delete them
				if (!in_array($order->order_status, array( "confirmed","shipped")))  { 
					if ($this->debug) {error_log("Deletion");}
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
				if ($this->debug) {error_log("No ticket found");}
				// If they dont and the order is confirmed, create some
				if (in_array($order->order_status, array( "confirmed","shipped")))  {
					if ($this->debug) {error_log("Creation");}
					$orderProductQty=$order_product->order_product_quantity;
					$this->createTickets($order_product,$order->order_id);
					if ($this->debug) {error_log("ID : ".$order->order_id);}


				}
			}



		}
		return true;
	}
	function onAfterOrderDelete($elements) {
		if(!is_array($elements)){
			$elements = array($elements);
		}

		foreach ($elements as $elt) {
			if ($this->debug) {error_log("Deletion :".$elt);}
			$query = 'update '.hikashop_table('etickets').' set status=0 WHERE order_id='.$elt;
			$this->database->setQuery($query);
			$this->database->query();
		}
		return true;
	}
	function onBeforeProductListingLoad( & $filters, & $order, &$view) {
		if ($this->debug) {error_log("load");}
		$productId=JRequest::getVar("cid","");
		$db = JFactory::getDBO();
		$action=JRequest::getVar("task");
		if ($action=="et4hgetticketlist") {
			ob_clean();
			$format=JRequest::getVar("fmt","");
				$query = 'SELECT * FROM #__hikashop_product WHERE product_id='.$productId;
				$db->setQuery($query);
				$infos=$db->loadObject();
				$eventXml=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><event />');
?><?php
				$eventXml->addAttribute("product_id","$productId");
				$query = 'SELECT * FROM #__hikashop_eticket_info WHERE product_id='.$productId;
				$db->setQuery($query);
				$infos=$db->loadObject();
				$infosXml=$eventXml->addchild('info');
				$infosXml->addchild('place',$infos->address);
				$infosXml->addchild('date',$infos->eventdate);

				$query = 'SELECT * FROM #__hikashop_etickets WHERE product_id='.$productId;
				$db->setQuery($query);
				$tickets=$db->loadObjectList();
				$ticketsXml=$eventXml->addChild('tickets');
				$attributes=array();
				foreach($tickets[0] as $k=>$v) {
					$attributes[]=$k;
				}
				foreach ($tickets as $ticket) {

					$ticketXml=$ticketsXml->addChild('ticket');
					foreach($ticket as $k=>$v) {
						$ticketXml->addAttribute($k,$v);
					}


				}
			if($format=="xml") {
				header('Content-Disposition: attachment; filename="ticketList-'.$productId.'.xml"');
				header('Content-type: application/xml');
				echo $eventXml->asXML();
			}
			else if ($format=="html") {
				$document= JFactory::getDocument();
				JHTML::_('behavior.modal'); 
				$document->addStyleSheet('templates/system/css/system.css');
				$document->addStyleSheet('templates/'.JFactory::getApplication()->getTemplate().'/css/template.css');
				$head=$document->loadRenderer('head')->render();
				if ($this->debug) {error_log("html");}
				$xsl='<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
					<xsl:output
					method="xml"
					doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" 
					doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" />

				  <xsl:template match="/event">
				  <html>
				  <head>';
				  $xsl.=$head;
				  $xsl.='</head>

				  <body>
				        <h1><xsl:value-of select="info/name"/></h1>
					<ul><li>Date : <xsl:value-of select="info/date"/></li>
					<li>Place : <xsl:value-of select="info/place"/></li>
					</ul>
					<table class="adminlist">
					<tr>';

					foreach ($attributes as $attr) {
						$xsl.="<th>$attr</th>";
					}
					$xsl.='</tr>
				        <xsl:for-each select="tickets/ticket">
					<tr>';
					foreach ($attributes as $attr) {
						$xsl.="<td>";
						if ($attr=="order_id") {
							$xsl.='<xsl:element name="a">
							    <xsl:attribute name="target">blank</xsl:attribute>
							    <xsl:attribute name="href">
							    '.hikashop_completeLink('order&task=edit&cid[]=').'<xsl:value-of select="@'.$attr.'"/>
							    </xsl:attribute>
							    <xsl:value-of select="@'.$attr.'"/>
							    </xsl:element>';
						}
						else {
							$xsl.="<xsl:value-of select=\"@$attr\"/>";
						}
						$xsl.="</td>\n";
					}

					$xsl.='</tr>
					</xsl:for-each>
					</table>
					</body></html>
				  </xsl:template>

				</xsl:stylesheet>';


				
				$proc = new XSLTProcessor();
				$proc->importStyleSheet(new  SimpleXMLElement($xsl));


				echo $page.$proc->transformToXml($eventXml)."</html>"; 


			}
			exit;
		}
		else if ($action=="et4huploadxmlfile") {
			$document= JFactory::getDocument();
			JHTML::_('behavior.modal'); 
			$document->addStyleSheet('templates/system/css/system.css');
			$document->addStyleSheet('templates/'.JFactory::getApplication()->getTemplate().'/css/template.css');
			$head=$document->loadRenderer('head')->render();
			echo "<html><head>".$head."</head><body>";?>
			<?php if (!$_FILES){?>
				<form action="<?php echo $_SERVER['REQUEST_URI'] ;?>" method="post" enctype="multipart/form-data">
					<input type="file" name="et4hxmlfile" id="et4hxmlfile">
					<input type="submit" value="<?php echo JText::_("PLG_HIKASHOP_ETICKETS_UPLOADSEND");?>">
				</form>
				<?php }
			else {
				if ($_FILES["et4hxmlfile"]["error"] > 0)
				{
					echo "Error: " . $_FILES["et4hxmlfile"]["error"] . "<br>";
				}
				else
				{
					$xmlInput=new SimpleXMLElement(file_get_contents($_FILES["et4hxmlfile"]["tmp_name"]));
					$productFromXml=$xmlInput->xpath('/event');
					$productFromXml=(string)$productFromXml[0]->attributes()->product_id;
					if ($productFromXml != $productId ) {
						echo "<h1>".JText::_("PLG_HIKASHOP_ETICKETS_ERROR")."</h1>";
						echo JText::_("PLG_HIKASHOP_ETICKETS_WRONGPRODUCT");
					}
					else {
						$tickets=$xmlInput->xpath('/event/tickets/ticket');

						foreach ($tickets as $ticketXml) {
							$newId=(string) $ticketXml->attributes()->id;
							$newStatus=(string) $ticketXml->attributes()->status;
							$query="select id,status from ".hikashop_table('etickets')." where id='".$newId."' and product_id='".$productId."';";
							$db->setQuery($query);
							$eTicketInfo=$db->loadObject();
							if ($eTicketInfo) {
								if ($eTicketInfo->status != $newStatus) {
									$eTicketInfo->status= $newStatus;
									$db->updateObject(hikashop_table('etickets'),$eTicketInfo,'id',false);
								}
							}
							else {
								echo JText::_("PLG_HIKASHOP_ETICKETS_ERROR")." : ";
								echo JText::_("PLG_HIKASHOP_ETICKETS_NOTICKET")." ";
								echo $newId." ";
								echo JText::_("PLG_HIKASHOP_ETICKETS_FORPRODUCT").".<br>";
							}
						}
						echo JText::_("PLG_HIKASHOP_ETICKETS_UPLOADDONE");
						?>
							<br>
							<a href="index.php?option=com_hikashop&ctrl=product&task=et4hgetticketlist&fmt=html&cid=<?php echo $productId;?>" ><?php echo JText::_('PLG_HIKASHOP_ETICKETS_GETTICKETTABLE');?></a></li>
							<?php
					}
				}
			}


			exit;
		}
	}

	function onBeforeCategoryListingLoad (&$element,&$html) {
		$action=JRequest::getVar("action");
		if ($action=="showtable") {
			//ob_clean();
			if ($this->debug) {error_log("showtable");}
			return false;
		}
	}
	function onProductFormDisplay(&$element,&$html) {
		$query = 'SELECT * FROM '.hikashop_table('eticket_info').' WHERE product_id='.$element->product_id;
		$this->database->setQuery($query);
		$eTicketInfo=$this->database->loadObjectList();
		if (!isset($eTicketInfo)) {$eTicketInfo=array(null);}
		$view=new JView();
		$view->product=$element;
		if ($this->debug) {error_log(var_export($element,true));}
		$view->eTicketInfo=$eTicketInfo[0];
		$view->addTemplatePath(dirname(__FILE__) . '/tmpl/');
		$view->setLayout("eTickets4HikashopForm");
		$html[]=$view->loadTemplate();

		return true;
	}
	function onAfterProductUpdate (&$element) {
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
		if ($this->debug) {error_log(var_export($ticketList,true));}
		foreach($ticketList as $ticketId) {
			$attachObj=null;
			$attachObj->filename="Ticket-".$ticketId.".pdf";
			$attachObj->contentAsText=$this->createTicketFile($ticketId);
			$mailer->AddStringAttachment($attachObj->contentAsText,$attachObj->filename);
		}
		return true;



	}
	function createTicketFile ($eTicketID) {
		require_once(dirname(__FILE__).'/lib/tcpdf/config/lang/fra.php');
		if(!class_exists('TCPDF')) {
			require_once(dirname(__FILE__).'/lib/tcpdf/tcpdf.php');
		}
		$pdf=new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		if ($this->debug) {error_log("Ticket : $eTicketID");}
		$db = JFactory::getDBO();
		$query='SELECT a.*,order_product_name,order_product_code FROM '.hikashop_table('eticket_info').' AS a, '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE a.product_id=c.product_id AND c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$info=$this->database->loadObjectList();
		$info=$info[0];
		$query='SELECT order_product_price FROM '.hikashop_table('etickets').' AS b, '.hikashop_table('order_product').' AS c WHERE c.order_product_id=b.order_product_id AND b.id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$price=$this->database->loadResult();
		$query='SELECT tn FROM '.hikashop_table('etickets').' WHERE id=\''.$eTicketID."'";
		$this->database->setQuery($query);
		$tn=$this->database->loadResult();
		$view=new JView();
		if(file_exists(JPATH_ROOT."/images/etickets/".$info->order_product_code.".php")) {
			ob_start();
			include(JPATH_ROOT."/images/etickets/".$info->order_product_code.".php");
			$html= ob_get_contents();
			ob_end_clean();

		}
		else if(file_exists(JPATH_ROOT."/images/etickets/eticket.php")) {
			ob_start();
			include(JPATH_ROOT."/images/etickets/eticket.php");
			$html= ob_get_contents();
			ob_end_clean();
		}
		else {
			$view->addTemplatePath(dirname(__FILE__) . '/tmpl/');
			$view->setLayout("eticket");
			$html=$view->loadTemplate();
		}

		$html=str_replace('ET4H_ADDRESS',$info->address,$html);
		$html=str_replace('ET4H_EVENTDATE',$info->eventdate,$html);
		$html=str_replace('ET4H_PRICE',sprintf("%.2f",$price),$html);
		$html=str_replace('ET4H_TN',$tn,$html);
		$html=str_replace('ET4H_TICKETID',$eTicketID,$html);
		$config =& JFactory::getConfig();
		$html=str_replace('ET4H_SITE_NAME',$config->getValue("config.sitename"),$html);
		$html=str_replace('ET4H_EVENT_NAME',$info->order_product_name,$html);
		$tcpdfParams = $pdf->serializeTCPDFtagParameters(array($eTicketID, 'C128','','','','20','10' )); 
		$html=str_replace('ET4H_BARCODE','<tcpdf method="write1DBarcode" params="'.$tcpdfParams.'" />',$html);
		$tcpdfParams = $pdf->serializeTCPDFtagParameters(array($eTicketID, 'QRCODE,B','','','50','50' )); 
		$html=str_replace('ET4H_QRCODE','<tcpdf method="write2DBarcode" params="'.$tcpdfParams.'" />',$html);
		$pdf->AddPage();

		$pdf->writeHTML($html, true, false, true, false, '');


		return $pdf->Output('',S);

	}
	function onAfterOrderProductsListingDisplay(&$order, $where) {
		if ($where =='order_front_show') {
			$order_id=$order->order_id;
			if (! $order_id) {return true;}
			$ticketList=$this->getTicketListForOrder($order_id);
			if ($this->debug) {error_log(var_export($ticketList,true));}
			$eTicketID=JRequest::getVar("eTicketID");
			if ($eTicketID && in_array($eTicketID,$ticketList)) {
				ob_clean();
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="ETicket-'.$eTicketID.'.pdf"');
				echo $this->createTicketFile($eTicketID);

				exit;
			}
			if ($ticketList) {
				echo "<p> ".JText::_('PLG_HIKASHOP_ETICKETS_ORDER_HAS_ETICKETS').":";
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
