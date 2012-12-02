<?php

$action=JRequest::getVar("action");
$productId=JRequest::getVar("product_id","");
if ($action=="getticketlist") {

	$db = JFactory::getDBO();
	$query = 'SELECT * FROM #__hikashop_product WHERE product_id='.$productId;
	$db->setQuery($query);
	$infos=$db->loadObject();
	#header('Content-Disposition: attachment; filename="ticketList-'.$productId.'.xml"');
	#header('Content-type: application/xml');

	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo "<event>\n";
	echo "\t<info>\n";
	echo "\t\t<name>".$infos->product_name."</name>\n";
	$query = 'SELECT * FROM #__hikashop_eticket_info WHERE product_id='.$productId;
	$db->setQuery($query);
	$infos=$db->loadObject();
	echo "\t\t<place>".$infos->address."</place>\n";
	echo "\t\t<date>".$infos->eventdate."</date>\n";
	echo "\t</info>\n";
	$query = 'SELECT * FROM #__hikashop_etickets WHERE product_id='.$productId;
	$db->setQuery($query);
	$tickets=$db->loadObjectList();
	echo "\t<tickets>\n";
	foreach ($tickets as $ticket) {
		echo "\t\t<ticket id=\"".$ticket->id."\" status=\"".$ticket->status."\"></ticket>\n";

	}
echo "\t</tickets>\n";
echo "</event>\n";
}
?>
