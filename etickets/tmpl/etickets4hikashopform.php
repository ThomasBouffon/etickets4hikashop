<?php
/**
 * @package		ETickets4Hikashop
 * @version		1.0.5
 * @hikashopVersion	1.5.8-2.2
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
			<input type="checkbox" name="et4hproductiseticket"<?php if (isset($isEticket)) {echo "checked=\"checked\" ";}; if ($this->product->product_parent_id !=0 ) {echo 'readonly="readonly"';}?>></input><br>
			<label for="et4heticketaddress"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_ADDRESS');?></label>
			<input type="text" name="et4heticketaddress" value="<?php if (isset($isEticket)) {echo $this->eTicketInfo->address;}?>"></input>
			<?php if ($this->product->product_parent_id ==0 ) :?>
			<input type="checkbox" name="et4heticketaddress_forall"></input>
			<label for="et4heticketeventaddress_forall"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_FORALLVARIANTS');?></label>
			<?php endif;?>
			<br>
			<label for="et4heticketeventdate"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_EVENTDATE');?></label>
			<?php echo JHTML::_('calendar', hikashop_getDate((@$this->eTicketInfo->eventdate?@$this->eTicketInfo->eventdate:''),'%Y-%m-%d'), 'et4heticketeventdate','et4heticketeventdate','%Y-%m-%d','style="max-width:7em"');?>
			<?php if ($this->product->product_parent_id ==0 ) :?>
			<input type="checkbox" name="et4heticketeventdate_forall"></input>
			<label for="et4heticketeventdate_forall"><?php echo JText::_('PLG_HIKASHOP_ETICKETS_FORALLVARIANTS');?></label>
			<?php endif;?>
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

