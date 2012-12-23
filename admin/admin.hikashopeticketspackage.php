<?php

$action=JRequest::getVar("action");
$productId=JRequest::getVar("product_id","");
$db = JFactory::getDBO();
if ($action=="getticketlist") {
	$query = 'SELECT * FROM #__hikashop_product WHERE product_id='.$productId;
	$db->setQuery($query);
	$infos=$db->loadObject();
	header('Content-Disposition: attachment; filename="ticketList-'.$productId.'.xml"');
	header('Content-type: application/xml');

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
exit;
}
else if ($action == "saveconfig" ) {
	$newCodeType=JRequest::getVar("codetype");
	$query = "SELECT * FROM #__hikashop_eticket_config WHERE config_key='codetype'";
	$db->setQuery($query);
	if ($db->loadObject()) { 
		$query = "DELETE FROM #__hikashop_eticket_config WHERE config_key='codetype'";
		$db->setQuery($query);
		$db->query();
	}
	$query = "INSERT into #__hikashop_eticket_config (config_key,config_value) values ('codetype','". $newCodeType ."');";
	$db->setQuery($query);
	$db->query();


}
$query = "SELECT * FROM #__hikashop_eticket_config WHERE config_key='codetype'";
$db->setQuery($query);
$conf=$db->loadObject();

echo '<form action="index.php">';
echo '<input type="hidden" name="option" value="com_hikashopeticketspackage">';
echo '<input type="hidden" name="action" value="saveconfig">';
echo '<fieldset class="adminform etickets4hikashop_config" id="htmlfieldset">';
echo '<label for="codetype_barcode">Barcode</label> <input id="codetype_barcode" type="radio" name="codetype" value="barcode" ';
	error_log(var_export($conf,true));
if ($conf->config_value == 'barcode') { echo 'checked="checked"'; }
echo '>';
echo '<label for="codetype_qrcode">QRCode</label> <input id="codetype_qrcode" type="radio" name="codetype" value="qrcode" ';
if ($conf->config_value == 'qrcode') { echo 'checked="checked"'; }
echo '>';
echo '<br><input type="submit" value="submit"></input>';
echo '</fieldset>';
echo '</form>';
?>
