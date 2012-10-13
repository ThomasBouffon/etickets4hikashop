<fieldset class="adminform hikashop_product_eticketinfo" id="htmlfieldset">
	<legend><?php echo JText::_('HIKA_ET_FIELDSET');?></legend>
	<label for="productiseticket"><?php echo JText::_('HIKA_ET_ISETICKET');?></label>
	<?php if (!is_null($this->eTicketInfo)) {$isEticket=1; }?>
	<input type="checkbox" name="et4hproductiseticket"<?php if (isset($isEticket)) {echo "checked=\"checked\"";}?>></input><br>
	<?php foreach(array('address','eventdate') as $field) :?>
	<label for="eticket<?php echo $field;?>"><?php echo JText::_('HIKA_ET_'.strtoupper($field));?></label>
	<input type="text" name="et4heticket<?php echo $field;?>" value="<?php if (isset($isEticket)) {echo $this->eTicketInfo->$field;}?>"></input>
	<br>
	<?php endforeach ;?>

</fieldset>

