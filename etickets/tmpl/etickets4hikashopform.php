<?php
/**
 * @package		ETickets4Hikashop
 * @version		1.0.1
 * @hikashopVersion	1.5.8-2.0
 * @author		Thomas Bouffon - thomas.bouffon@gmail.com
 * @copyright		(C) . All rights reserved.
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');?>
<fieldset class="adminform hikashop_product_eticketinfo" id="htmlfieldset">
	<legend><?php echo JText::_('PLG_HIKASHOP_ETICKETS_FIELDSET');?></legend>
	<?php if (isset($this->product->product_id)) {?>
		<label for="productiseticket"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_ISETICKET');?></label>
			<?php if (!is_null($this->eTicketInfo)) {$isEticket=1; }?>
			<input type="checkbox" name="et4hproductiseticket"<?php if (isset($isEticket)) {echo "checked=\"checked\"";}?>></input><br>
			<?php foreach(array('address','eventdate') as $field) :?>
			<label for="eticket<?php echo $field;?>"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_'.strtoupper($field));?></label>
			<input type="text" name="et4heticket<?php echo $field;?>" value="<?php if (isset($isEticket)) {echo $this->eTicketInfo->$field;}?>"></input>
			<br>
			<?php endforeach ;?>
			<?php if (isset($isEticket)) : ?>
			<ul>

			<?php 
			if ($this->product->product_published && (empty($this->product->product_sale_end) || $this->product->product_sale_end>time())) { $productStillForSale=1;} else {$productStillForSale=0;}?>
				<?php if ($productStillForSale ==0) { ?>
					<li><a href="index.php?option=com_hikashop&ctrl=product&task=et4hgetticketlist&fmt=xml&cid=<?php echo $this->eTicketInfo->product_id;?>"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_GETTICKETLIST');?></a>
						<li><a href="index.php?option=com_hikashop&ctrl=product&task=et4huploadxmlfile&cid=<?php echo $this->eTicketInfo->product_id;?>" class="modal" rel="{handler: 'iframe', size: {x: 680, y: 370}}"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_UPLOADXMLFILE');?></a></li>
						<?php }
			else {echo "<li>".JText::_('PLG_HIKASHOP_ETICKETS_GETTICKETLIST')." (".JText::_('PLG_HIKASHOP_ETICKETS_NOTWHENPUBLISHED').")</li>";
				echo "<li>".JText::_('PLG_HIKASHOP_ETICKETS_UPLOADXMLFILE')." (".JText::_('PLG_HIKASHOP_ETICKETS_NOTWHENPUBLISHED').")</li>";}
		?>
			</li>
			<li><a href="index.php?option=com_hikashop&ctrl=product&task=et4hgetticketlist&fmt=html&cid=<?php echo $this->eTicketInfo->product_id;?>" class="modal" rel="{handler: 'iframe', size: {x: 680, y: 370}}"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_GETTICKETTABLE');?></a></li>

			</ul>
			<?php endif;?>
			<?php 
			}
			else {echo JText::_('PLG_HIKASHOP_ETICKETS_ONLYWHENSAVED');}?>
				 
</fieldset>

