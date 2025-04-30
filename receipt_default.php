<?php /* $this->load->view("partial/header"); */ ?>

<?php
if(isset($error_message))
{
	echo "<div class='alert alert-dismissible alert-danger'>".$error_message."</div>";
	exit;
}
?>

<?php if(!empty($customer_email)): ?>
<script type="text/javascript">
$(document).ready(function()
{
	var send_email = function()
	{
		$.get('<?php echo site_url() . "sales/send_pdf/" . $sale_id_num; ?>',
			function(response)
			{
				$.notify({ message: response.message }, { type: response.success ? 'success' : 'danger'});
			}, 'json'
		);
	};

	$("#show_email_button").click(send_email);

	<?php if(!empty($email_receipt)): ?>
		send_email();
	<?php endif; ?>
});
</script>
<?php endif; ?>

<?php $this->load->view('partial/print_receipt', array('print_after_sale'=>$print_after_sale, 'selected_printer'=>'invoice_printer')); ?>

<div id="page-wrap">
    <div id="block1" style="display: flex; justify-content: space-between; align-items: flex-start;">
	<div id="store-info">
		<?php if($this->Appconfig->get('company_logo') != '') { ?>
			<img id="image" src="<?php echo base_url('uploads/' . $this->Appconfig->get('company_logo')); ?>" alt="company_logo" />
		<?php } ?>
		<div id="company_name"><?php echo $this->config->item('company'); ?></div>
		<div id="company-contact"><?php echo nl2br($company_info); ?></div>
	</div>

	<?php if(isset($customer)) { ?>
		<div id="customer-info" style="text-align: right;">
			<?php echo nl2br($customer_info); ?>
		</div>
	<?php } ?>
</div>

	<div id="block2">
		
		<table id="meta">
			<tr>
				<td class="meta-head">Sale Date</td>
				<td><?php echo $transaction_date; ?></td>
			</tr>
			<tr>
				<td class="meta-head">Total</td>
				<td><?php echo to_currency($total); ?></td>
			</tr>
		</table>
	</div>

	<table id="items" style="width:100%;">
  <?php $col_count = ($include_hsn ? 6 : 5); ?>

		<tr>
			<th style="width:15%;"><?php echo $this->lang->line('sales_item_number'); ?></th>
			<?php if($include_hsn) { ?>
				<th style="width:15%;"><?php echo $this->lang->line('sales_hsn'); ?></th>
			<?php } ?>
			<th style="width:40%;">Item Name</th> <!-- Made Item Name wider -->
			<th style="width:15%;"><?php echo $this->lang->line('sales_quantity'); ?></th>
			<th style="width:15%;"><?php echo $this->lang->line('sales_price'); ?></th>
			<th style="width:15%;"><?php echo $this->lang->line('sales_total'); ?></th>
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
					<td><?php echo to_currency($item['price']); ?></td>
					<td style='text-align:right;'><?php echo to_currency($item['discounted_total']); ?></td>
				</tr>
				<?php
				if($item['is_serialized'])
				{
				?>
					<tr class="item-row">
						<td class="item-description" colspan="<?php echo $col_count - 1; ?>"></td>
						<td style='text-align:center;'><?php echo $item['serialnumber']; ?></td>
					</tr>
				<?php
				}
			}
		}
		?>

		<tr>
			<td class="blank" colspan="<?php echo $col_count; ?>" align="center"><?php echo '&nbsp;'; ?></td>
		</tr>

		<tr>
			<td colspan="<?php echo $col_count - 3; ?>" class="blank-bottom"> </td>
			<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_sub_total'); ?></td>
			<td class="total-value" id="subtotal"><?php echo to_currency($subtotal); ?></td>
		</tr>

		<?php foreach($taxes as $tax_group_index=>$tax): ?>
			<tr>
				<td colspan="<?php echo $col_count - 3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo (float)$tax['tax_rate'] . '% ' . $tax['tax_group']; ?></td>
				<td class="total-value" id="taxes"><?php echo to_currency_tax($tax['sale_tax_amount']); ?></td>
			</tr>
		<?php endforeach; ?>

		<tr>
			<td colspan="<?php echo $col_count - 3; ?>" class="blank"> </td>
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
				<td colspan="<?php echo $col_count - 3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo $splitpayment[0]; ?></td>
				<td class="total-value" id="paid"><?php echo to_currency(abs($payment['payment_amount'])) . ' Paid'; ?></td>
			</tr>
		<?php } ?>

		<?php if(isset($cur_giftcard_value) && $show_giftcard_remainder): ?>
			<tr>
				<td colspan="<?php echo $col_count - 3; ?>" class="blank"> </td>
				<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_giftcard_balance'); ?></td>
				<td class="total-value" id="giftcard"><?php echo to_currency($cur_giftcard_value); ?></td>
			</tr>
		<?php endif; ?>

    <?php
$has_cash_payment = false;
foreach($payments as $payment)
{
	if (stripos($payment['payment_type'], 'Cash') !== false)
	{
		$has_cash_payment = true;
		break;
	}
}
?>

<?php if($has_cash_payment && $amount_change > 0): ?>
<tr>
	<td colspan="<?php echo $col_count - 3; ?>" class="blank"> </td>
	<td colspan="2" class="total-line"><?php echo $this->lang->line('sales_change_due'); ?></td>
	<td class="total-value" id="change"><?php echo to_currency($amount_change); ?></td>
</tr>
<?php endif; ?>


	</table>

	<div id="terms">
		<div id="sale_return_policy">
      <div style='padding:2%;'><?php echo nl2br($this->config->item('return_policy')); ?></div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(window).on("load", function()
{
	if(window.jsPrintSetup)
	{
		<?php if(!$this->Appconfig->get('print_header')) { ?>
			jsPrintSetup.setOption('headerStrLeft', '');
			jsPrintSetup.setOption('headerStrCenter', '');
			jsPrintSetup.setOption('headerStrRight', '');
		<?php } ?>
		<?php if(!$this->Appconfig->get('print_footer')) { ?>
			jsPrintSetup.setOption('footerStrLeft', '');
			jsPrintSetup.setOption('footerStrCenter', '');
			jsPrintSetup.setOption('footerStrRight', '');
		<?php } ?>
	}
});
</script>

<?php /* $this->load->view("partial/footer"); */ ?>
