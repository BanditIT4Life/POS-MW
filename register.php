<?php $this->load->view("partial/header"); ?>

<?php
if(isset($error))
{
	echo "<div class='alert alert-dismissible alert-danger'>".$error."</div>";
}

if(!empty($warning))
{
	echo "<div class='alert alert-dismissible alert-warning'>".$warning."</div>";
}

if(isset($success))
{
	echo "<div class='alert alert-dismissible alert-success'>".$success."</div>";
}
?>

<div id="register_wrapper">












<!-- ======================= Quick Add   Section 1 (Register header UI) ======================= -->
<?php
  $qa_can_manage = isset($can_manage) ? (bool)$can_manage : true;
?>

<style>
  /* Toolbar shell: light grey like other boxes */
  #qa_tabs_bar{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:10px 12px;margin:10px 0 14px;
    background:#f5f5f5;                 /* grey background */
    border:1px solid #e5e7eb;           /* subtle border */
    border-radius:8px;
  }

  /* Tabs: boxed / pill look with bold black text */
  #qa_tabs_ul{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0}
  #qa_tabs_ul>li{list-style:none}
  #qa_tabs_ul>li>a{
    display:inline-block;
    padding:6px 12px;
    border:1px solid #d1d5db;
    border-radius:10px;
    background:#f1f5f9;                 /* light grey tab tile */
    color:#111827;                       /* near-black text */
    font-weight:700;                     /* bold */
    text-decoration:none !important;
    line-height:1.2;
    transition:background .15s,border-color .15s,color .15s,box-shadow .15s;
  }
  #qa_tabs_ul>li>a:hover{
    background:#e5e7eb;                 /* slightly darker on hover */
    border-color:#cbd5e1;
    color:#111827;
  }
  #qa_tabs_ul>li>a:focus{
    outline:none;
    box-shadow:0 0 0 3px rgba(17,24,39,.15); /* focus ring */
  }

  /* Active tab (Section 2 toggles .qa-active on <li>) */
  #qa_tabs_ul>li.qa-active>a{
    background:#e5e7eb;                 /* active = darker grey */
    border-color:#94a3b8;
    color:#111827 !important;           /* black, bold */
    box-shadow:inset 0 1px 0 rgba(255,255,255,.35);
  }

  /* Settings button: match grey + bold black text */
  #qa_settings_btn.btn.btn-default.btn-sm{
    border-radius:8px;
    border-color:#d1d5db;
    background:#f1f5f9;
    color:#111827;
    font-weight:700;
  }
  #qa_settings_btn.btn.btn-default.btn-sm:hover{
    background:#e5e7eb;
    border-color:#cbd5e1;
    color:#111827;
  }
</style>

<div id="qa_tabs_bar" data-can-manage="<?php echo $qa_can_manage ? '1':'0'; ?>">
  <ul id="qa_tabs_ul" class="nav nav-pills"><!-- filled by Section 2 JS --></ul>

  <?php if ($qa_can_manage): ?>
    <button id="qa_settings_btn" type="button" class="btn btn-default btn-sm" aria-controls="qa_settings_modal">
      <span class="glyphicon glyphicon-cog"></span> Settings
    </button>
  <?php endif; ?>
</div>

<!-- CSRF tokens consumed by Sections 2/3 -->
<input type="hidden" id="qa_csrf_name" value="<?php echo $this->security->get_csrf_token_name(); ?>">
<input type="hidden" id="qa_csrf_hash" value="<?php echo $this->security->get_csrf_hash(); ?>">

<script>
/* Fallback: ensure Settings opens the modal even if Section 2 binds late. */
(function($){
  if (!window.__qaSettingsBtnBound){
    window.__qaSettingsBtnBound = true;
    $(document).on('click', '#qa_settings_btn', function(e){
      e.preventDefault();
      var $m = $('#qa_settings_modal');
      if ($m.length){
        $m.modal('show');
      } else if (typeof ensureSettingsModal === 'function'){
        ensureSettingsModal();
        $('#qa_settings_modal').modal('show');
      }
    });
  }
})(jQuery);
</script>
<!-- =================== /Quick Add   Section 1 =================== -->

















