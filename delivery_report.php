<?php
// Variables expected:
// $rows, $filters, $result_count, $filter_type
// $active_from_disp, $active_to_disp
// $sale_date_from, $sale_date_to, $delivery_date_from, $delivery_date_to
// $gcal_enabled, $gcal_calendar_id

$label_for_picker = 'Delivery Date Range';
if ($filter_type === 'sale_date') $label_for_picker = 'Sale Date Range';
if ($filter_type === 'local_delivery_only') $label_for_picker = 'Local Delivery Only Date Range';

// CSRF
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>

<style>
/* ---------- Filters / layout ---------- */
.report-filters .form-control{height:44px;padding:8px 12px;font-size:14px}
.report-filters .control-label{display:block;font-weight:600;margin-bottom:6px}
.report-filters .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:768px){.report-filters .grid{grid-template-columns:1fr}}

/* ---------- Table + chips ---------- */
td.items-cell{white-space:normal;word-break:break-word;line-height:1.35}
.badge-status{display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px;color:#fff}
.badge-pending{background:#d9534f}.badge-complete{background:#5cb85c}

/* Date pill (keep your existing color) */
.badge-date{
  display:inline-block;padding:6px 10px;border-radius:14px;
  background:#e8f0fe;border:1px solid #c6dafc;color:#174ea6;
  font-weight:600;font-size:13px;line-height:1
}

.text-muted-sm{color:#666;font-size:12px}
.table-actions .btn{margin-right:6px;margin-bottom:6px}

/* Customer bits */
.address-line{color:#222;font-weight:600;margin-top:2px}
.sale-link{color:#2367d1;text-decoration:none}
.sale-link:hover,.sale-link:focus{text-decoration:underline}

/* ---------- Delivery column (clean) ---------- */
.delivery-stack{ text-align:center }

/* Weekday = outlined tag (distinct from blue date pill) */
.weekday-tag{
  display:inline-block;padding:5px 10px;border-radius:16px;
  border:2px solid #8b5cf6;color:#5b21b6;background:#fff;
  font-weight:800;font-size:13px;letter-spacing:.2px;text-transform:uppercase;
  line-height:1;margin-bottom:6px
}

/* Time + phone = bold & darker */
.time-line{color:#111;font-weight:700;margin-top:8px;font-size:13px}
.phone-line{color:#111;font-weight:700;margin-top:2px;font-size:13px}

/* ---------- Mobile readability ---------- */
@media (max-width:768px){
  .delivery-report-wrap{font-size:16px;line-height:1.45}
  .report-filters .form-control{height:52px;font-size:16px}
  .report-filters .control-label{font-size:15px}
  .table>thead>tr>th,.table>tbody>tr>td{font-size:16px;padding:10px 8px}
  .badge-status,.badge-date{font-size:13px;padding:6px 10px}
  .btn{font-size:15px;padding:8px 12px}
  .text-muted-sm{font-size:13px}
  .weekday-tag{font-size:14px}
  .time-line{font-size:14px}
  .phone-line{font-size:16px}
}

/* Items list: one consistent gap between rows */
.items-list .item-row{
  display:flex;
  align-items:baseline;
  gap:6px;            /* space between name and qty */
  margin:0;           /* no extra margins on each row */
}
.items-list .item-row + .item-row{
  margin-top:4px;     /* <-- exactly one “space” between items */
}
.items-list .item-name{
  flex:1 1 auto;
  white-space:nowrap;     /* single line */
  overflow:hidden;
  text-overflow:ellipsis; /* … if too long */
}
.items-list .item-qty{
  flex:0 0 auto;
  white-space:nowrap;
  color:#555;
}

/* slightly larger gap on mobile for readability */
@media (max-width:768px){
  .items-list .item-row + .item-row{ margin-top:6px; }
}


/* Time pill (teal) */
.time-pill{
  display:inline-block;
  padding:6px 10px;
  border-radius:14px;
  background:#e6fffb;      /* light teal */
  border:1px solid #b7f4ee; /* teal border */
  color:#0f766e;           /* deep teal text */
  font-weight:700;
  font-size:13px;
  line-height:1;
}
@media (max-width:768px){
  .time-pill{ font-size:14px; padding:6px 10px; }
}




</style>


<div class="container-fluid delivery-report-wrap">
	<?php echo form_open('sales/delivery_report', array('id' => 'dr-form', 'method' => 'get', 'class' => 'report-filters')); ?>
		<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>"/>

		<!-- Keep both pairs in hidden inputs (yyyy-mm-dd) -->
		<input type="hidden" name="sale_date_from" id="sale_date_from" value="<?php echo html_escape($sale_date_from); ?>">
    <input type="hidden" name="sale_date_to" id="sale_date_to" value="<?php echo html_escape($sale_date_to); ?>">
    <input type="hidden" name="delivery_date_from" id="delivery_date_from" value="<?php echo html_escape($delivery_date_from); ?>">
    <input type="hidden" name="delivery_date_to" id="delivery_date_to" value="<?php echo html_escape($delivery_date_to); ?>">
    <input type="hidden" name="page" id="page" value="<?php echo (int)$page; ?>">

    
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ti-truck"></i> Delivery Report
					<small class="text-muted"> • <?php echo (int)$total; ?> results • Google Calendar: <strong><?php echo $gcal_enabled ? 'ON' : 'OFF'; ?></strong></small>
				</h3>
			</div>
			<div class="panel-body report-filters">

				<div class="grid">
					<div>
						<label class="control-label" for="filter_type">Filter Type</label>
						<select id="filter_type" name="filter_type" class="form-control">
							<option value="delivery_date" <?php echo $filter_type==='delivery_date'?'selected':''; ?>>Delivery Date Range</option>
							<option value="sale_date" <?php echo $filter_type==='sale_date'?'selected':''; ?>>Sale Date Range</option>
							<option value="local_delivery_only" <?php echo $filter_type==='local_delivery_only'?'selected':''; ?>>Local Delivery Only Date Range</option>
						</select>
					</div>
					<div>
						<label class="control-label" for="dr-daterange" id="dr-label"><?php echo html_escape($label_for_picker); ?></label>
						<input type="text" id="dr-daterange" class="form-control" value="<?php echo html_escape($active_from_disp . ' - ' . $active_to_disp); ?>" readonly>
					</div>
				</div>
			</div>
		</div>
	<?php echo form_close(); ?>

	<div class="panel panel-piluku">
		<div class="panel-heading">
			<h3 class="panel-title">
				Results
			</h3>
		</div>

		<div class="panel-body">
			<div class="table-actions" style="margin-bottom:8px;">
				<button type="button" class="btn btn-success btn-sm" id="btn-selected-complete">
					<i class="fa fa-check-square-o"></i> Mark selected complete
				</button>
				<button type="button" class="btn btn-default btn-sm" id="btn-selected-uncomplete">
					<i class="fa fa-undo"></i> Unmark selected
				</button>
				<button type="button" class="btn btn-primary btn-sm" id="btn-all-complete">
					<i class="fa fa-check"></i> Mark all complete
				</button>
			</div>

			<div class="table-responsive">
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<tr>
							<th style="width:36px;">
								<input type="checkbox" id="check-all">
							</th>
							<th>Sale</th>
							<th>Customer</th>
							<th>Delivery</th>
							<th>Items</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php if (empty($rows)): ?>
						<tr><td colspan="7" class="text-center text-muted">No results.</td></tr>
					<?php else: foreach ($rows as $r): ?>
						<tr class="sale-row" data-sale-id="<?php echo (int)$r['sale_id']; ?>" data-gcal-id="<?php echo html_escape($r['gcal_event_id']); ?>">
							<td>
								<input type="checkbox" class="row-check" value="<?php echo (int)$r['sale_id']; ?>">
							</td>
							<td>
  <a class="sale-link" target="_blank" rel="noopener"
     href="<?php echo site_url('sales/receipt/' . (int)$r['sale_id']); ?>">
     <strong>#<?php echo (int)$r['sale_id']; ?> Receipt</strong>
  </a><br>
  <span class="text-muted-sm"><?php echo date('m/d/Y h:ia', strtotime($r['sale_time'])); ?></span>
</td>

							<td>
  <div><strong><?php echo html_escape($r['customer_name']); ?></strong></div>

  <?php
    // Safe address builder
    $a1    = trim((string)$r['address_1']);
    $a2    = trim((string)$r['address_2']);
    $city  = trim((string)$r['city']);
    $state = trim((string)$r['state']);
    $zip   = trim((string)$r['postal_code']);

    $line1 = trim($a1 . ($a2 !== '' ? ' ' . $a2 : ''));

    $line2 = '';
    if ($city !== '' || $state !== '' || $zip !== '')
    {
      // Add comma only when city and state both present
      $line2 = $city
             . (($city !== '' && $state !== '') ? ', ' : '')
             . $state
             . ($zip !== '' ? ' ' . $zip : '');
      // Strip any accidental leading/trailing commas/spaces
      $line2 = trim($line2, " \t\n\r\0\x0B,");
    }

    // Join non-empty parts with ", "
    $addr_out = implode(', ', array_values(array_filter([$line1, $line2], function($v){ return $v !== ''; })));
  ?>

  <?php if ($addr_out !== ''): ?>
    <div class="address-line"><?php echo html_escape($addr_out); ?></div>
  <?php endif; ?>

  <?php if (!empty($r['customer_phone'])): ?>
    <div class="phone-line"><?php echo html_escape($r['customer_phone']); ?></div>
  <?php endif; ?>
</td>

							<td>
  <?php if (!empty($r['delivery_date'])): ?>
    <div class="delivery-stack">
      <span class="weekday-tag">
        <?php echo date('l', strtotime($r['delivery_date'])); ?>
      </span><br>
      <span class="badge-date">
        <?php echo date('m/d/Y', strtotime($r['delivery_date'])); ?>
      </span>
      <?php if (!empty($r['delivery_time_label'])): ?>
  <div style="margin-top:8px;">
    <span class="time-pill"><?php echo html_escape($r['delivery_time_label']); ?></span>
  </div>
<?php endif; ?>
    </div>
  <?php else: ?>
    <span class="text-muted-sm">No delivery date</span>
  <?php endif; ?>
</td>


							<td class="items-cell">
								<div class="items-list">
									<?php echo $r['items_html']; ?>
								</div>
							</td>
							<td>
								<?php if ($r['is_complete']): ?>
									<span class="badge-status badge-complete">Complete</span>
								<?php else: ?>
									<span class="badge-status badge-pending">Pending</span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button"
									class="btn btn-xs btn-<?php echo $r['is_complete'] ? 'default' : 'success'; ?> btn-toggle-complete"
									data-done="<?php echo $r['is_complete'] ? 0 : 1; ?>">
									<?php echo $r['is_complete'] ? 'Unmark' : 'Mark Complete'; ?>
								</button>

								<?php if ($gcal_enabled): ?>
									<button type="button"
											class="btn btn-xs btn-info btn-sync-gcal">
										<?php echo $r['gcal_event_id'] ? 'Update Calendar' : 'Sync to Calendar'; ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>



<?php
	$total    = isset($total) ? (int)$total : 0;
	$page     = isset($page) ? (int)$page : 1;
	$per_page = isset($per_page) ? (int)$per_page : 25;
	$pages    = isset($pages) ? (int)$pages : 1;

	$start_item = $total ? (($page - 1) * $per_page + 1) : 0;
	$end_item   = min($total, $page * $per_page);

	$base_qs  = isset($query_string_base) && strlen($query_string_base) ? $query_string_base : '';
	$base_url = site_url('sales/delivery_report') . (strlen($base_qs) ? ('?' . $base_qs . '&') : '?');
?>

<div class="row" style="margin-top:12px; align-items:center;">
	<div class="col-sm-6" style="margin-bottom:10px;">
		<div class="form-inline">
			<label for="per-page-select" style="margin-right:8px;">Rows per page:</label>
			<select id="per-page-select" name="per_page" form="dr-form" class="form-control input-sm" style="width:auto; display:inline-block;">
				<option value="25"  <?php echo $per_page==25  ? 'selected' : ''; ?>>25</option>
				<option value="50"  <?php echo $per_page==50  ? 'selected' : ''; ?>>50</option>
				<option value="100" <?php echo $per_page==100 ? 'selected' : ''; ?>>100</option>
			</select>
			<span class="text-muted" style="margin-left:12px;">
				<?php echo $total ? "Showing {$start_item}-{$end_item} of {$total}" : "No results"; ?>
			</span>
		</div>
	</div>

	<div class="col-sm-6 text-right">
		<ul class="pagination pagination-sm" style="margin:0;">
			<?php if ($page > 1): ?>
				<li><a href="<?php echo $base_url.'per_page='.$per_page.'&page='.($page-1); ?>">&laquo;</a></li>
			<?php else: ?>
				<li class="disabled"><span>&laquo;</span></li>
			<?php endif; ?>

			<?php
				$from = max(1, $page - 3);
				$to   = min($pages, $page + 3);
				if ($from > 1) {
					echo '<li><a href="' . $base_url.'per_page='.$per_page.'&page=1">1</a></li>';
					if ($from > 2) echo '<li class="disabled"><span>…</span></li>';
				}
				for ($p = $from; $p <= $to; $p++) {
					if ($p == $page) {
						echo '<li class="active"><span>'.$p.'</span></li>';
					} else {
						echo '<li><a href="' . $base_url.'per_page='.$per_page.'&page='.$p.'">'.$p.'</a></li>';
					}
				}
				if ($to < $pages) {
					if ($to < $pages - 1) echo '<li class="disabled"><span>…</span></li>';
					echo '<li><a href="' . $base_url.'per_page='.$per_page.'&page='.$pages.'">'.$pages.'</a></li>';
				}
			?>

			<?php if ($page < $pages): ?>
				<li><a href="<?php echo $base_url.'per_page='.$per_page.'&page='.($page+1); ?>">&raquo;</a></li>
			<?php else: ?>
				<li class="disabled"><span>&raquo;</span></li>
			<?php endif; ?>
		</ul>
	</div>
</div>




<script type="text/javascript">
(function() {
	// CSRF setup (OSPOS usually does this globally; keep local as a fallback)
	var CSRF_NAME = <?php echo json_encode($csrf_name); ?>;
	var CSRF_HASH = <?php echo json_encode($csrf_hash); ?>;

	function addCsrf(payload) {
		payload = payload || {};
		payload[CSRF_NAME] = CSRF_HASH;
		return payload;
	}

	// Filter type change => submit immediately (preserve both ranges)
	document.getElementById('filter_type').addEventListener('change', function() {
	$('#page').val(1);
	document.getElementById('dr-form').submit();
});

	// Daterangepicker (use same plugin as Daily Sales)
	$(function() {
  // Use either MM/DD/YYYY or YYYY-MM-DD (we'll parse both)
  var start = moment(<?php echo json_encode($active_from_disp); ?>, ['MM/DD/YYYY','YYYY-MM-DD']);
  var end   = moment(<?php echo json_encode($active_to_disp); ?>,   ['MM/DD/YYYY','YYYY-MM-DD']);

  // Helper to write both hidden ranges (controller uses the active pair)
  function applyRange(start, end) {
    $('#dr-daterange').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

    var fromIso = start.format('YYYY-MM-DD');
    var toIso   = end.format('YYYY-MM-DD');

    // write BOTH pairs; controller chooses based on filter_type
    $('#sale_date_from').val(fromIso);
    $('#sale_date_to').val(toIso);
    $('#delivery_date_from').val(fromIso);
    $('#delivery_date_to').val(toIso);
    $('#page').val(1);
    $('#dr-form').submit();
  }

  // Build preset ranges (menu on the left, calendars only on Custom Range)
  function presetRanges() {
  var today = moment().startOf('day');

  return {
    'Today':                [ today.clone(), today.clone() ],
    'Next 2 Days':          [ today.clone(), today.clone().add(1, 'days') ],
    'Next 7 Days':          [ today.clone(), today.clone().add(6, 'days') ],
    'Next 30 Days':         [ today.clone(), today.clone().add(30, 'days') ],
    'Yesterday':            [ today.clone().subtract(1,'days'), today.clone().subtract(1,'days') ],
    'Last 7 Days':          [ today.clone().subtract(6,'days'), today.clone() ],
    'Last 30 Days':         [ today.clone().subtract(29,'days'), today.clone() ],
    'Current Month':        [ today.clone().startOf('month'), today.clone().endOf('month') ],
    'Same Month To Same Day Last Year':
                            [ today.clone().subtract(1,'year').startOf('month'),
                              today.clone().subtract(1,'year').date(today.date()) ],
    'Same Month Last Year': [ today.clone().subtract(1,'year').startOf('month'),
                              today.clone().subtract(1,'year').endOf('month') ],
    'Last Month':           [ today.clone().subtract(1,'month').startOf('month'),
                              today.clone().subtract(1,'month').endOf('month') ],
    'Current Year':         [ today.clone().startOf('year'), today.clone().endOf('year') ],
    'Last Year':            [ today.clone().subtract(1,'year').startOf('year'),
                              today.clone().subtract(1,'year').endOf('year') ],
    'Today Last Year':      [ today.clone().subtract(1,'year'), today.clone().subtract(1,'year') ],
    'All Time':             [ moment('2000-01-01','YYYY-MM-DD'),
                              moment('2099-12-31','YYYY-MM-DD') ]
  };
}


  $('#dr-daterange').daterangepicker({
  startDate: start,
  endDate: end,
  opens: 'right',
  linkedCalendars: true,
  autoUpdateInput: false,
  autoApply: false,              // Apply/Clear buttons
  showCustomRangeLabel: true,    // adds the "Custom Range" button
  alwaysShowCalendars: false,    // calendars only when Custom Range is clicked
  showDropdowns: true,           // month/year dropdowns
  ranges: presetRanges(),
  locale: {
    format: 'YYYY-MM-DD',
    applyLabel: 'Apply',
    cancelLabel: 'Clear'
  }
}, applyRange);


  // Initialize input text to the current range (YYYY-MM-DD - YYYY-MM-DD)
  $('#dr-daterange').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

  // When user clicks Apply, submit with both pairs written
  $('#dr-daterange').on('apply.daterangepicker', function(ev, picker) {
    applyRange(picker.startDate, picker.endDate);
  });

  // Optional: if they click Clear, keep current dates in field (or you could reset)
  $('#dr-daterange').on('cancel.daterangepicker', function() {
    // no-op; keeps current range visible
  });
});


	// Bulk: selected complete / uncomplete
	function bulkComplete(done) {
		var ids = [];
		$('.row-check:checked').each(function() {
			ids.push($(this).val());
		});
		if (ids.length === 0) {
			alert('Select at least one row.');
			return;
		}
		$.post(<?php echo json_encode(site_url('sales/delivery_complete_bulk')); ?>,
			addCsrf({ sale_ids: ids, done: done }),
			function(resp) {
				if (resp && resp.ok) {
					location.reload();
				} else {
					alert(resp && resp.error ? resp.error : 'Error');
				}
			},
			'json'
		);
	}

	$('#btn-selected-complete').on('click', function(){ bulkComplete(1); });
	$('#btn-selected-uncomplete').on('click', function(){ bulkComplete(0); });

	// Bulk: all visible complete
	$('#btn-all-complete').on('click', function(){
		var ids = [];
		$('.row-check').each(function(){ ids.push($(this).val()); });
		if (ids.length === 0) { return; }
		$.post(<?php echo json_encode(site_url('sales/delivery_complete_bulk')); ?>,
			addCsrf({ sale_ids: ids, done: 1 }),
			function(resp) {
				if (resp && resp.ok) {
					location.reload();
				} else {
					alert(resp && resp.error ? resp.error : 'Error');
				}
			},
			'json'
		);
	});

	// Check all
	$('#check-all').on('change', function(){
		$('.row-check').prop('checked', $(this).is(':checked'));
	});




// Rows-per-page: update only per_page & page in the current URL, keep everything else (dates, filter, etc.)
$(document).on('change', '#per-page-select', function () {
  var url = new URL(window.location.href);
  url.searchParams.set('per_page', this.value);
  url.searchParams.set('page', '1'); // reset to first page
  // Do NOT touch other params; this preserves filter_type and all date params already in the URL
  window.location.href = url.toString();
});








// Per-row toggle: Mark Complete / Unmark
$(document).on('click', '.btn-toggle-complete', function () {
  var $btn = $(this);
  var $tr  = $btn.closest('tr');
  var id   = parseInt($tr.data('sale-id'), 10);
  var done = parseInt($btn.attr('data-done'), 10) || 0;

  $.post(
    <?php echo json_encode(site_url('sales/delivery_complete')); ?>,
    addCsrf({ sale_id: id, done: done }),
    function (resp) {
      if (resp && resp.ok) {
        // quick UI update without reload
        if (done) {
          $tr.find('.badge-status').removeClass('badge-pending').addClass('badge-complete').text('Complete');
          $btn.removeClass('btn-success').addClass('btn-default').attr('data-done', 0).text('Unmark');
        } else {
          $tr.find('.badge-status').removeClass('badge-complete').addClass('badge-pending').text('Pending');
          $btn.removeClass('btn-default').addClass('btn-success').attr('data-done', 1).text('Mark Complete');
        }
      } else {
        alert((resp && resp.error) ? resp.error : 'Error updating status');
      }
    },
    'json'
  );
});



	// Google Calendar — per-row sync button
	$(document).on('click', '.btn-sync-gcal', function() {
		var $tr  = $(this).closest('tr');
		var id   = parseInt($tr.data('sale-id'), 10);

		$.post(<?php echo json_encode(site_url('sales/delivery_gcal_create_event')); ?>,
			addCsrf({ sale_id: id }),
			function(resp) {
				if (resp && resp.ok) {
					$tr.attr('data-gcal-id', resp.event_id);
					alert('Calendar synced: ' + resp.event_id);
				} else {
					alert(resp && resp.error ? resp.error : 'Calendar sync failed');
				}
			},
			'json'
		);
	});

	// AUTO calendar creation/update on visible rows (gentle: staggered) when GCAL is ON and missing token
	<?php if ($gcal_enabled): ?>
		(function autoSyncVisible() {
			var rows = $('.sale-row').filter(function() {
				return !$(this).attr('data-gcal-id');
			});
			var delay = 400; // ms between calls
			rows.each(function(i, el){
				setTimeout(function(){
					var id = parseInt($(el).data('sale-id'), 10);
					$.post(<?php echo json_encode(site_url('sales/delivery_gcal_create_event')); ?>,
						addCsrf({ sale_id: id }),
						function(resp) {
							// ignore individual errors; keep UI quiet
							if (resp && resp.ok) {
								$(el).attr('data-gcal-id', resp.event_id);
							}
						},
						'json'
					);
				}, i * delay);
			});
		})();
	<?php endif; ?>
})();
</script>
