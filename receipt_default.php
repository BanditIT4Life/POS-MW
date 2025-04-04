<div id="page-wrap">
	<div id="block1">
		<div id="customer-title">
			<?php
			if(isset($customer))
			{
			?>
				<div id="customer"><?php echo nl2br($customer_info) ?></div>
				<div style='padding:0%;'><?php echo empty($comments) ? '' : $this->lang->line('') . ' ' . $comments; ?></div>
			<?php
			}
			?>
		</div>
		<div id="logo">
	<div id="company_name"><?php echo $this->config->item('company'); ?></div>
</div>

	</div>

	<div id="block2">
		<div id="company-title"><?php echo nl2br($company_info) ?></div>
		<table id="meta">
			<tr>
				<td class="meta-head"><?php echo $this->lang->line('common_date'); ?></td>
				<td><?php echo $transaction_date; ?></td>
			</tr>
			<tr>
				<td class="meta-head"><?php echo $this->lang->line('sales_invoice_total'); ?></td>
				<td><?php echo to_currency($total); ?></td>
			</tr>
		</table>
	</div>

	<table id="items">
		<tr>
			<th><?php echo $this->lang->line('sales_item_number'); ?></th>
			<?php
				$invoice_columns = 6;
				if($include_hsn)
				{
					$invoice_columns += 1;
					?>
					<th><?php echo $this->lang->line('sales_hsn'); ?></th>
					<?php
				}
			?>
			<th><?php echo $this->lang->line('sales_item_name'); ?></th>
			<th><?php echo $this->lang->line('sales_quantity'); ?></th>
			<th><?php echo $this->lang->line('sales_price'); ?></th>
			<th><?php echo $this->lang->line(''); ?></th>
			<?php
			if($discount > 0)
			{
				$invoice_columns += 1;
				?>
				<th><?php echo $this->lang->line('sales_customer_discount'); ?></th>
			<?php
			}
			?>
			<th><?php echo $this->lang->line('sales_total'); ?></th>
		</tr>

		<?php
		foreach($cart as $line=>$item)
		{
			if($item['print_option'] == PRINT_YES)
			{
			?>
				<tr class="item-row">
					<td><?php echo $item['item_number']; ?></td>
					<?php if($include_hsn): ?>
						<td style='text-align:center;'><?php echo $item['hsn_code']; ?></td>
					<?php endif; ?>
					<td class="item-name"><?php echo ($item['is_serialized'] || $item['allow_alt_description']) && !empty($item['description']) ? $item['description'] : $item['name'] . ' ' . $item['attribute_values']; ?></td>
					<td style='text-align:center;'><?php echo to_quantity_decimals($item['quantity']); ?></td>
					<td><?php echo to_currency($item['discounted_total'] / $item['quantity']); ?></td>
					<td style='text-align:center;'><?php echo ($item['discount_type']==FIXED)?to_currency($item['']):to_decimals($item['']) . '';?></td>
					<?php if($discount > 0): ?>
					<td style='text-align:center;'><?php echo to_currency($item['discounted_total'] / $item['quantity']); ?></td>
					<?php endif; ?>
					<td style='border-right: solid 1px; text-align:right;'><?php echo to_currency($item['discounted_total']); ?></td>
				</tr>
				<?php
				if($item['is_serialized'])
				{
				?>
					<tr class="item-row">
						<td class="item-description" colspan="<?php echo $invoice_columns-1; ?>"></td>
						<td style='text-align:center;'><?php echo $item['serialnumber']; ?></td>
					</tr>
				<?php
				}
			}
		}
		?>

		<tr>
			<td class="blank" colspan="<?php echo $invoice_columns; ?>" align="center"><?php echo '&nbsp;'; ?></td>
		</tr>

		<tr>
			<td colspan="<?php echo $invoice_columns-3; ?>" class="blank-bottom"> </td>
			<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_sub_total'); ?></td>
			<td class="total-value" id="subtotal"><?php echo to_currency($subtotal); ?></td>
		</tr>

		<?php
		foreach($taxes as $tax_group_index=>$tax)
		{
		?>
			<tr>
				<td colspan="<?php echo $invoice_columns-3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo (float)$tax['tax_rate'] . '% ' . $tax['tax_group']; ?></td>
				<td class="total-value" id="taxes"><?php echo to_currency_tax($tax['sale_tax_amount']); ?></td>
			</tr>
		<?php
		}
		?>

		<tr>
			<td colspan="<?php echo $invoice_columns-3; ?>" class="blank"> </td>
			<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_total'); ?></td>
			<td class="total-value" id="total"><?php echo to_currency($total); ?></td>
		</tr>

		<?php
		$only_sale_check = FALSE;
		$show_giftcard_remainder = FALSE;
		foreach($payments as $payment_id=>$payment)
		{
			$only_sale_check |= $payment['payment_type'] == $this->lang->line('sales_check');
			$splitpayment = explode(':', $payment['payment_type']);
			$show_giftcard_remainder |= $splitpayment[0] == $this->lang->line('sales_giftcard');
		?>
			<tr>
				<td colspan="<?php echo $invoice_columns-3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo $splitpayment[0]; ?></td>
				<td class="total-value" id="paid"><?php echo to_currency( $payment['payment_amount'] ) . " Paid"; ?></td>
			</tr>
		<?php
		}

		if(isset($cur_giftcard_value) && $show_giftcard_remainder)
		{
		?>
			<tr>
				<td colspan="<?php echo $invoice_columns-3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_giftcard_balance'); ?></td>
				<td class="total-value" id="giftcard"><?php echo to_currency($cur_giftcard_value); ?></td>
			</tr>
			<?php
		}

		if(!empty($payments))
		{
		?>
		<?php
		}
		?>

	</table>

	<div id="terms">
		<div id="sale_return_policy">
			<div style='padding:2%;'><?php echo nl2br($this->config->item('return_policy')); ?></div>
		</div>

		</div>
	</div>
</div>

<script type="text/javascript">
$(window).on("load", function()
{
	// install firefox addon in order to use this plugin
	if(window.jsPrintSetup)
	{
		<?php if(!$this->Appconfig->get('print_header'))
		{
		?>
			// set page header
			jsPrintSetup.setOption('headerStrLeft', '');
			jsPrintSetup.setOption('headerStrCenter', '');
			jsPrintSetup.setOption('headerStrRight', '');
		<?php
		}

		if(!$this->Appconfig->get('print_footer'))
		{
		?>
			// set empty page footer
			jsPrintSetup.setOption('footerStrLeft', '');
			jsPrintSetup.setOption('footerStrCenter', '');
			jsPrintSetup.setOption('footerStrRight', '');
		<?php
		}
		?>
	}
});
</script>

<?php $this->load->view("partial/footer"); ?>