<!-- Top register controls -->

	<?php echo form_open($controller_name."/change_mode", array('id'=>'mode_form', 'class'=>'form-horizontal panel panel-default')); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="pull-left first_li">
					<label class="control-label"><?php echo $this->lang->line('sales_mode'); ?></label>
				</li>
				<li class="pull-left">
					<?php echo form_dropdown('mode', $modes, $mode, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
				</li>
				<?php
				if($this->config->item('dinner_table_enable') == TRUE)
				{
				?>
					<li class="pull-left first_li">
						<label class="control-label"><?php echo $this->lang->line('sales_table'); ?></label>
					</li>
					<li class="pull-left">
						<?php echo form_dropdown('dinner_table', $empty_tables, $selected_table, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
					</li>
				<?php
				}
				if(count($stock_locations) > 1)
				{
				?>
					<li class="pull-left">
						<label class="control-label"><?php echo $this->lang->line('sales_stock_location'); ?></label>
					</li>
					<li class="pull-left">
						<?php echo form_dropdown('stock_location', $stock_locations, $stock_location, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
					</li>
				<?php
				}
				?>

<?php if($this->Employee->has_grant('reports_sales', $this->session->userdata('person_id'))) { ?>
<li class="pull-right">
    <a href="<?php echo site_url($controller_name."/manage"); ?>" class="btn btn-primary btn-sm" id="sales_takings_button" title="<?php echo $this->lang->line('sales_takings'); ?>">
        <span class="glyphicon glyphicon-list-alt">&nbsp;</span><?php echo $this->lang->line('sales_takings'); ?>
    </a>
</li>
<?php } ?>

























<!-- ?? NEW Delivery Report Button -->
<li class="pull-right">
    <a href="<?php echo site_url('sales/delivery_report'); ?>" class="btn btn-info btn-sm" id="delivery_report_button" title="View Delivery Report">
        <span class="glyphicon glyphicon-road">&nbsp;</span>Delivery Report
    </a>
</li>
































        
			</ul>
		</div>
	<?php echo form_close(); ?>

	<?php $tabindex = 0; ?>

	<?php echo form_open($controller_name."/add", array('id'=>'add_item_form', 'class'=>'form-horizontal panel panel-default')); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="pull-left first_li">
					<label for="item" class='control-label'><?php echo $this->lang->line('sales_find_or_scan_item_or_receipt'); ?></label>
				</li>
				<li class="pull-left">
					<?php echo form_input(array('name'=>'item', 'id'=>'item', 'class'=>'form-control input-sm', 'size'=>'50', 'tabindex'=>++$tabindex)); ?>
					<span class="ui-helper-hidden-accessible" role="status"></span>
				</li>
				<li class="pull-right">
					<button id='new_item_button' class='btn btn-info btn-sm pull-right modal-dlg' data-btn-new="<?php echo $this->lang->line('common_new') ?>" data-btn-submit="<?php echo $this->lang->line('common_submit')?>" data-href="<?php echo site_url("items/view"); ?>"
							title="<?php echo $this->lang->line($controller_name . '_new_item'); ?>">
						<span class="glyphicon glyphicon-tag">&nbsp</span><?php echo $this->lang->line($controller_name. '_new_item'); ?>
					</button>
				</li>
			</ul>
		</div>
	<?php echo form_close(); ?>


<!-- Sale Items List -->

	<table class="sales_table_100" id="register">
		<thead>
			<tr>
				<th style="width: 5%; "><?php echo $this->lang->line('common_delete'); ?></th>
				<th style="width: 15%;"><?php echo $this->lang->line('sales_item_number'); ?></th>
				<th style="width: 30%;"><?php echo $this->lang->line('sales_item_name'); ?></th>
				<th style="width: 10%;"><?php echo $this->lang->line('sales_price'); ?></th>
				<th style="width: 10%;"><?php echo $this->lang->line('sales_quantity'); ?></th>
				<th style="width: 15%;"><?php echo $this->lang->line('sales_discount'); ?></th>
				<th style="width: 10%;"><?php echo $this->lang->line('sales_total'); ?></th>
				<th style="width: 5%; "><?php echo $this->lang->line('sales_update'); ?></th>
			</tr>
		</thead>

		<tbody id="cart_contents">
			<?php
			if(count($cart) == 0)
			{
			?>
				<tr>
					<td colspan='8'>
						<div class='alert alert-dismissible alert-info'><?php echo $this->lang->line('sales_no_items_in_cart'); ?></div>
					</td>
				</tr>
			<?php
			}
			else
			{
				foreach(array_reverse($cart, TRUE) as $line=>$item)
				{
			?>
					<?php echo form_open($controller_name."/edit_item/$line", array('class'=>'form-horizontal', 'id'=>'cart_'.$line)); ?>
						<tr>
							<td>
								<span data-item-id="<?php echo $line; ?>" class="delete_item_button"><span class="glyphicon glyphicon-trash"></span></span>
								<?php
								echo form_hidden('location', $item['item_location']);
								echo form_input(array('type'=>'hidden', 'name'=>'item_id', 'value'=>$item['item_id']));
								?>
							</td>
							<?php
							if($item['item_type'] == ITEM_TEMP)
							{
							?>
								<td><?php echo form_input(array('name'=>'item_number', 'id'=>'item_number','class'=>'form-control input-sm', 'value'=>$item['item_number'], 'tabindex'=>++$tabindex)); ?></td>
								<td style="align: center;">
									<?php echo form_input(array('name'=>'name','id'=>'name', 'class'=>'form-control input-sm', 'value'=>$item['name'], 'tabindex'=>++$tabindex)); ?>
								</td>
							<?php
							}
							else
							{
							?>
								<td><?php echo $item['item_number']; ?></td>
								<td style="align: center;">
									<?php echo $item['name'] . ' '. implode(' ', array($item['attribute_values'], $item['attribute_dtvalues'])); ?>
									<br/>
									<?php if ($item['stock_type'] == '0'): echo '[' . to_quantity_decimals($item['in_stock']) . ' in ' . $item['stock_name'] . ']'; endif; ?>
								</td>
							<?php
							}
							?>

							<td>
								<?php
								if($items_module_allowed && $change_price)
								{
									echo form_input(array('name'=>'price', 'class'=>'form-control input-sm', 'value'=>to_currency_no_money($item['price']), 'tabindex'=>++$tabindex, 'onClick'=>'this.select();'));
								}
								else
								{
									echo to_currency($item['price']);
									echo form_hidden('price', to_currency_no_money($item['price']));
								}
								?>
							</td>

							<td>
								<?php
								if($item['is_serialized'])
								{
									echo to_quantity_decimals($item['quantity']);
									echo form_hidden('quantity', $item['quantity']);
								}
								else
								{
									echo form_input(array('name'=>'quantity', 'class'=>'form-control input-sm', 'value'=>to_quantity_decimals($item['quantity']), 'tabindex'=>++$tabindex, 'onClick'=>'this.select();'));
								}
								?>
							</td>

							<td>
								<div class="input-group">
									<?php echo form_input(array('name'=>'discount', 'class'=>'form-control input-sm', 'value'=>$item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'tabindex'=>++$tabindex, 'onClick'=>'this.select();')); ?>
									<span class="input-group-btn">
										<?php echo form_checkbox(array('id'=>'discount_toggle', 'name'=>'discount_toggle', 'value'=>1, 'data-toggle'=>"toggle",'data-size'=>'small', 'data-onstyle'=>'success', 'data-on'=>'<b>'.$this->config->item('currency_symbol').'</b>', 'data-off'=>'<b>%</b>', 'data-line'=>$line, 'checked'=>$item['discount_type'])); ?>
									</span>
								</div>
							</td>

							<td>
								<?php
								if($item['item_type'] == ITEM_AMOUNT_ENTRY)
								{
									echo form_input(array('name'=>'discounted_total', 'class'=>'form-control input-sm', 'value'=>to_currency_no_money($item['discounted_total']), 'tabindex'=>++$tabindex, 'onClick'=>'this.select();'));
								}
								else
								{
									echo to_currency($item['discounted_total']);
								}
								?>
							</td>

							<td><a href="javascript:document.getElementById('<?php echo 'cart_'.$line ?>').submit();" title=<?php echo $this->lang->line('sales_update')?> ><span class="glyphicon glyphicon-refresh"></span></a></td>
						</tr>
						<tr>
							<?php
							if($item['item_type'] == ITEM_TEMP)
							{
							?>
								<td><?php echo form_input(array('type'=>'hidden', 'name'=>'item_id', 'value'=>$item['item_id'])); ?></td>
								<td style="align: center;" colspan="6">
									<?php echo form_input(array('name'=>'item_description', 'id'=>'item_description', 'class'=>'form-control input-sm', 'value'=>$item['description'], 'tabindex'=>++$tabindex)); ?>
								</td>
								<td> </td>
							<?php
							}
							else
							{
							?>
								<td> </td>
								<?php
								if($item['allow_alt_description'])
								{
								?>
									<td style="color: #2F4F4F;"><?php echo $this->lang->line('sales_description_abbrv'); ?></td>
								<?php
								}
								?>

								<td colspan='2' style="text-align: left;">
									<?php
									if($item['allow_alt_description'])
									{
										echo form_input(array('name'=>'description', 'class'=>'form-control input-sm', 'value'=>$item['description'], 'onClick'=>'this.select();'));
									}
									else
									{
										if($item['description'] != '')
										{
											echo $item['description'];
											echo form_hidden('description', $item['description']);
										}
										else
										{
											echo $this->lang->line('sales_no_description');
											echo form_hidden('description','');
										}
									}
									?>
								</td>
								<td>&nbsp;</td>
								<td style="color: #2F4F4F;">
									<?php
									if($item['is_serialized'])
									{
										echo $this->lang->line('sales_serial');
									}
									?>
								</td>
								<td colspan='4' style="text-align: left;">
									<?php
									if($item['is_serialized'])
									{
										echo form_input(array('name'=>'serialnumber', 'class'=>'form-control input-sm', 'value'=>$item['serialnumber'], 'onClick'=>'this.select();'));
									}
									else
									{
										echo form_hidden('serialnumber', '');
									}
									?>
								</td>
							<?php
							}
							?>
						</tr>
					<?php echo form_close(); ?>
			<?php
				}
			}
			?>
		</tbody>
	</table>
</div>




<!-- Overall Sale -->

<div id="overall_sale" class="panel panel-default">
	<div class="panel-body">
		<?php echo form_open($controller_name."/select_customer", array('id'=>'select_customer_form', 'class'=>'form-horizontal')); ?>
			<?php
			if(isset($customer))
			{
			?>
				<table class="sales_table_100">
					<tr>
						<th style="width: 55%;"><?php echo $this->lang->line("sales_customer"); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo anchor('customers/view/'.$customer_id, $customer, array('class' => 'modal-dlg', 'data-btn-submit' => $this->lang->line('common_submit'), 'title' => $this->lang->line('customers_update'))); ?></th>
					</tr>
					<?php
					if(!empty($customer_email))
					{
					?>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_email"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $customer_email; ?></th>
						</tr>
					<?php
					}
					?>
					<?php
					if(!empty($customer_address))
					{
					?>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_address"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $customer_address; ?></th>
						</tr>
					<?php
					}
					?>
					<?php
					if(!empty($customer_location))
					{
					?>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_location"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $customer_location; ?></th>
						</tr>
					<?php
					}
					?>
					<tr>
						<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_discount"); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo ($customer_discount_type == FIXED)?to_currency($customer_discount):$customer_discount . '%'; ?></th>
					</tr>
					<?php if($this->config->item('customer_reward_enable') == TRUE): ?>
					<?php
					if(!empty($customer_rewards))
					{
					?>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("rewards_package"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $customer_rewards['package_name']; ?></th>
						</tr>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("customers_available_points"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $customer_rewards['points']; ?></th>
						</tr>
					<?php
					}
					?>
					<?php endif; ?>
					<tr>
						<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_total"); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo to_currency($customer_total); ?></th>
					</tr>
					<?php
					if(!empty($mailchimp_info))
					{
					?>
						<tr>
							<th style="width: 55%;"><?php echo $this->lang->line("sales_customer_mailchimp_status"); ?></th>
							<th style="width: 45%; text-align: right;"><?php echo $mailchimp_info['status']; ?></th>
						</tr>
					<?php
					}
					?>
				</table>

				<button class="btn btn-danger btn-sm" id="remove_customer_button" title="<?php echo $this->lang->line('common_remove').' '.$this->lang->line('customers_customer')?>">
					<span class="glyphicon glyphicon-remove">&nbsp</span><?php echo $this->lang->line('common_remove').' '.$this->lang->line('customers_customer') ?>
				</button>






























<!-- Delivery/Pickup Button -->
<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#deliveryPickupModal">
  <span class="glyphicon glyphicon-road"></span> Delivery
</button>

<!-- Delivery/Pickup Modal -->
<div class="modal fade" id="deliveryPickupModal" tabindex="-1" role="dialog" aria-labelledby="deliveryPickupModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title">Schedule Delivery</h4>
      </div>

      <div class="modal-body">
        <form id="deliveryPickupForm">

	<div class="form-group" style="display: none;">
  <label for="deliveryType">Type</label>
  <select class="form-control" id="deliveryType" name="type">
    <option value="Delivery" selected>Delivery</option>
  </select>
</div>

          <div class="form-group">
            <label for="deliveryDate">Select Date</label>
            <input type="date" class="form-control" id="deliveryDate" name="date"
                   min="<?= date('Y-m-d'); ?>" required onclick="this.showPicker()">
          </div>

          <div class="form-group">
            <label for="deliveryTime">Delivery Time</label>

            <!-- Time Slot Dropdown -->
            <select class="form-control mb-2" id="timeSlotSelect">
              <option value="">-- Select a Time Slot (Optional) --</option>
              <option value="? ">-- No Time Selected --</option>
              <option>10:00AM - 11:00AM</option>
              <option>10:00AM - 12:00PM</option>
              <option>10:00AM - 1:00PM</option>
              <option>10:00AM - 2:00PM</option>
              <option>10:00AM - 3:00PM</option>
              <option>10:00AM - 4:00PM</option>
              <option>10:00AM - 5:00PM</option>
              <option>10:00AM - 6:00PM</option>
              <option>10:00AM - 7:00PM</option>
              <option>11:00AM - 12:00PM</option>
              <option>11:00AM - 1:00PM</option>
              <option>11:00AM - 2:00PM</option>
              <option>11:00AM - 3:00PM</option>
              <option>11:00AM - 4:00PM</option>
              <option>11:00AM - 5:00PM</option>
              <option>11:00AM - 6:00PM</option>
              <option>11:00AM - 7:00PM</option>
              <option>12:00PM - 1:00PM</option>
              <option>12:00PM - 2:00PM</option>
              <option>12:00PM - 3:00PM</option>
              <option>12:00PM - 4:00PM</option>
              <option>12:00PM - 5:00PM</option>
              <option>12:00PM - 6:00PM</option>
              <option>12:00PM - 7:00PM</option>
              <option>1:00PM - 2:00PM</option>
              <option>1:00PM - 3:00PM</option>
              <option>1:00PM - 4:00PM</option>
              <option>1:00PM - 5:00PM</option>
              <option>1:00PM - 6:00PM</option>
              <option>1:00PM - 7:00PM</option>
              <option>2:00PM - 3:00PM</option>
              <option>2:00PM - 4:00PM</option>
              <option>2:00PM - 5:00PM</option>
              <option>2:00PM - 6:00PM</option>
              <option>2:00PM - 7:00PM</option>
              <option>3:00PM - 4:00PM</option>
              <option>3:00PM - 5:00PM</option>
              <option>3:00PM - 6:00PM</option>
              <option>3:00PM - 7:00PM</option>
              <option>4:00PM - 5:00PM</option>
              <option>4:00PM - 6:00PM</option>
              <option>4:00PM - 7:00PM</option>
              <option>5:00PM - 6:00PM</option>
              <option>5:00PM - 7:00PM</option>
              <option>6:00PM - 7:00PM</option>
            </select>

            <div class="text-center my-2">or</div>

            <!-- Specific Time Picker -->
            <input type="time" class="form-control mb-2" id="specificTime" onclick="this.showPicker()">
            <button type="button" class="btn btn-link p-0" id="clearSpecificTime">Clear Specific Time</button>
          </div>
        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
  <!-- Left Side -->
  <button id="submitBlankDeliveryBtn" type="button" class="btn btn-warning">Local Delivery</button>

  <!-- Right Side -->
  <div>
    <button id="submitDeliveryBtn" type="button" class="btn btn-success">Submit</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
  </div>
</div>

    </div>
  </div>
</div>






























<!-- Pickup Button (Defaults to Pickup, uses Auto_pickup controller) -->
<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#pickupOnlyModal">
  <span class="glyphicon glyphicon-road"></span> Pickup
</button>

<!-- Pickup Modal -->
<div class="modal fade" id="pickupOnlyModal" tabindex="-1" role="dialog" aria-labelledby="pickupOnlyModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title">Schedule Pickup</h4>
      </div>

      <div class="modal-body">
        <form id="pickupOnlyForm">

          <!-- Type fixed as Pickup -->
          <input type="hidden" id="pickupOnlyType" value="Pickup">

          <div class="form-group">
            <label for="pickupOnlyDate">Select Date</label>
            <input type="date" class="form-control" id="pickupOnlyDate"
                   min="<?= date('Y-m-d'); ?>" required onclick="this.showPicker()">
          </div>

          <div class="form-group">
            <label for="pickupOnlyTime">Pickup Time</label>

            <!-- Time Slot Dropdown -->
            <select class="form-control mb-2" id="pickupOnlySlot">
	      <option value="">-- Select a Time Slot (Optional) --</option>
              <option value="? ">-- No Time Selected --</option>
              <option>10:00AM - 11:00AM</option>
              <option>10:00AM - 12:00PM</option>
              <option>10:00AM - 1:00PM</option>
              <option>10:00AM - 2:00PM</option>
              <option>10:00AM - 3:00PM</option>
              <option>10:00AM - 4:00PM</option>
              <option>10:00AM - 5:00PM</option>
              <option>10:00AM - 6:00PM</option>
              <option>10:00AM - 7:00PM</option>
              <option>11:00AM - 12:00PM</option>
              <option>11:00AM - 1:00PM</option>
              <option>11:00AM - 2:00PM</option>
              <option>11:00AM - 3:00PM</option>
              <option>11:00AM - 4:00PM</option>
              <option>11:00AM - 5:00PM</option>
              <option>11:00AM - 6:00PM</option>
              <option>11:00AM - 7:00PM</option>
              <option>12:00PM - 1:00PM</option>
              <option>12:00PM - 2:00PM</option>
              <option>12:00PM - 3:00PM</option>
              <option>12:00PM - 4:00PM</option>
              <option>12:00PM - 5:00PM</option>
              <option>12:00PM - 6:00PM</option>
              <option>12:00PM - 7:00PM</option>
              <option>1:00PM - 2:00PM</option>
              <option>1:00PM - 3:00PM</option>
              <option>1:00PM - 4:00PM</option>
              <option>1:00PM - 5:00PM</option>
              <option>1:00PM - 6:00PM</option>
              <option>1:00PM - 7:00PM</option>
              <option>2:00PM - 3:00PM</option>
              <option>2:00PM - 4:00PM</option>
              <option>2:00PM - 5:00PM</option>
              <option>2:00PM - 6:00PM</option>
              <option>2:00PM - 7:00PM</option>
              <option>3:00PM - 4:00PM</option>
              <option>3:00PM - 5:00PM</option>
              <option>3:00PM - 6:00PM</option>
              <option>3:00PM - 7:00PM</option>
              <option>4:00PM - 5:00PM</option>
              <option>4:00PM - 6:00PM</option>
              <option>4:00PM - 7:00PM</option>
              <option>5:00PM - 6:00PM</option>
              <option>5:00PM - 7:00PM</option>
              <option>6:00PM - 7:00PM</option>
            </select>

            <div class="text-center my-2">or</div>

            <!-- Specific Time Picker -->
            <input type="time" class="form-control mb-2" id="pickupOnlySpecific" onclick="this.showPicker()">
            
          </div>
        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
  <!-- Left Side -->
  <button id="submitBlankPickupBtn" type="button" class="btn btn-warning">In Store Pickup</button>

  <!-- Right Side -->
  <div>
    <button id="submitPickupOnlyBtn" type="button" class="btn btn-success">Submit</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
  </div>
</div>

    </div>
  </div>
</div>





			<?php
			}
			else
			{
			?>
				<div class="form-group" id="select_customer">
					<label id="customer_label" for="customer" class="control-label" style="margin-bottom: 1em; margin-top: -1em;"><?php echo $this->lang->line('sales_select_customer') . ' ' . $customer_required; ?></label>
					<?php echo form_input(array('name'=>'customer', 'id'=>'customer', 'class'=>'form-control input-sm', 'value'=>$this->lang->line('sales_start_typing_customer_name'))); ?>

					<button class='btn btn-info btn-sm modal-dlg' data-btn-submit="<?php echo $this->lang->line('common_submit') ?>" data-href="<?php echo site_url("customers/view"); ?>"
							title="<?php echo $this->lang->line($controller_name. '_new_customer'); ?>">
						<span class="glyphicon glyphicon-user">&nbsp</span><?php echo $this->lang->line($controller_name. '_new_customer'); ?>
					</button>




































<!-- Delivery/Pickup Button -->
<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#deliveryPickupModal">
  <span class="glyphicon glyphicon-road"></span> Delivery
</button>

<!-- Delivery/Pickup Modal -->
<div class="modal fade" id="deliveryPickupModal" tabindex="-1" role="dialog" aria-labelledby="deliveryPickupModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title">Schedule Delivery</h4>
      </div>

      <div class="modal-body">
        <form id="deliveryPickupForm">

<div class="form-group" style="display: none;">
  <label for="deliveryType">Type</label>
  <select class="form-control" id="deliveryType" name="type">
    <option value="Delivery" selected>Delivery</option>
  </select>
</div>

          <div class="form-group">
            <label for="deliveryDate">Select Date</label>
            <input type="date" class="form-control" id="deliveryDate" name="date"
                   min="<?= date('Y-m-d'); ?>" required onclick="this.showPicker()">
          </div>

          <div class="form-group">
            <label for="deliveryTime">Delivery Time</label>

            <!-- Time Slot Dropdown -->
            <select class="form-control mb-2" id="timeSlotSelect">
              <option value="">-- Select a Time Slot (Optional) --</option>
              <option value="? ">-- No Time Selected --</option>
              <option>10:00AM - 11:00AM</option>
              <option>10:00AM - 12:00PM</option>
              <option>10:00AM - 1:00PM</option>
              <option>10:00AM - 2:00PM</option>
              <option>10:00AM - 3:00PM</option>
              <option>10:00AM - 4:00PM</option>
              <option>10:00AM - 5:00PM</option>
              <option>10:00AM - 6:00PM</option>
              <option>10:00AM - 7:00PM</option>
              <option>11:00AM - 12:00PM</option>
              <option>11:00AM - 1:00PM</option>
              <option>11:00AM - 2:00PM</option>
              <option>11:00AM - 3:00PM</option>
              <option>11:00AM - 4:00PM</option>
              <option>11:00AM - 5:00PM</option>
              <option>11:00AM - 6:00PM</option>
              <option>11:00AM - 7:00PM</option>
              <option>12:00PM - 1:00PM</option>
              <option>12:00PM - 2:00PM</option>
              <option>12:00PM - 3:00PM</option>
              <option>12:00PM - 4:00PM</option>
              <option>12:00PM - 5:00PM</option>
              <option>12:00PM - 6:00PM</option>
              <option>12:00PM - 7:00PM</option>
              <option>1:00PM - 2:00PM</option>
              <option>1:00PM - 3:00PM</option>
              <option>1:00PM - 4:00PM</option>
              <option>1:00PM - 5:00PM</option>
              <option>1:00PM - 6:00PM</option>
              <option>1:00PM - 7:00PM</option>
              <option>2:00PM - 3:00PM</option>
              <option>2:00PM - 4:00PM</option>
              <option>2:00PM - 5:00PM</option>
              <option>2:00PM - 6:00PM</option>
              <option>2:00PM - 7:00PM</option>
              <option>3:00PM - 4:00PM</option>
              <option>3:00PM - 5:00PM</option>
              <option>3:00PM - 6:00PM</option>
              <option>3:00PM - 7:00PM</option>
              <option>4:00PM - 5:00PM</option>
              <option>4:00PM - 6:00PM</option>
              <option>4:00PM - 7:00PM</option>
              <option>5:00PM - 6:00PM</option>
              <option>5:00PM - 7:00PM</option>
              <option>6:00PM - 7:00PM</option>
            </select>

            <div class="text-center my-2">or</div>

            <!-- Specific Time Picker -->
            <input type="time" class="form-control mb-2" id="specificTime" onclick="this.showPicker()">
            <button type="button" class="btn btn-link p-0" id="clearSpecificTime">Clear Specific Time</button>
          </div>
        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
  <!-- Left Side -->
  <button id="submitBlankDeliveryBtn" type="button" class="btn btn-warning">Local Delivery</button>

  <!-- Right Side -->
  <div>
    <button id="submitDeliveryBtn" type="button" class="btn btn-success">Submit</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
  </div>
</div>



    </div>
  </div>
</div>




































<!-- Pickup Button (Defaults to Pickup, uses Auto_pickup controller) -->
<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#pickupOnlyModal">
  <span class="glyphicon glyphicon-road"></span> Pickup
</button>

<!-- Pickup Modal -->
<div class="modal fade" id="pickupOnlyModal" tabindex="-1" role="dialog" aria-labelledby="pickupOnlyModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title">Schedule Pickup</h4>
      </div>

      <div class="modal-body">
        <form id="pickupOnlyForm">

          <!-- Type fixed as Pickup -->
          <input type="hidden" id="pickupOnlyType" value="Pickup">

          <div class="form-group">
            <label for="pickupOnlyDate">Select Date</label>
            <input type="date" class="form-control" id="pickupOnlyDate"
                   min="<?= date('Y-m-d'); ?>" required onclick="this.showPicker()">
          </div>

          <div class="form-group">
            <label for="pickupOnlyTime">Pickup Time</label>

            <!-- Time Slot Dropdown -->
            <select class="form-control mb-2" id="pickupOnlySlot">
              <option value="">-- Select a Time Slot (Optional) --</option>
              <option value="? ">-- No Time Selected --</option>
              <option>10:00AM - 11:00AM</option>
              <option>10:00AM - 12:00PM</option>
              <option>10:00AM - 1:00PM</option>
              <option>10:00AM - 2:00PM</option>
              <option>10:00AM - 3:00PM</option>
              <option>10:00AM - 4:00PM</option>
              <option>10:00AM - 5:00PM</option>
              <option>10:00AM - 6:00PM</option>
              <option>10:00AM - 7:00PM</option>
              <option>11:00AM - 12:00PM</option>
              <option>11:00AM - 1:00PM</option>
              <option>11:00AM - 2:00PM</option>
              <option>11:00AM - 3:00PM</option>
              <option>11:00AM - 4:00PM</option>
              <option>11:00AM - 5:00PM</option>
              <option>11:00AM - 6:00PM</option>
              <option>11:00AM - 7:00PM</option>
              <option>12:00PM - 1:00PM</option>
              <option>12:00PM - 2:00PM</option>
              <option>12:00PM - 3:00PM</option>
              <option>12:00PM - 4:00PM</option>
              <option>12:00PM - 5:00PM</option>
              <option>12:00PM - 6:00PM</option>
              <option>12:00PM - 7:00PM</option>
              <option>1:00PM - 2:00PM</option>
              <option>1:00PM - 3:00PM</option>
              <option>1:00PM - 4:00PM</option>
              <option>1:00PM - 5:00PM</option>
              <option>1:00PM - 6:00PM</option>
              <option>1:00PM - 7:00PM</option>
              <option>2:00PM - 3:00PM</option>
              <option>2:00PM - 4:00PM</option>
              <option>2:00PM - 5:00PM</option>
              <option>2:00PM - 6:00PM</option>
              <option>2:00PM - 7:00PM</option>
              <option>3:00PM - 4:00PM</option>
              <option>3:00PM - 5:00PM</option>
              <option>3:00PM - 6:00PM</option>
              <option>3:00PM - 7:00PM</option>
              <option>4:00PM - 5:00PM</option>
              <option>4:00PM - 6:00PM</option>
              <option>4:00PM - 7:00PM</option>
              <option>5:00PM - 6:00PM</option>
              <option>5:00PM - 7:00PM</option>
              <option>6:00PM - 7:00PM</option>
            </select>

            <div class="text-center my-2">or</div>

            <!-- Specific Time Picker -->
            <input type="time" class="form-control mb-2" id="pickupOnlySpecific" onclick="this.showPicker()">

          </div>
        </form>
      </div>

      <div class="modal-footer d-flex justify-content-between">
  <!-- Left Side -->
  <button id="submitBlankPickupBtn" type="button" class="btn btn-warning">In Store Pickup</button>

  <!-- Right Side -->
  <div>
    <button id="submitPickupOnlyBtn" type="button" class="btn btn-success">Submit</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
  </div>
</div>

    </div>
  </div>
</div>



























				</div>
			<?php
			}
			?>
		<?php echo form_close(); ?>

		<table class="sales_table_100" id="sale_totals">
			<tr>
				<th style="width: 55%;"><?php echo $this->lang->line('sales_quantity_of_items',$item_count); ?></th>
				<th style="width: 45%; text-align: right;"><?php echo $total_units; ?></th>
			</tr>
			<tr>
				<th style="width: 55%;"><?php echo $this->lang->line('sales_sub_total'); ?></th>
				<th style="width: 45%; text-align: right;"><?php echo to_currency($subtotal); ?></th>
			</tr>

			<?php
			foreach($taxes as $tax_group_index=>$tax)
			{
			?>
				<tr>
					<th style="width: 55%;"><?php echo (float)$tax['tax_rate'] . '% ' . $tax['tax_group']; ?></th>
					<th style="width: 45%; text-align: right;"><?php echo to_currency_tax($tax['sale_tax_amount']); ?></th>
				</tr>
			<?php
			}
			?>

			<tr>
				<th style="width: 55%; font-size: 150%"><?php echo $this->lang->line('sales_total'); ?></th>
				<th style="width: 45%; font-size: 150%; text-align: right;"><span id="sale_total"><?php echo to_currency($total); ?></span></th>
			</tr>
		</table>

		<?php
		// Only show this part if there are Items already in the register
		if(count($cart) > 0)
		{
		?>
			<table class="sales_table_100" id="payment_totals">
				<tr>
					<th style="width: 55%;"><?php echo $this->lang->line('sales_payments_total'); ?></th>
					<th style="width: 45%; text-align: right;"><?php echo to_currency($payments_total); ?></th>
				</tr>
				<tr>
					<th style="width: 55%; font-size: 120%"><?php echo $this->lang->line('sales_amount_due'); ?></th>
					<th style="width: 45%; font-size: 120%; text-align: right;"><span id="sale_amount_due"><?php echo to_currency($amount_due); ?></span></th>
				</tr>
			</table>

			<div id="payment_details">
				<?php
				// Show Complete sale button instead of Add Payment if there is no amount due left
				if($payments_cover_total)
				{
				?>
					<?php echo form_open($controller_name."/add_payment", array('id'=>'add_payment_form', 'class'=>'form-horizontal')); ?>
						<table class="sales_table_100">
							<tr>
								<td><?php echo $this->lang->line('sales_payment'); ?></td>
								<td>
									<?php echo form_dropdown('payment_type', $payment_options, $selected_payment_type, array('id'=>'payment_types', 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit', 'disabled'=>'disabled')); ?>
								</td>
							</tr>
							<tr>
								<td><span id="amount_tendered_label"><?php echo $this->lang->line('sales_amount_tendered'); ?></span></td>
								<td>
									<?php echo form_input(array('name'=>'amount_tendered', 'id'=>'amount_tendered', 'class'=>'form-control input-sm disabled', 'disabled'=>'disabled', 'value'=>'0', 'size'=>'5', 'tabindex'=>++$tabindex, 'onClick'=>'this.select();')); ?>
								</td>
							</tr>
						</table>
					<?php echo form_close(); ?>

					<?php
					// Only show this part if in sale or return mode
					if($pos_mode)
					{
						$due_payment = FALSE;

						if(count($payments) > 0)
						{
							foreach($payments as $payment_id => $payment)
							{
								if($payment['payment_type'] == $this->lang->line('sales_due'))
								{
									$due_payment = TRUE;
								}
							}
						}

						if(!$due_payment || ($due_payment && isset($customer)))
						{
					?>
							<div class='btn btn-sm btn-success pull-right' id='finish_sale_button' tabindex="<?php echo ++$tabindex; ?>"><span class="glyphicon glyphicon-ok">&nbsp</span><?php echo $this->lang->line('sales_complete_sale'); ?></div>
					<?php
						}
					}
					?>
				<?php
				}
				else
				{
				?>
					<?php echo form_open($controller_name."/add_payment", array('id'=>'add_payment_form', 'class'=>'form-horizontal')); ?>
						<table class="sales_table_100">
							<tr>
								<td><?php echo $this->lang->line('sales_payment'); ?></td>
								<td>
									<?php echo form_dropdown('payment_type', $payment_options,  $selected_payment_type, array('id'=>'payment_types', 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
								</td>
							</tr>
							<tr>
								<td><span id="amount_tendered_label"><?php echo $this->lang->line('sales_amount_tendered'); ?></span></td>
								<td>
									<?php echo form_input(array('name'=>'amount_tendered', 'id'=>'amount_tendered', 'class'=>'form-control input-sm non-giftcard-input', 'value'=>to_currency_no_money($amount_due), 'size'=>'5', 'tabindex'=>++$tabindex, 'onClick'=>'this.select();')); ?>
									<?php echo form_input(array('name'=>'amount_tendered', 'id'=>'amount_tendered', 'class'=>'form-control input-sm giftcard-input', 'disabled' => true, 'value'=>to_currency_no_money($amount_due), 'size'=>'5', 'tabindex'=>++$tabindex)); ?>
								</td>
							</tr>
						</table>
					<?php echo form_close(); ?>

					<div class='btn btn-sm btn-success pull-right' id='add_payment_button' tabindex="<?php echo ++$tabindex; ?>"><span class="glyphicon glyphicon-credit-card">&nbsp</span><?php echo $this->lang->line('sales_add_payment'); ?></div>
				<?php
				}
				?>

				<?php
				// Only show this part if there is at least one payment entered.
				if(count($payments) > 0)
				{
				?>
					<table class="sales_table_100" id="register">
						<thead>
							<tr>
								<th style="width: 10%;"><?php echo $this->lang->line('common_delete'); ?></th>
								<th style="width: 60%;"><?php echo $this->lang->line('sales_payment_type'); ?></th>
								<th style="width: 20%;"><?php echo $this->lang->line('sales_payment_amount'); ?></th>
							</tr>
						</thead>

						<tbody id="payment_contents">
							<?php
							foreach($payments as $payment_id => $payment)
							{
							?>
								<tr>
									<td><span data-payment-id="<?php echo $payment_id; ?>" class="delete_payment_button"><span class="glyphicon glyphicon-trash"></span></span></td>
									<td><?php echo $payment['payment_type']; ?></td>
									<td style="text-align: right;"><?php echo to_currency($payment['payment_amount']); ?></td>
								</tr>
							<?php
							}
							?>
						</tbody>
					</table>
				<?php
				}
				?>
			</div>

			<?php echo form_open($controller_name."/cancel", array('id'=>'buttons_form')); ?>
				<div class="form-group" id="buttons_sale">
					<div class='btn btn-sm btn-default pull-left' id='suspend_sale_button'><span class="glyphicon glyphicon-align-justify">&nbsp</span><?php echo $this->lang->line('sales_suspend_sale'); ?></div>
					<?php
					// Only show this part if the payment covers the total
					if(!$pos_mode && isset($customer))
					{
					?>
						<div class='btn btn-sm btn-success' id='finish_invoice_quote_button'><span class="glyphicon glyphicon-ok">&nbsp</span><?php echo $mode_label; ?></div>
					<?php
					}
					?>

					<div class='btn btn-sm btn-danger pull-right' id='cancel_sale_button'><span class="glyphicon glyphicon-remove">&nbsp</span><?php echo $this->lang->line('sales_cancel_sale'); ?></div>
				</div>
			<?php echo form_close(); ?>

			<?php
			// Only show this part if the payment cover the total
			if($payments_cover_total || !$pos_mode)
			{
			?>
				<div class="container-fluid">
					<div class="no-gutter row">
						<div class="form-group form-group-sm">
							<div class="col-xs-12">
								<?php echo form_label($this->lang->line('common_comments'), 'comments', array('class'=>'control-label', 'id'=>'comment_label', 'for'=>'comment')); ?>
								<?php echo form_textarea(array('name'=>'comment', 'id'=>'comment', 'class'=>'form-control input-sm', 'value'=>$comment, 'rows'=>'2')); ?>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="form-group form-group-sm">
							<div class="col-xs-6">
								<label for="sales_print_after_sale" class="control-label checkbox">
									<?php echo form_checkbox(array('name'=>'sales_print_after_sale', 'id'=>'sales_print_after_sale', 'value'=>1, 'checked'=>$print_after_sale)); ?>
									<?php echo $this->lang->line('sales_print_after_sale')?>
								</label>
							</div>

							<?php
							if(!empty($customer_email))
							{
							?>
								<div class="col-xs-6">
									<label for="email_receipt" class="control-label checkbox">
										<?php echo form_checkbox(array('name'=>'email_receipt', 'id'=>'email_receipt', 'value'=>1, 'checked'=>$email_receipt)); ?>
										<?php echo $this->lang->line('sales_email_receipt'); ?>
									</label>
								</div>
							<?php
							}
							?>
							<?php
							if($mode == 'sale_work_order')
							{
							?>
								<div class="col-xs-6">
									<label for="price_work_orders" class="control-label checkbox">
									<?php echo form_checkbox(array('name'=>'price_work_orders', 'id'=>'price_work_orders', 'value'=>1, 'checked'=>$price_work_orders)); ?>
									<?php echo $this->lang->line('sales_include_prices'); ?>
									</label>
								</div>
							<?php
							}
							?>
						</div>
					</div>
					<?php
					if(($mode == 'sale_invoice') && $this->config->item('invoice_enable') == TRUE)
					{
					?>
						<div class="row">
							<div class="form-group form-group-sm">
								<div class="col-xs-6">
									<label for="sales_invoice_number" class="control-label checkbox">
										<?php echo $this->lang->line('sales_invoice_enable'); ?>
									</label>
								</div>

								<div class="col-xs-6">
									<div class="input-group input-group-sm">
										<span class="input-group-addon input-sm">#</span>
										<?php echo form_input(array('name'=>'sales_invoice_number', 'id'=>'sales_invoice_number', 'class'=>'form-control input-sm', 'value'=>$invoice_number)); ?>
									</div>
								</div>
							</div>
						</div>
					<?php
					}
					?>
				</div>
			<?php
			}
			?>
		<?php
		}
		?>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function()
{
	const redirect = function() {
		window.location.href = "<?php echo site_url('sales'); ?>";
	};

	$("#remove_customer_button").click(function()
	{
		$.post("<?php echo site_url('sales/remove_customer'); ?>", redirect);
	});

	$(".delete_item_button").click(function()
	{
		const item_id = $(this).data('item-id');
		$.post("<?php echo site_url('sales/delete_item/'); ?>" + item_id, redirect);
	});

	$(".delete_payment_button").click(function() {
		const item_id = $(this).data('payment-id');
		$.post("<?php echo site_url('sales/delete_payment/'); ?>" + item_id, redirect);
	});

	$("input[name='item_number']").change(function() {
		var item_id = $(this).parents('tr').find("input[name='item_id']").val();
		var item_number = $(this).val();
		$.ajax({
			url: "<?php echo site_url('sales/change_item_number'); ?>",
			method: 'post',
			data: {
				'item_id': item_id,
				'item_number': item_number,
			},
			dataType: 'json'
		});
	});

	$("input[name='name']").change(function() {
		var item_id = $(this).parents('tr').find("input[name='item_id']").val();
		var item_name = $(this).val();
		$.ajax({
			url: "<?php echo site_url('sales/change_item_name'); ?>",
			method: 'post',
			data: {
				'item_id': item_id,
				'item_name': item_name,
			},
			dataType: 'json'
		});
	});

	$("input[name='item_description']").change(function() {
		var item_id = $(this).parents('tr').find("input[name='item_id']").val();
		var item_description = $(this).val();
		$.ajax({
			url: "<?php echo site_url('sales/change_item_description'); ?>",
			method: 'post',
			data: {
				'item_id': item_id,
				'item_description': item_description,
			},
			dataType: 'json'
		});
	});

	$('#item').focus();

	$('#item').blur(function() {
		$(this).val("<?php echo $this->lang->line('sales_start_typing_item_name'); ?>");
	});

	$('#item').autocomplete( {
		source: "<?php echo site_url($controller_name . '/item_search'); ?>",
		minChars: 0,
		autoFocus: false,
		delay: 500,
		select: function (a, ui) {
			$(this).val(ui.item.value);
			$('#add_item_form').submit();
			return false;
		}
	});

	$('#item').keypress(function (e) {
		if(e.which == 13) {
			$('#add_item_form').submit();
			return false;
		}
	});

	var clear_fields = function() {
		if($(this).val().match("<?php echo $this->lang->line('sales_start_typing_item_name') . '|' . $this->lang->line('sales_start_typing_customer_name'); ?>"))
		{
			$(this).val('');
		}
	};

	$('#item, #customer').click(clear_fields).dblclick(function(event) {
		$(this).autocomplete('search');
	});

	$('#customer').blur(function() {
		$(this).val("<?php echo $this->lang->line('sales_start_typing_customer_name'); ?>");
	});

	$('#customer').autocomplete( {
		source: "<?php echo site_url('customers/suggest'); ?>",
		minChars: 0,
		delay: 10,
		select: function (a, ui) {
			$(this).val(ui.item.value);
			$('#select_customer_form').submit();
			return false;
		}
	});

	$('#customer').keypress(function (e) {
		if(e.which == 13) {
			$('#select_customer_form').submit();
			return false;
		}
	});

	$('.giftcard-input').autocomplete( {
		source: "<?php echo site_url('giftcards/suggest'); ?>",
		minChars: 0,
		delay: 10,
		select: function (a, ui) {
			$(this).val(ui.item.value);
			$('#add_payment_form').submit();
			return false;
		}
	});

	$('#comment').keyup(function() {
		$.post("<?php echo site_url($controller_name.'/set_comment'); ?>", {comment: $('#comment').val()});
	});

	<?php
	if($this->config->item('invoice_enable') == TRUE)
	{
	?>
		$('#sales_invoice_number').keyup(function() {
			$.post("<?php echo site_url($controller_name.'/set_invoice_number'); ?>", {sales_invoice_number: $('#sales_invoice_number').val()});
		});

	<?php
	}
	?>

	$('#sales_print_after_sale').change(function() {
		$.post("<?php echo site_url($controller_name.'/set_print_after_sale'); ?>", {sales_print_after_sale: $(this).is(':checked')});
	});

	$('#price_work_orders').change(function() {
		$.post("<?php echo site_url($controller_name.'/set_price_work_orders'); ?>", {price_work_orders: $(this).is(':checked')});
	});

	$('#email_receipt').change(function() {
		$.post("<?php echo site_url($controller_name.'/set_email_receipt'); ?>", {email_receipt: $(this).is(':checked')});
	});

	$('#finish_sale_button').click(function() {
		$('#buttons_form').attr('action', "<?php echo site_url($controller_name.'/complete'); ?>");
		$('#buttons_form').submit();
	});

	$('#finish_invoice_quote_button').click(function() {
		$('#buttons_form').attr('action', "<?php echo site_url($controller_name.'/complete'); ?>");
		$('#buttons_form').submit();
	});

	$('#suspend_sale_button').click(function() {
		$('#buttons_form').attr('action', "<?php echo site_url($controller_name.'/suspend'); ?>");
		$('#buttons_form').submit();
	});

	$('#cancel_sale_button').click(function() {
		if(confirm("<?php echo $this->lang->line('sales_confirm_cancel_sale'); ?>"))
		{
			$('#buttons_form').attr('action', "<?php echo site_url($controller_name.'/cancel'); ?>");
			$('#buttons_form').submit();
		}
	});

	$('#add_payment_button').click(function() {
		$('#add_payment_form').submit();
	});

	$('#payment_types').change(check_payment_type).ready(check_payment_type);

	$('#cart_contents input').keypress(function(event) {
		if(event.which == 13)
		{
			$(this).parents('tr').prevAll('form:first').submit();
		}
	});

	$('#amount_tendered').keypress(function(event) {
		if(event.which == 13)
		{
			$('#add_payment_form').submit();
		}
	});

	$('#finish_sale_button').keypress(function(event) {
		if(event.which == 13)
		{
			$('#finish_sale_form').submit();
		}
	});

	dialog_support.init('a.modal-dlg, button.modal-dlg');

	table_support.handle_submit = function(resource, response, stay_open) {
		$.notify( { message: response.message }, { type: response.success ? 'success' : 'danger'} )

		if(response.success)
		{
			if(resource.match(/customers$/))
			{
				$('#customer').val(response.id);
				$('#select_customer_form').submit();
			}
			else
			{
				var $stock_location = $("select[name='stock_location']").val();
				$('#item_location').val($stock_location);
				$('#item').val(response.id);
				if(stay_open)
				{
					$('#add_item_form').ajaxSubmit();
				}
				else
				{
					$('#add_item_form').submit();
				}
			}
		}
	}

	$('[name="price"],[name="quantity"],[name="discount"],[name="description"],[name="serialnumber"],[name="discounted_total"]').change(function() {
		$(this).parents('tr').prevAll('form:first').submit()
	});

	$('[name="discount_toggle"]').change(function() {
		var input = $('<input>').attr('type', 'hidden').attr('name', 'discount_type').val(($(this).prop('checked'))?1:0);
		$('#cart_'+ $(this).attr('data-line')).append($(input));
		$('#cart_'+ $(this).attr('data-line')).submit();
	});
});

function check_payment_type()
{
	var cash_mode = <?php echo json_encode($cash_mode); ?>;

	if($("#payment_types").val() == "<?php echo $this->lang->line('sales_giftcard'); ?>")
	{
		$("#sale_total").html("<?php echo to_currency($total); ?>");
		$("#sale_amount_due").html("<?php echo to_currency($amount_due); ?>");
		$("#amount_tendered_label").html("<?php echo $this->lang->line('sales_giftcard_number'); ?>");
		$("#amount_tendered:enabled").val('').focus();
		$(".giftcard-input").attr('disabled', false);
		$(".non-giftcard-input").attr('disabled', true);
		$(".giftcard-input:enabled").val('').focus();
	}
	else if(($("#payment_types").val() == "<?php echo $this->lang->line('sales_cash'); ?>" && cash_mode == '1'))
	{
		$("#sale_total").html("<?php echo to_currency($non_cash_total); ?>");
		$("#sale_amount_due").html("<?php echo to_currency($cash_amount_due); ?>");
		$("#amount_tendered_label").html("<?php echo $this->lang->line('sales_amount_tendered'); ?>");
		$("#amount_tendered:enabled").val("<?php echo to_currency_no_money($cash_amount_due); ?>");
		$(".giftcard-input").attr('disabled', true);
		$(".non-giftcard-input").attr('disabled', false);
	}
	else
	{
		$("#sale_total").html("<?php echo to_currency($non_cash_total); ?>");
		$("#sale_amount_due").html("<?php echo to_currency($amount_due); ?>");
		$("#amount_tendered_label").html("<?php echo $this->lang->line('sales_amount_tendered'); ?>");
		$("#amount_tendered:enabled").val("<?php echo to_currency_no_money($amount_due); ?>");
		$(".giftcard-input").attr('disabled', true);
		$(".non-giftcard-input").attr('disabled', false);
	}
}

// Add Keyboard Shortcuts/Hotkeys to Sale Register
document.body.onkeyup = function(e)
{
	switch(event.altKey && event.keyCode) 
	{
        case 49: // Alt + 1 Items Seach
			$("#item").focus();
			$("#item").select();
            break;
        case 50: // Alt + 2 Customers Search
			$("#customer").focus();
			$("#customer").select();
            break;
		case 51: // Alt + 3 Suspend Current Sale
			$("#suspend_sale_button").click();
			break;
		case 52: // Alt + 4 Check Suspended
			$("#show_suspended_sales_button").click();
			break;
        case 53: // Alt + 5 Edit Amount Tendered Value
			$("#amount_tendered").focus();
			$("#amount_tendered").select();
            break;
		case 54: // Alt + 6 Add Payment
			$("#add_payment_button").click();
			break;	
		case 55: // Alt + 7 Add Payment and Complete Sales/Invoice
			$("#add_payment_button").click();
			window.location.href = "<?php echo site_url('sales/complete'); ?>";
			break; 
		case 56: // Alt + 8 Finish Quote/Invoice without payment
			$("#finish_invoice_quote_button").click();
			break;
		case 57: // Alt + 9 Open Shortcuts Help Modal
			$("#show_keyboard_help").click();
			break;
	}
	
	switch(event.keyCode) 
	{
		case 27: // ESC Cancel Current Sale
			$("#cancel_sale_button").click();
			break;		  
    }
}

</script>









































<script>
document.getElementById('submitDeliveryBtn').addEventListener('click', function () {
  const type = document.getElementById('deliveryType').value;
  const date = document.getElementById('deliveryDate').value;
  const timeSlot = document.getElementById('timeSlotSelect').value;
  const specificTime = document.getElementById('specificTime').value;

  if (!date || (!timeSlot && !specificTime)) {
    alert("Please select a date & time.");
    return;
  }

  const parts = date.split('-'); // ['2025', '04', '25']
  const formattedDate = `${parts[1]}/${parts[2]}/${parts[0]}`; // MM/DD/YYYY


  // Determine final time value to send
  let finalTime = timeSlot;
  if (!finalTime && specificTime) {
    // Convert "14:08" to "2:08PM"
    const [hour, minute] = specificTime.split(':');
    const hourNum = parseInt(hour, 10);
    const ampm = hourNum >= 12 ? 'PM' : 'AM';
    const formattedHour = ((hourNum + 11) % 12 + 1);
    finalTime = `${formattedHour}:${minute}${ampm}`;
  }

  const encodedTime = encodeURIComponent(finalTime);
  const url = `<?php echo site_url('Auto_delivery/generate_items'); ?>?date_delivery=${formattedDate}&time_delivery=${encodedTime}`;

  // Open in same tab
  window.location.href = url;
});

document.getElementById('clearSpecificTime').addEventListener('click', function () {
  specificTimeInput.value = '';
  specificTimeInput.disabled = false;
  timeSlotSelect.disabled = false;
});

</script>


<script>
  const timeSlotSelect = document.getElementById('timeSlotSelect');
  const specificTimeInput = document.getElementById('specificTime');

  // Disable specific time when time slot is selected
  timeSlotSelect.addEventListener('change', function () {
    if (this.value !== '') {
      specificTimeInput.value = '';
      specificTimeInput.disabled = true;
    } else {
      specificTimeInput.disabled = false;
    }
  });

  // Disable time slot when specific time is picked
  specificTimeInput.addEventListener('input', function () {
    if (this.value !== '') {
      timeSlotSelect.selectedIndex = 0; // Reset to "no selection"
      timeSlotSelect.disabled = true;
    } else {
      timeSlotSelect.disabled = false;
    }
  });

  // On modal open, reset both
  $('#deliveryPickupModal').on('shown.bs.modal', function () {
    specificTimeInput.disabled = false;
    timeSlotSelect.disabled = false;
  });
</script>

<script>
  const pickupSlot = document.getElementById('pickupOnlySlot');
  const pickupTime = document.getElementById('pickupOnlySpecific');

  document.getElementById('submitPickupOnlyBtn').addEventListener('click', function () {
    const date = document.getElementById('pickupOnlyDate').value;
    const timeSlot = pickupSlot.value;
    const specificTime = pickupTime.value;

    if (!date || (!timeSlot && !specificTime)) {
      alert("Please select a date & time.");
      return;
    }

    const parts = date.split('-');
    const formattedDate = `${parts[1]}/${parts[2]}/${parts[0]}`;

    let finalTime = timeSlot;
    if (!finalTime && specificTime) {
      const [hour, minute] = specificTime.split(':');
      const hourNum = parseInt(hour, 10);
      const ampm = hourNum >= 12 ? 'PM' : 'AM';
      const formattedHour = ((hourNum + 11) % 12 + 1);
      finalTime = `${formattedHour}:${minute}${ampm}`;
    }

    const encodedTime = encodeURIComponent(finalTime);
    const url = `<?php echo site_url('Auto_pickup/generate_items'); ?>?date_delivery=${formattedDate}&time_delivery=${encodedTime}`;

    window.location.href = url;
  });

  pickupSlot.addEventListener('change', function () {
    if (this.value !== '') {
      pickupTime.value = '';
      pickupTime.disabled = true;
    } else {
      pickupTime.disabled = false;
    }
  });

  pickupTime.addEventListener('input', function () {
    if (this.value !== '') {
      pickupSlot.selectedIndex = 0;
      pickupSlot.disabled = true;
    } else {
      pickupSlot.disabled = false;
    }
  });

  document.getElementById('clearPickupSlot').addEventListener('click', function () {
    pickupSlot.selectedIndex = 0;
    pickupSlot.disabled = false;
    pickupTime.disabled = false;
  });

  document.getElementById('clearPickupSpecific').addEventListener('click', function () {
    pickupTime.value = '';
    pickupTime.disabled = false;
    pickupSlot.disabled = false;
  });

  $('#pickupOnlyModal').on('shown.bs.modal', function () {
    const dateField = document.getElementById('pickupOnlyDate');
    pickupSlot.disabled = false;
    pickupTime.disabled = false;
    dateField.focus();
    if (dateField.showPicker) dateField.showPicker();
  });
</script>


<script>
  const params = new URLSearchParams(window.location.search);
  const itemQuery = params.get('item');

  if (itemQuery) {
    document.getElementById('item').value = itemQuery;
    document.getElementById('add_item_form').submit();
  }
</script>


<script>
document.getElementById('submitBlankDeliveryBtn').addEventListener('click', function () {
  const type = document.getElementById('deliveryType').value;

  // Submit with blank placeholders
  const formattedDate = ' ';
  const finalTime = ' ';

  const encodedTime = encodeURIComponent(finalTime);
  const url = `<?php echo site_url('Auto_delivery/generate_items'); ?>?date_delivery=${formattedDate}&time_delivery=${encodedTime}`;

  window.location.href = url;
});
</script>



<script>
document.getElementById('submitBlankPickupBtn').addEventListener('click', function () {
  // Set type to pickup
  const formattedDate = ' ';
  const finalTime = ' ';

  const encodedTime = encodeURIComponent(finalTime);
  const url = `<?php echo site_url('Auto_pickup/generate_items'); ?>?date_delivery=${formattedDate}&time_delivery=${encodedTime}`;

  window.location.href = url;
});
</script>












<!-- ======================= Quick Add   Section 2 (Runtime + Reliability) ======================= -->





<script>
(function($){
  "use strict";

  var QA = {
    canManage: $('#qa_tabs_bar').data('can-manage') === 1 || $('#qa_tabs_bar').data('can-manage') === '1',
    flows: [],
    currentFlowId: null,
    selections: {},
    csrfName: $('#qa_csrf_name').val() || '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash: $('#qa_csrf_hash').val() || '<?php echo $this->security->get_csrf_hash(); ?>'
  };

  function setCsrf(resp){
    if (!resp) return;
    var c = resp.csrf || (resp.data && resp.data.csrf) || null;
    if (c && c.name && c.hash){
      QA.csrfName = c.name; QA.csrfHash = c.hash;
      $('#qa_csrf_name').val(c.name); $('#qa_csrf_hash').val(c.hash);
    }
  }

  function apiGET(url, data, ok, err){
    $.ajax({ url:url, method:'GET', data:data||{}, dataType:'json' })
      .done(function(r){ setCsrf(r); if (r && r.ok){ ok && ok(r.data||{}, r); } else { (err||alert)((r && r.message)||'Request failed'); } })
      .fail(function(x){ (err||alert)((x.responseJSON && x.responseJSON.message)||'Network error'); });
  }
  function apiPOST(url, data, ok, err){
    var p = $.extend({}, data||{}); p[QA.csrfName] = QA.csrfHash;
    $.ajax({ url:url, method:'POST', data:p, dataType:'json', traditional:true })
      .done(function(r){ setCsrf(r); if (r && r.ok){ ok && ok(r.data||{}, r); } else { (err||alert)((r && r.message)||'Request failed'); } })
      .fail(function(x){ (err||alert)((x.responseJSON && x.responseJSON.message)||'Network error'); });
  }
  window.apiGET = apiGET; window.apiPOST = apiPOST;

  function ensureQuickModal(){
    if ($('#qa_quick_modal').length) return;
    var html = ''
      + '<div class="modal fade" id="qa_quick_modal" tabindex="-1" role="dialog" aria-labelledby="qaQuickTitle">'
      + '  <div class="modal-dialog" role="document">'
      + '    <div class="modal-content">'
      + '      <div class="modal-header">'
      + '        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>'
      + '        <h4 class="modal-title" id="qaQuickTitle">Quick Add</h4>'
      + '      </div>'
      + '      <div class="modal-body"><div id="qa_quick_steps"></div></div>'
      + '      <div class="modal-footer">'
      + '        <button type="button" id="qa_clear_btn" class="btn btn-default">Clear / Reset</button>'
      + '        <button type="button" id="qa_add_to_cart_btn" class="btn btn-primary" disabled>Add to Cart</button>'
      + '      </div>'
      + '    </div>'
      + '  </div>'
      + '</div>';
    $('body').append(html);
  }
  function ensureSettingsModal(){
    if ($('#qa_settings_modal').length) return;
    var html = ''
      + '<div class="modal fade" id="qa_settings_modal" tabindex="-1" role="dialog" aria-labelledby="qaSettingsTitle">'
      + '  <div class="modal-dialog modal-lg" role="document" style="width:100%;max-width:1100px;">'
      + '    <div class="modal-content">'
      + '      <div class="modal-header">'
      + '        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>'
      + '        <h4 class="modal-title" id="qaSettingsTitle">Universal Quick Add   Designer</h4>'
      + '      </div>'
      + '      <div class="modal-body"><div class="alert alert-info">Loading </div></div>'
      + '      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>'
      + '    </div>'
      + '  </div>'
      + '</div>';
    $('body').append(html);
  }
  window.ensureSettingsModal = ensureSettingsModal;

  $(document).off('click.qaSettings').on('click.qaSettings', '#qa_settings_btn', function(e){
    e.preventDefault(); ensureSettingsModal(); $('#qa_settings_modal').modal('show');
  });

  function loadFlows(){
    apiGET('<?php echo site_url('quick_add/flows'); ?>', { enabled: QA.canManage ? 0 : 1 }, function(d){
      QA.flows = d.flows || [];
      renderTabs();
    });
  }
  function renderTabs(){
    var $ul = $('#qa_tabs_ul').empty();
    if (!QA.flows.length){
      $ul.append('<li class="disabled"><a href="javascript:void(0)">No tabs yet</a></li>');
      return;
    }
    QA.flows.sort(function(a,b){ return (a.sort_order||0)-(b.sort_order||0) || (a.id-b.id); });
    QA.flows.forEach(function(flow){
      var $li = $('<li></li>').attr('data-flow-id', flow.id);
      if (QA.currentFlowId === flow.id) $li.addClass('qa-active');
      var $a = $('<a href="javascript:void(0)"></a>').text(flow.name);
      $a.on('click', function(){ $('#qa_tabs_ul>li').removeClass('qa-active'); $li.addClass('qa-active'); openFlow(flow.id, flow.name); });
      $li.append($a); $ul.append($li);
    });
  }

  function openFlow(flowId, flowName){
    ensureQuickModal();
    QA.currentFlowId = flowId;
    QA.selections = {};
    $('#qaQuickTitle').text('Quick Add   ' + flowName);
    $('#qa_quick_steps').html('<p>Loading </p>');
    $('#qa_add_to_cart_btn').prop('disabled', true);
    $('#qa_quick_modal').modal('show');
    loadRuntimeOptions();
  }
  function loadRuntimeOptions(){
    apiPOST('<?php echo site_url('quick_add/runtime_options'); ?>', {
      flow_id: QA.currentFlowId,
      selections: QA.selections,
      selections_json: JSON.stringify(QA.selections||{})
    }, function(d){
      renderRuntime(d.steps || []);
      updateAddEnabled();
    });
  }
  function renderRuntime(steps){
    var $w = $('#qa_quick_steps').empty();
    steps.forEach(function(step){
      var sid = step.id;
      var $g = $('<div class="form-group" style="margin-bottom:10px;"></div>');
      $g.append($('<label class="control-label"></label>').text(step.label));
      var $sel = $('<select class="form-control input-sm"></select>').attr('data-step-id', sid);
      $sel.append('<option value="">Select ' + step.label + '</option>');
      (step.options||[]).forEach(function(o){
        var $opt = $('<option></option>').val(o.id).text(o.label);
        if (QA.selections[sid] === o.id) $opt.prop('selected', true);
        $sel.append($opt);
      });
      if (!(step.options && step.options.length)) $sel.prop('disabled', true);
      $sel.on('change', function(){
        var v = $(this).val();
        if (!v){ delete QA.selections[sid]; } else { QA.selections[sid] = parseInt(v,10); }
        loadRuntimeOptions();
      });
      $g.append($sel); $w.append($g);
    });
    $('#qa_clear_btn').off('click').on('click', function(){ QA.selections = {}; loadRuntimeOptions(); $('#qa_add_to_cart_btn').prop('disabled', true); });
    $('#qa_add_to_cart_btn').off('click').on('click', resolveAndAdd);
  }
  function updateAddEnabled(){
    var ok = true;
    $('#qa_quick_steps select').each(function(){
      var hasOpts = $(this).find('option').length > 1;
      if (hasOpts && !$(this).val()) ok = false;
    });
    $('#qa_add_to_cart_btn').prop('disabled', !ok);
  }
  function resolveAndAdd(){
    var tuple = Object.keys(QA.selections).map(function(k){ return { step_id: parseInt(k,10), option_id: QA.selections[k] }; });
    if (!tuple.length){ alert('Please complete your selections.'); return; }
    var payload = { flow_id: QA.currentFlowId, selections_json: JSON.stringify(tuple) };
    for (var i = 0; i < tuple.length; i++){
      payload['selections['+i+'][step_id]'] = tuple[i].step_id;
      payload['selections['+i+'][option_id]'] = tuple[i].option_id;
    }
    apiPOST('<?php echo site_url('quick_add/resolve'); ?>', payload, function(d){
      var itemId = d.item_id || null;
      var kitId  = d.item_kit_id || null;
      if (!itemId && !kitId){ alert('No mapping found for that combination.'); return; }
      var addPayload = {}; addPayload[QA.csrfName] = QA.csrfHash;
      if (itemId){ addPayload.item = itemId; }
      else { addPayload.item_kit = kitId; }  // if your build does not accept this, backend will ignore; items still work
      $.post('<?php echo site_url('sales/add'); ?>', addPayload)
        .done(function(){ window.location.href = '<?php echo site_url('sales'); ?>'; })
        .fail(function(){ alert('Could not add to cart.'); });
    }, function(msg){ alert(msg || 'Unable to resolve selection.'); });
  }

  window.addEventListener('qa:settings:changed', function(){ loadFlows(); });

  $(function(){
    ensureQuickModal();
    ensureSettingsModal();
    loadFlows();
  });

})(jQuery);
</script>






<!-- =================== /Quick Add   Section 2 =================== -->


<!-- ======================= Quick Add   Section 3: Modals + Admin Designer (No Scopes/Deps) ======================= -->


<style>
  .qa-flex{display:flex;gap:10px;align-items:center}
  .qa-grid{display:grid;gap:8px}
  .qa-grid-2{grid-template-columns:1fr 1fr}
  .qa-grid-3{grid-template-columns:1fr 1fr 1fr}
  .qa-list{max-height:260px;overflow:auto;border:1px solid #eee;border-radius:4px;padding:6px}
  .qa-muted{color:#777}
  .qa-tight .form-group{margin-bottom:8px}
  .qa-table th,.qa-table td{vertical-align:middle!important}
  .qa-btn-xs{padding:2px 6px;line-height:1.2}
</style>

<div class="modal fade" id="qa_quick_modal" tabindex="-1" role="dialog" aria-labelledby="qaQuickTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="qaQuickTitle">Quick Add</h4>
      </div>
      <div class="modal-body"><div id="qa_quick_steps"></div></div>
      <div class="modal-footer">
        <button type="button" id="qa_clear_btn" class="btn btn-default">Clear / Reset</button>
        <button type="button" id="qa_add_to_cart_btn" class="btn btn-primary" disabled>Add to Cart</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="qa_settings_modal" tabindex="-1" role="dialog" aria-labelledby="qaSettingsTitle">
  <div class="modal-dialog modal-lg" role="document" style="width:100%;max-width:1100px;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="qaSettingsTitle">Universal Quick Add   Designer</h4>
      </div>

      <div class="modal-body qa-tight">
        <div class="row">
          <div class="col-sm-3">
            <div class="panel panel-default">
              <div class="panel-heading qa-flex" style="justify-content:space-between;">
                <strong>Flows</strong>
                <button id="qa_flow_add_btn" type="button" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-plus"></span></button>
              </div>
              <div class="panel-body"><div id="qa_flow_list" class="qa-list"></div></div>
            </div>

            <div class="panel panel-default">
              <div class="panel-heading"><strong>Flow Properties</strong></div>
              <div class="panel-body">
                <div class="form-group"><label>Name</label><input type="text" id="qa_flow_name" class="form-control input-sm" placeholder="Flow name"></div>
                <div class="qa-flex">
                  <div class="checkbox" style="margin:0;"><label><input type="checkbox" id="qa_flow_enabled"> Enabled</label></div>
                  <button id="qa_flow_save_btn" class="btn btn-success btn-sm">Save</button>
                  <button id="qa_flow_delete_btn" class="btn btn-danger btn-sm pull-right">Delete</button>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-9">
            <ul class="nav nav-tabs" role="tablist">
              <li class="active"><a href="#qa_tab_steps" role="tab" data-toggle="tab">Steps</a></li>
              <li><a href="#qa_tab_options" role="tab" data-toggle="tab">Options</a></li>
              <li><a href="#qa_tab_mappings" role="tab" data-toggle="tab">Mappings</a></li>
            </ul>

            <div class="tab-content" style="margin-top:10px;">
              <div role="tabpanel" class="tab-pane active" id="qa_tab_steps">
                <div class="panel panel-default">
                  <div class="panel-heading qa-flex" style="justify-content:space-between;">
                    <strong>Steps in Flow</strong>
                    <button id="qa_step_add_btn" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-plus"></span></button>
                  </div>
                  <div class="panel-body">
                    <table class="table table-striped table-condensed qa-table" id="qa_steps_table">
                      <thead><tr><th style="width:34px;">#</th><th>Label</th><th style="width:160px;">Actions</th></tr></thead>
                      <tbody></tbody>
                    </table>
                    <p class="qa-muted">Order here is the dropdown order at the register.</p>
                  </div>
                </div>
              </div>

              <div role="tabpanel" class="tab-pane" id="qa_tab_options">
                <div class="qa-grid qa-grid-2">
                  <div class="panel panel-default">
                    <div class="panel-heading"><strong>Select Step</strong></div>
                    <div class="panel-body">
                      <select id="qa_opt_step_select" class="form-control input-sm"></select>
                      <p class="qa-muted" style="margin-top:8px;">Add / rename / reorder options for the selected step.</p>
                    </div>
                  </div>
                  <div class="panel panel-default">
                    <div class="panel-heading"><strong>Add Option</strong></div>
                    <div class="panel-body"><button id="qa_opt_add_btn" class="btn btn-success btn-sm">Add Option</button></div>
                  </div>
                </div>
                <div class="panel panel-default" style="margin-top:10px;">
                  <div class="panel-heading qa-flex" style="justify-content:space-between;">
                    <strong>Options</strong><span class="qa-muted">Reorder / rename / delete</span>
                  </div>
                  <div class="panel-body">
                    <table class="table table-striped table-condensed qa-table" id="qa_options_table">
                      <thead><tr><th style="width:34px;">#</th><th>Label</th><th style="width:120px;">Actions</th></tr></thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>

              <div role="tabpanel" class="tab-pane" id="qa_tab_mappings">
                <div class="panel panel-default">
                  <div class="panel-heading"><strong>Create Mapping</strong></div>
                  <div class="panel-body">
                    <div id="qa_map_step_pickers" class="qa-grid qa-grid-3"></div>
                    <div class="qa-grid qa-grid-3" style="margin-top:10px;">
                      <div class="form-group">
                        <label>Find Item / Kit</label>
                        <div class="input-group">
                          <input type="text" id="qa_item_search" class="form-control input-sm" placeholder="Search by name or number">
                          <span class="input-group-btn"><button id="qa_item_search_btn" class="btn btn-default btn-sm" type="button">Search</button></span>
                        </div>
                        <select id="qa_item_results" class="form-control input-sm" style="margin-top:6px;"></select>
                        <div class="help-block">Item kits are prefixed with  (Kit) .</div>
                      </div>
                      <div class="form-group">
                        <label>&nbsp;</label>
                        <button id="qa_map_create_btn" class="btn btn-success btn-sm" style="display:block;">Create Mapping</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel panel-default">
                  <div class="panel-heading"><strong>Existing Mappings</strong></div>
                  <div class="panel-body">
                    <table class="table table-striped table-condensed qa-table" id="qa_mappings_table">
                      <thead><tr><th>Tuple</th><th style="width:160px;">Target</th><th style="width:80px;">Actions</th></tr></thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div> <!-- /tab-content -->
          </div> <!-- /col-sm-9 -->
        </div> <!-- /row -->
      </div>

      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="qa_step_editor_modal" tabindex="-1" role="dialog" aria-labelledby="qaStepEditorTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="qaStepEditorTitle">Add Step</h4>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Label</label><input type="text" id="qa_step_label" class="form-control input-sm" placeholder="Step label"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" id="qa_step_save_btn" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="qa_option_editor_modal" tabindex="-1" role="dialog" aria-labelledby="qaOptionEditorTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="qaOptionEditorTitle">Add Option</h4>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Option label</label><input type="text" id="qa_opt_label_input" class="form-control input-sm" placeholder="e.g., Pine / Queen"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" id="qa_option_save_btn" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
(function($){
  "use strict";

  function csrf(){ return { name: $('#qa_csrf_name').val() || '<?php echo $this->security->get_csrf_token_name(); ?>', hash: $('#qa_csrf_hash').val() || '<?php echo $this->security->get_csrf_hash(); ?>' }; }
  function setCsrf(r){ var c=(r&&r.csrf)||(r&&r.data&&r.data.csrf); if(c&&c.name&&c.hash){ $('#qa_csrf_name').val(c.name); $('#qa_csrf_hash').val(c.hash);} }
  function okWrap(r){ if(r && typeof r==='object' && 'ok' in r){ return {ok:!!r.ok, data:r.data||{}, message:r.message||''}; } return {ok:true, data:r||{}, message:''}; }
  function getJSON(u,d,ok,err){ $.ajax({url:u,method:'GET',data:d||{},dataType:'json'}).done(function(r){setCsrf(r);var n=okWrap(r);if(n.ok){ok&&ok(n.data);}else{(err||alert)(n.message||'Request failed');}}).fail(function(x){(err||alert)((x.responseJSON&&x.responseJSON.message)||'Network error');}); }
  function postJSON(u,d,ok,err){ var c=csrf(); var p=$.extend({},d||{}); p[c.name]=c.hash; $.ajax({url:u,method:'POST',data:p,dataType:'json',traditional:true}).done(function(r){setCsrf(r);var n=okWrap(r);if(n.ok){ok&&ok(n.data);}else{(err||alert)(n.message||'Request failed');}}).fail(function(x){(err||alert)((x.responseJSON&&x.responseJSON.message)||'Network error');}); }
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);}); }

  var ST = { flows:[], currentFlowId:null, steps:[], optionsByStep:{}, runtimeSteps:[], mapSelections:{} };

  $('#qa_settings_modal').on('shown.bs.modal', function(){ loadFlows(); }).on('hidden.bs.modal', function(){
    ST.flows=[]; ST.currentFlowId=null; ST.steps=[]; ST.optionsByStep={}; ST.runtimeSteps=[]; ST.mapSelections={};
    $('#qa_flow_list').empty(); $('#qa_flow_name').val(''); $('#qa_flow_enabled').prop('checked',false);
    $('#qa_steps_table tbody').empty(); $('#qa_opt_step_select').empty(); $('#qa_options_table tbody').empty();
    $('#qa_map_step_pickers').empty(); $('#qa_mappings_table tbody').empty();
    $('#qa_item_search').val(''); $('#qa_item_results').empty();
  });

  function loadFlows(){ getJSON('<?php echo site_url('quick_add/flows'); ?>', {}, function(d){
    ST.flows = d.flows || [];
    renderFlowList();
    if (!ST.currentFlowId && ST.flows.length) selectFlow(ST.flows[0].id);
  }); }
  function renderFlowList(){
    var $w=$('#qa_flow_list').empty();
    if (!ST.flows.length){ $w.append('<div class="qa-muted">No flows yet. Use the + button to create one.</div>'); return; }
    ST.flows.forEach(function(f){
      var row=$('<div class="qa-flex"></div>').css({justifyContent:'space-between',padding:'4px 2px',borderBottom:'1px solid #f3f3f3'});
      var a=$('<a href="javascript:void(0)"></a>').text(f.name+(f.is_enabled?'':' (disabled)')).on('click',function(){selectFlow(f.id);});
      var btns=$('<span></span>');
      btns.append($('<button class="btn btn-default btn-xs qa-btn-xs" title="Up"><span class="glyphicon glyphicon-chevron-up"></span></button>').on('click',function(){flowNudge(f.id,-1);})); btns.append(' ');
      btns.append($('<button class="btn btn-default btn-xs qa-btn-xs" title="Down"><span class="glyphicon glyphicon-chevron-down"></span></button>').on('click',function(){flowNudge(f.id,1);})); 
      row.append(a).append(btns); $w.append(row);
    });
  }
  function selectFlow(id){
    ST.currentFlowId=parseInt(id,10);
    var f=ST.flows.find(function(x){return x.id===ST.currentFlowId;});
    $('#qa_flow_name').val(f?f.name:''); $('#qa_flow_enabled').prop('checked',!!(f&&f.is_enabled));
    loadSteps(); loadMappings();
  }
  $(document).off('click.qaFlowAdd').on('click.qaFlowAdd','#qa_flow_add_btn',function(){
    var name=prompt('New flow name:'); if(!name) return;
    postJSON('<?php echo site_url('quick_add/flows_create'); ?>',{name:name,is_enabled:true},function(){loadFlows(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
  });
  $('#qa_flow_save_btn').on('click',function(){
    if(!ST.currentFlowId) return;
    postJSON('<?php echo site_url('quick_add/flows_update'); ?>',{id:ST.currentFlowId,name:$('#qa_flow_name').val(),is_enabled:$('#qa_flow_enabled').is(':checked')},function(){
      loadFlows(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));
    });
  });
  $('#qa_flow_delete_btn').on('click',function(){
    if(!ST.currentFlowId) return;
    if(!confirm('Delete this flow and all of its steps/options/mappings?')) return;
    postJSON('<?php echo site_url('quick_add/flows_delete'); ?>',{id:ST.currentFlowId},function(){
      ST.currentFlowId=null; loadFlows();
      $('#qa_steps_table tbody').empty(); $('#qa_opt_step_select').empty(); $('#qa_options_table tbody').empty();
      $('#qa_map_step_pickers').empty(); $('#qa_mappings_table tbody').empty();
      window.dispatchEvent(new CustomEvent('qa:settings:changed'));
    });
  });
  function flowNudge(flowId,dir){
    var arr=ST.flows.slice().sort(function(a,b){return(a.sort_order||0)-(b.sort_order||0)||a.id-b.id;});
    var idx=arr.findIndex(function(x){return x.id===flowId;}); var tgt=idx+dir; if(tgt<0||tgt>=arr.length) return;
    var tmp=arr[idx]; arr[idx]=arr[tgt]; arr[tgt]=tmp;
    var ops=[]; arr.forEach(function(f,i){ ops.push(new Promise(function(res){ postJSON('<?php echo site_url('quick_add/flows_update'); ?>',{id:f.id,sort_order:(i+1)},function(){res();}); })); });
    Promise.all(ops).then(function(){ loadFlows(); window.dispatchEvent(new CustomEvent('qa:settings:changed')); });
  }

  function loadSteps(){ if(!ST.currentFlowId) return;
    getJSON('<?php echo site_url('quick_add/steps'); ?>',{flow_id:ST.currentFlowId},function(d){
      ST.steps=(d.steps||[]).map(function(s){s.id=parseInt(s.id,10);return s;});
      renderSteps(); renderOptStepSelect(); ST.mapSelections={}; buildMapPickers();
    });
  }
  function renderSteps(){
    var $tb=$('#qa_steps_table tbody').empty();
    if(!ST.steps.length){$tb.append('<tr><td colspan="3" class="qa-muted">No steps yet.</td></tr>');return;}
    ST.steps.forEach(function(s,i){
      var tr=$('<tr></tr>');
      tr.append('<td class="qa-muted">'+(i+1)+'</td>');
      tr.append('<td>'+esc(s.label)+'</td>');
      var a=$('<td></td>');
      a.append(stepBtnNudge(s.id,-1)).append(' ');
      a.append(stepBtnNudge(s.id, 1)).append(' ');
      a.append(stepBtnEdit(s)).append(' ');
      a.append(stepBtnDel(s.id));
      tr.append(a); $tb.append(tr);
    });
  }
  function stepBtnNudge(id,dir){
    return $('<button class="btn btn-default btn-xs qa-btn-xs"><span class="glyphicon glyphicon-chevron-'+(dir<0?'up':'down')+'"></span></button>').on('click',function(){
      var ids=ST.steps.map(function(s){return s.id;}); var idx=ids.indexOf(id),t=idx+dir; if(t<0||t>=ids.length) return;
      var tmp=ids[idx]; ids[idx]=ids[t]; ids[t]=tmp;
      var p={flow_id:ST.currentFlowId,ordered_ids_json:JSON.stringify(ids)}; for(var i=0;i<ids.length;i++){p['ordered_ids['+i+']']=ids[i];}
      postJSON('<?php echo site_url('quick_add/steps_reorder'); ?>',p,function(){loadSteps(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
    });
  }
  function stepBtnDel(id){
    return $('<button class="btn btn-danger btn-xs qa-btn-xs"><span class="glyphicon glyphicon-trash"></span></button>').on('click',function(){
      if(!confirm('Delete this step and its options/mappings?')) return;
      postJSON('<?php echo site_url('quick_add/steps_delete'); ?>',{id:id},function(){loadSteps(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
    });
  }
  function stepBtnEdit(step){
    return $('<button class="btn btn-default btn-xs qa-btn-xs"><span class="glyphicon glyphicon-pencil"></span></button>').on('click',function(){ openStepEditor(step); });
  }
  $('#qa_step_add_btn').on('click',function(){ openStepEditor(null); });
  function openStepEditor(step){
    var edit=!!step; $('#qaStepEditorTitle').text(edit?'Edit Step':'Add Step'); $('#qa_step_label').val(edit?step.label:'');
    $('#qa_step_save_btn').off('click').on('click',function(){
      var label=$.trim($('#qa_step_label').val()); if(!label){alert('Label is required.');return;}
      if(edit){ postJSON('<?php echo site_url('quick_add/steps_update'); ?>',{id:step.id,label:label},function(){ $('#qa_step_editor_modal').modal('hide'); loadSteps(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));}); }
      else{ postJSON('<?php echo site_url('quick_add/steps_create'); ?>',{flow_id:ST.currentFlowId,label:label},function(){ $('#qa_step_editor_modal').modal('hide'); loadSteps(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));}); }
    });
    $('#qa_step_editor_modal').modal('show');
  }

  function renderOptStepSelect(){
    var $s=$('#qa_opt_step_select').empty();
    if(!ST.steps.length){ $s.append('<option value="">No steps yet</option>'); return; }
    ST.steps.forEach(function(s){ $s.append($('<option></option>').val(s.id).text(s.label)); });
    $s.off('change').on('change',function(){ var sid=parseInt($(this).val(),10); if(!sid){$('#qa_options_table tbody').empty();return;} loadOptions(sid); });
    $s.trigger('change');
  }
  function loadOptions(stepId,cb){
    getJSON('<?php echo site_url('quick_add/options'); ?>',{step_id:stepId,raw:1},function(d){
      ST.optionsByStep[stepId]=(d.options||[]).map(function(o){o.id=parseInt(o.id,10);return o;});
      renderOptions(stepId); if(cb) cb();
    });
  }
  function renderOptions(stepId){
    var $tb=$('#qa_options_table tbody').empty(); var rows=ST.optionsByStep[stepId]||[];
    if(!rows.length){$tb.append('<tr><td colspan="3" class="qa-muted">No options yet.</td></tr>');return;}
    rows.forEach(function(o,i){
      var tr=$('<tr></tr>');
      tr.append('<td class="qa-muted">'+(i+1)+'</td>');
      tr.append('<td>'+esc(o.label)+'</td>');
      var a=$('<td></td>');
      a.append(optBtnNudge(stepId,o.id,-1)).append(' ');
      a.append(optBtnNudge(stepId,o.id, 1)).append(' ');
      a.append(optBtnRename(stepId,o)).append(' ');
      a.append(optBtnDel(stepId,o.id));
      tr.append(a); $tb.append(tr);
    });
  }
  function optBtnNudge(stepId,optId,dir){
    return $('<button class="btn btn-default btn-xs qa-btn-xs"><span class="glyphicon glyphicon-chevron-'+(dir<0?'up':'down')+'"></span></button>').on('click',function(){
      var rows=(ST.optionsByStep[stepId]||[]).slice(); var ids=rows.map(function(r){return r.id;});
      var idx=ids.indexOf(optId),t=idx+dir; if(t<0||t>=ids.length) return;
      var tmp=ids[idx]; ids[idx]=ids[t]; ids[t]=tmp;
      var p={step_id:stepId,ordered_ids_json:JSON.stringify(ids)}; for(var i=0;i<ids.length;i++){p['ordered_ids['+i+']']=ids[i];}
      postJSON('<?php echo site_url('quick_add/options_reorder'); ?>',p,function(){loadOptions(stepId); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
    });
  }
  function optBtnRename(stepId,opt){
    return $('<button class="btn btn-default btn-xs qa-btn-xs"><span class="glyphicon glyphicon-pencil"></span></button>').on('click',function(){
      var v=prompt('New label:',opt.label); if(!v) return;
      postJSON('<?php echo site_url('quick_add/options_update'); ?>',{id:opt.id,label:v},function(){loadOptions(stepId); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
    });
  }
  function optBtnDel(stepId,optId){
    return $('<button class="btn btn-danger btn-xs qa-btn-xs"><span class="glyphicon glyphicon-trash"></span></button>').on('click',function(){
      if(!confirm('Delete this option? It will be removed from mappings.')) return;
      postJSON('<?php echo site_url('quick_add/options_delete'); ?>',{id:optId},function(){loadOptions(stepId); window.dispatchEvent(new CustomEvent('qa:settings:changed'));});
    });
  }
  $('#qa_opt_add_btn').off('click').on('click',function(){
    var sid=parseInt($('#qa_opt_step_select').val(),10); if(!sid){alert('Select a step first.');return;} openOptionEditor(sid);
  });
  function openOptionEditor(stepId){
    var step=ST.steps.find(function(s){return s.id===stepId;}); if(!step){alert('Step not found.');return;}
    $('#qaOptionEditorTitle').text('Add Option to "'+step.label+'"'); $('#qa_opt_label_input').val('');
    $('#qa_option_save_btn').off('click').on('click',function(){
      var label=$.trim($('#qa_opt_label_input').val()); if(!label){alert('Label is required.');return;}
      postJSON('<?php echo site_url('quick_add/options_create'); ?>',{step_id:stepId,label:label},function(){
        $('#qa_option_editor_modal').modal('hide'); loadOptions(stepId); window.dispatchEvent(new CustomEvent('qa:settings:changed'));
      });
    });
    $('#qa_option_editor_modal').modal('show');
  }

  function buildMapPickers(){
    if(!ST.currentFlowId) return;
    var partial={}; Object.keys(ST.mapSelections).forEach(function(k){ if(ST.mapSelections[k]!=='*'){ partial[k]=ST.mapSelections[k]; } });
    postJSON('<?php echo site_url('quick_add/runtime_options'); ?>',{flow_id:ST.currentFlowId,selections:partial,selections_json:JSON.stringify(partial)},function(d){
      ST.runtimeSteps=d.steps||[];
      var $w=$('#qa_map_step_pickers').empty();
      ST.runtimeSteps.forEach(function(s){
        var sel=$('<select class="form-control input-sm qa-map-sel"></select>').attr('data-step-id',s.id);
        sel.append('<option value="">* (Any)</option>');
        (s.options||[]).forEach(function(o){
          var op=$('<option></option>').val(o.id).text(o.label);
          if(String(ST.mapSelections[s.id]||'')===String(o.id)) op.prop('selected',true);
          sel.append(op);
        });
        var grp=$('<div class="form-group"></div>'); grp.append('<label>'+esc(s.label)+'</label>').append(sel); $w.append(grp);
      });
      $(document).off('change.qaMapSel').on('change.qaMapSel','.qa-map-sel',function(){
        var sid=parseInt($(this).attr('data-step-id'),10); var v=$(this).val(); ST.mapSelections[sid]=v?parseInt(v,10):'*'; buildMapPickers();
      });
      loadMappings();
    });
  }
  function loadMappings(){ if(!ST.currentFlowId) return;
    getJSON('<?php echo site_url('quick_add/mappings'); ?>',{flow_id:ST.currentFlowId},function(d){ renderMappings(d.mappings||[]); });
  }
  function renderMappings(rows){
    var $tb=$('#qa_mappings_table tbody').empty();
    if(!rows.length){ $tb.append('<tr><td colspan="3" class="qa-muted">No mappings yet.</td></tr>'); return; }
    rows.forEach(function(m){
      var tupleArr=[]; try{ tupleArr=Array.isArray(m.key)?m.key:JSON.parse(m.key_json||'[]'); }catch(e){ tupleArr=[]; }
      var tuple=(tupleArr||[]).map(function(k){ return stepLabel(k.step_id)+': '+(k.option_id==='*'?'*':optLabel(k.step_id,k.option_id)); }).join(' | ');
      var target = m.item_kit_id ? ('Kit #'+m.item_kit_id) : ('Item #'+m.item_id);
      var tr=$('<tr></tr>');
      tr.append('<td>'+esc(tuple)+'</td>');
      tr.append('<td>'+esc(target)+'</td>');
      var td=$('<td></td>');
      td.append($('<button class="btn btn-danger btn-xs qa-btn-xs"><span class="glyphicon glyphicon-trash"></span></button>').on('click',function(){
        if(!confirm('Delete this mapping?')) return;
        postJSON('<?php echo site_url('quick_add/mappings_delete'); ?>',{id:m.id},function(){ loadMappings(); window.dispatchEvent(new CustomEvent('qa:settings:changed')); });
      }));
      tr.append(td); $tb.append(tr);
    });
  }

  function parseIntLoose(v){ var n=parseInt(v,10); return isNaN(n)?null:n; }
  function extractIdFromLabel(label){
    var m = String(label||'').match(/(^|\D)(\d{1,9})(\D|$)/); return m ? parseInt(m[2],10) : null;
  }
  function doSearch(){
    var q=$.trim($('#qa_item_search').val());
    var itemsReq = $.ajax({ url:'<?php echo site_url('quick_add/items_search'); ?>', data:{ q:q, limit:20 }, dataType:'json' });
    var kitsReq  = $.ajax({ url:'<?php echo site_url('item_kits/suggest'); ?>',      data:{ term:q },          dataType:'json' });
    $.when(itemsReq, kitsReq).done(function(itemsRes, kitsRes){
      var items = (itemsRes[0] && itemsRes[0].data && itemsRes[0].data.results) || itemsRes[0] || [];
      var kits  = kitsRes[0] || [];

      var options=[];
      $.each(items, function(_,r){
        if (r && (r.id||r.item_id)){
          var id = r.id || r.item_id;
          var text = r.text || r.label || ('Item #'+id);
          options.push({ value:'item:'+id, text:text });
        }
      });

      $.each(kits, function(_,k){
        var id = k.id || parseIntLoose(k.value) || extractIdFromLabel(k.label);
        if (!id) return;
        var text = k.text || k.label || ('Kit #'+id);
        if (text.indexOf('(Kit)') !== 0) text = '(Kit) ' + text;
        options.push({ value:'kit:'+id, text:text });
      });

      var $sel=$('#qa_item_results').empty();
      if (!options.length){ $sel.append('<option value="">No results</option>'); return; }
      $.each(options, function(_,o){ $sel.append($('<option></option>').val(o.value).text(o.text)); });
    }).fail(function(){
      // Fallback: items only
      $.getJSON('<?php echo site_url('quick_add/items_search'); ?>',{ q:q, limit:20 }).done(function(r){
        var items=(r&&r.data&&r.data.results)||r||[];
        var $sel=$('#qa_item_results').empty();
        $.each(items,function(_,it){ if(it&&it.id){ $sel.append($('<option></option>').val('item:'+it.id).text(it.text||('Item #'+it.id))); }});
      });
    });
  }
  $(document).off('click.qaSearch').on('click.qaSearch','#qa_item_search_btn',function(e){ e.preventDefault(); doSearch(); });
  $(document).off('keydown.qaSearch').on('keydown.qaSearch','#qa_item_search',function(e){ if(e.which===13){ e.preventDefault(); doSearch(); } });

  $('#qa_map_create_btn').on('click',function(){
    if(!ST.currentFlowId) return;
    var sel=$('#qa_item_results').val(); if(!sel){ alert('Choose an Item or Item Kit.'); return; }
    var parts=String(sel).split(':'); var typ=(parts[0]||'item').toLowerCase(); var id=parseInt(parts[1]||'0',10); if(!id){ alert('Invalid selection.'); return; }

    var tuple=[]; ST.steps.forEach(function(s){ var v=ST.mapSelections[s.id]; if(v==='*'){ tuple.push({step_id:s.id,option_id:'*'}); } else if(typeof v==='number'&&v>0){ tuple.push({step_id:s.id,option_id:v}); }});
    if(!tuple.length){ alert('Pick tuple values first.'); return; }

    var p={ flow_id:ST.currentFlowId, key_json:JSON.stringify(tuple) };
    for(var i=0;i<tuple.length;i++){ p['key['+i+'][step_id]']=tuple[i].step_id; p['key['+i+'][option_id]']=tuple[i].option_id; }
    if(typ==='item'){ p.item_id=id; } else { p.target_type='kit'; p.item_kit_id=id; }

    postJSON('<?php echo site_url('quick_add/mappings_create'); ?>', p, function(){
      alert('Mapping created.'); loadMappings(); window.dispatchEvent(new CustomEvent('qa:settings:changed'));
    }, function(msg){
      if(typ==='kit'){ alert((msg||'This server build may not support mapping kits yet. Items will still work.')); }
      else { alert(msg||'Create mapping failed.'); }
    });
  });

  function stepLabel(step_id){ var s=ST.steps.find(function(x){return x.id===step_id;}); return s?s.label:('#'+step_id); }
  function optLabel(step_id, option_id){
    var arr=ST.optionsByStep[step_id]||[]; var o=arr.find(function(x){return x.id===option_id;}); if(o) return o.label;
    var rs=ST.runtimeSteps.find(function(x){return x.id===step_id;}); if(rs){ var ro=(rs.options||[]).find(function(x){return x.id===option_id;}); if(ro) return ro.label; }
    return '#'+option_id;
  }

})(jQuery);
</script>




<?php $this->load->view("partial/footer"); ?>
