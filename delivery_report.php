<?php $this->load->view("partial/header"); ?>

<div style="text-align: center;">
    <ul class="nav nav-tabs module-tabs" style="display: inline-block; padding: 0;">
        <li style="display: inline-block; text-align: center; margin: 0 10px;">
            <a href="<?php echo site_url('home'); ?>" style="display: block;">
                <img src="<?php echo base_url('images/menubar/home.png'); ?>" alt="Home" class="module-icon" />
                <div>Home</div>
            </a>
        </li>
        <li style="display: inline-block; text-align: center; margin: 0 10px;">
            <a href="<?php echo site_url('sales'); ?>" style="display: block;">
                <img src="<?php echo base_url('images/menubar/sales.png'); ?>" alt="Sales" class="module-icon" />
                <div><?php echo $this->lang->line('module_sales'); ?></div>
            </a>
        </li>
    </ul>
</div>

<div id="page_title" style="text-align: left; margin-bottom: 20px;">
    <h3 style="margin: 0;">Delivery Report</h3>
</div>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-3">
        <label>Sale Date Range:</label>
        <?php echo form_input(array('name'=>'sale_daterangepicker', 'id'=>'sale_daterangepicker', 'class'=>'form-control input-sm', 'autocomplete'=>'off')); ?>
    </div>

    <div class="col-md-3">
        <label>Delivery Date Range:</label>
        <?php echo form_input(array('name'=>'delivery_daterangepicker', 'id'=>'delivery_daterangepicker', 'class'=>'form-control input-sm', 'autocomplete'=>'off')); ?>
    </div>

    <div class="col-md-3">
        <label>Local Delivery Only Date Range:</label>
        <?php echo form_input(array('name'=>'local_delivery_daterangepicker', 'id'=>'local_delivery_daterangepicker', 'class'=>'form-control input-sm', 'autocomplete'=>'off')); ?>
    </div>
</div>



<div id="table_holder"></div>

