<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auto_delivery extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Item');
        $this->load->model('Stock_location');
	$this->load->helper('date');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

public function generate_items()
{
    $category_name = 'Local Delivery';

    // Get inputs
    $date_str = $this->input->get('date_delivery');
    $time_slot = $this->input->get('time_delivery');

    if (empty($date_str) || empty($time_slot)) {
        echo "❌ Missing date or time information.";
        return;
    }

    $item_name = "Local Delivery   {$date_str} {$time_slot}";

    // Create category if not exists
    $this->ensure_category_exists($category_name);

    // Create item if it doesn't already exist
    if (!$this->Item->exists_by_name($item_name)) {
        $item_data = [
            'name' => $item_name,
            'description' => 'Auto-generated delivery slot',
            'category' => $category_name,
            'cost_price' => 0.00,
            'unit_price' => 59.99,
            'reorder_level' => 0,
            'receiving_quantity' => 1,
            'allow_alt_description' => 0,
            'is_serialized' => 0,
            'low_sell_item_id' => -1,
            'deleted' => 0
        ];

        $saved = $this->Item->save($item_data);
        if (!$saved) {
            $db_error = $this->db->error();
            echo "❌ Failed to save item: " . $db_error['message'];
            return;
        }
    }

    // Get the item ID after save
    $item_id = $this->Item->get_item_id_by_name($item_name);

$this->db->where('item_id', $item_id);
$this->db->update('items', ['deleted' => 0]);

    // Add item to cart session
    $cart_item = array(
        'item_id' => $item_id,
        'quantity' => 1,
        'discount' => 0,
        'price' => 0.00,
        'description' => '',
        'serialnumber' => ''
    );

    // Load Cart Library and Add
    $this->load->library('sale_lib');
    $price = 0.00;
$discount = 0.00;
$description = '';
$serial_number = '';










// --- GET A VALID STOCK LOCATION ID SAFELY ---
$default_location_id = 1;
$location_check = $this->db
    ->select('location_id')
    ->from($this->db->dbprefix('stock_locations'))
    ->where('location_id', $default_location_id)
    ->where('deleted', 0)
    ->get()
    ->row();

if ($location_check) {
    $stock_location = $location_check->location_id;
} else {
    // fallback to ANY existing non-deleted location
    $fallback = $this->db
        ->select('location_id')
        ->from($this->db->dbprefix('stock_locations'))
        ->where('deleted', 0)
        ->limit(1)
        ->get()
        ->row();

    if ($fallback) {
        $stock_location = $fallback->location_id;
    } else {
        echo "❌ No valid stock location exists.";
        return;
    }
}

// NOW safe to continue
$this->sale_lib->set_sale_location($stock_location);




















    // Redirect back to register

	redirect(site_url("sales?item={$item_id}")); 
}



    private function ensure_category_exists($category)
    {
        $this->db->from('items');
        $this->db->where('category', $category);
        $exists = $this->db->count_all_results() > 0;

        if (!$exists) {
            $dummy_item = [
                'name' => '__TEMP__',
                'description' => 'temp',
                'category' => $category,
                'cost_price' => 0.00,
                'unit_price' => 0.00,
                'quantity' => 1,
                'reorder_level' => 0,
                'receiving_quantity' => 1,
                'disable_loyalty' => 1,
                'deleted' => 0
            ];
            $this->Item->save($dummy_item);
            $this->db->where('name', '__TEMP__');
            $this->db->delete('items');
        }
    }
}