<div class="row" style="margin-top: 20px;">
    <div class="col-md-6">
        <label>Results Per Page:</label>
        <select id="results_per_page" class="form-control input-sm" style="width: auto; display: inline;">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
    <div class="col-md-6 text-right">
        <button id="prev_page" class="btn btn-default btn-sm">Previous</button>
        <span id="page_info" style="margin: 0 10px;">Page 1</span>
        <button id="next_page" class="btn btn-default btn-sm">Next</button>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function()
{
    <?php $this->load->view('partial/daterangepicker'); ?>

    var sale_start_date = '';
    var sale_end_date = '';
    var delivery_start_date = '';
    var delivery_end_date = '';
    var local_delivery_start_date = '';
    var local_delivery_end_date = '';
    var current_page = 1;
    var results_per_page = 25;
    var total_records = 0;

    function disable_others(active)
    {
        if (active === 'sale')
        {
            $("#delivery_daterangepicker").prop('disabled', true);
            $("#local_delivery_daterangepicker").prop('disabled', true);
        }
        else if (active === 'delivery')
        {
            $("#sale_daterangepicker").prop('disabled', true);
            $("#local_delivery_daterangepicker").prop('disabled', true);
        }
        else if (active === 'local_delivery')
        {
            $("#sale_daterangepicker").prop('disabled', true);
            $("#delivery_daterangepicker").prop('disabled', true);
        }
    }

    function enable_all()
    {
        $("#sale_daterangepicker").prop('disabled', false);
        $("#delivery_daterangepicker").prop('disabled', false);
        $("#local_delivery_daterangepicker").prop('disabled', false);
    }

$("#sale_daterangepicker").daterangepicker({
    locale: {
        format: 'YYYY-MM-DD',
        cancelLabel: 'Clear'
    },
    autoUpdateInput: false,
    ranges: {
        'Today': [moment(), moment()],
        'Today Last Year': [moment().subtract(1, 'years'), moment().subtract(1, 'years')],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'Current Month': [moment().startOf('month'), moment()],
        'Same Month To Same Day Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years')],
        'Same Month Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years').endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        'Current Year': [moment().startOf('year'), moment()],
        'Last Year': [moment().subtract(1, 'years').startOf('year'), moment().subtract(1, 'years').endOf('year')],
        'Current Fiscal Year': [moment().startOf('year'), moment()],
        'All Time': [moment('1970-01-01'), moment()]
    }
});


    $("#sale_daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        sale_start_date = picker.startDate.format('YYYY-MM-DD');
        sale_end_date = picker.endDate.format('YYYY-MM-DD');
        $(this).val(sale_start_date + ' - ' + sale_end_date);
        disable_others('sale');
        current_page = 1;
        loadTable();
    }).on('cancel.daterangepicker', function(ev, picker) {
        sale_start_date = '';
        sale_end_date = '';
        $(this).val('');
        enable_all();
        loadTable();
    });

    $("#delivery_daterangepicker").daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        },
        autoUpdateInput: false,
        ranges: {
        'Today': [moment(), moment()],
        'Today Last Year': [moment().subtract(1, 'years'), moment().subtract(1, 'years')],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'Current Month': [moment().startOf('month'), moment()],
        'Same Month To Same Day Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years')],
        'Same Month Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years').endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        'Current Year': [moment().startOf('year'), moment()],
        'Last Year': [moment().subtract(1, 'years').startOf('year'), moment().subtract(1, 'years').endOf('year')],
        'Current Fiscal Year': [moment().startOf('year'), moment()],
        'All Time': [moment('1970-01-01'), moment()]
        }
    });

    $("#delivery_daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        delivery_start_date = picker.startDate.format('YYYY-MM-DD');
        delivery_end_date = picker.endDate.format('YYYY-MM-DD');
        $(this).val(delivery_start_date + ' - ' + delivery_end_date);
        disable_others('delivery');
        current_page = 1;
        loadTable();
    }).on('cancel.daterangepicker', function(ev, picker) {
        delivery_start_date = '';
        delivery_end_date = '';
        $(this).val('');
        enable_all();
        loadTable();
    });

    $("#local_delivery_daterangepicker").daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        },
        autoUpdateInput: false,
        ranges: {
        'Today': [moment(), moment()],
        'Today Last Year': [moment().subtract(1, 'years'), moment().subtract(1, 'years')],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'Current Month': [moment().startOf('month'), moment()],
        'Same Month To Same Day Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years')],
        'Same Month Last Year': [moment().subtract(1, 'years').startOf('month'), moment().subtract(1, 'years').endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        'Current Year': [moment().startOf('year'), moment()],
        'Last Year': [moment().subtract(1, 'years').startOf('year'), moment().subtract(1, 'years').endOf('year')],
        'Current Fiscal Year': [moment().startOf('year'), moment()],
        'All Time': [moment('1970-01-01'), moment()]
        }
    });

    $("#local_delivery_daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        local_delivery_start_date = picker.startDate.format('YYYY-MM-DD');
        local_delivery_end_date = picker.endDate.format('YYYY-MM-DD');
        $(this).val(local_delivery_start_date + ' - ' + local_delivery_end_date);
        disable_others('local_delivery');
        current_page = 1;
        loadTable();
    }).on('cancel.daterangepicker', function(ev, picker) {
        local_delivery_start_date = '';
        local_delivery_end_date = '';
        $(this).val('');
        enable_all();
        loadTable();
    });

    function loadTable()
{
    var offset = (current_page - 1) * results_per_page;

    $.get('<?php echo site_url('delivery_report/get_data'); ?>', {
        from_date: sale_start_date,
        to_date: sale_end_date,
        delivery_from_date: delivery_start_date,
        delivery_to_date: delivery_end_date,
        local_delivery_from_date: local_delivery_start_date,
        local_delivery_to_date: local_delivery_end_date,
        limit: results_per_page,
        offset: offset
    }, function(data)
    {
        var parsedData = JSON.parse(data);
        var deliveries = parsedData.sales;
        total_records = parsedData.total;

        var html = '<table class="table table-striped table-bordered">';
        html += '<thead><tr><th>Sale ID</th><th>Item Name</th><th>Customer Name</th><th>Phone Number</th><th>Address 1</th><th>City</th><th>State</th><th>Postal Code</th><th>Date</th><th>Receipt</th></tr></thead><tbody>';

        deliveries.forEach(function(delivery) {
            html += '<tr>';
            html += '<td>' + delivery.sale_id + '</td>';
            html += '<td>' + (delivery.item_name || '') + '</td>';
            html += '<td>' + (delivery.customer_name || '') + '</td>';
            html += '<td>' + (delivery.phone_number || '') + '</td>';
            html += '<td>' + (delivery.address_1 || '') + '</td>';
            html += '<td>' + (delivery.city || '') + '</td>';
            html += '<td>' + (delivery.state || '') + '</td>';
            html += '<td>' + (delivery.postal_code || '') + '</td>';
            html += '<td>' + delivery.sale_time + '</td>';
            html += '<td><a href="<?php echo site_url('sales/receipt'); ?>/' + delivery.sale_id + '" target="_blank" title="View Receipt"><span class="glyphicon glyphicon-usd"></span></a></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#table_holder').html(html);

        // Update page info
        var total_pages = Math.ceil(total_records / results_per_page);
        $("#page_info").text("Page " + current_page + " of " + total_pages);

        // Disable/Enable Prev/Next
        $("#prev_page").prop('disabled', current_page === 1);
        $("#next_page").prop('disabled', current_page >= total_pages);
    });
}


    // ðŸ“… Set Default: Delivery Date Range = Today
    var today = moment().format('YYYY-MM-DD');
    $("#delivery_daterangepicker").data('daterangepicker').setStartDate(today);
    $("#delivery_daterangepicker").data('daterangepicker').setEndDate(today);
    $('#delivery_daterangepicker').val(today + ' - ' + today);
    delivery_start_date = today;
    delivery_end_date = today;
    $("#sale_daterangepicker").prop('disabled', true);
    $("#local_delivery_daterangepicker").prop('disabled', true);

    loadTable();
    
    $("#results_per_page").on('change', function() {
    results_per_page = parseInt($(this).val());
    current_page = 1; // Reset to first page
    loadTable();
});

$("#prev_page").click(function() {
    if (current_page > 1) {
        current_page--;
        loadTable();
    }
});

$("#next_page").click(function() {
    var total_pages = Math.ceil(total_records / results_per_page);
    if (current_page < total_pages) {
        current_page++;
        loadTable();
    }
});

});

</script>


<?php $this->load->view("partial/footer"); ?>
