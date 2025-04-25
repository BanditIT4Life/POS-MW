<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auto_pickup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Item');
        $this->load->model('Stock_location');
        $this->load->helper('date');
        $this->load->library('sale_lib');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function generate_items()
    {
        $category_name = 'Pickup';

        $date_str = $this->input->get('date_delivery');
        $time_slot = $this->input->get('time_delivery');

        if (empty($date_str) || empty($time_slot)) {
            echo "❌ Missing required parameters.";
            return;
        }

        $item_name = "In Store Pickup - {$date_str} {$time_slot}";

        // Ensure category exists
        $this->db->from('items');
        $this->db->where('category', $category_name);
        $exists = $this->db->count_all_results() > 0;

        if (!$exists) {
            $dummy_item = [
                'name' => '__TEMP__',
                'description' => 'temp',
                'category' => $category_name,
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

        // Add item if not exists
        if (!$this->Item->exists_by_name($item_name)) {
            $item_data = [
                'name' => $item_name,
                'description' => 'Auto-generated pickup slot',
                'category' => $category_name,
                'cost_price' => 0.00,
                'unit_price' => 0.00,
                'reorder_level' => 0,
                'receiving_quantity' => 1,
                'allow_alt_description' => 0,
                'is_serialized' => 0,
                'low_sell_item_id' => -1,
                'deleted' => 0
            ];
            $this->Item->save($item_data);
        }

        $item_id = $this->Item->get_item_id_by_name($item_name);

        $price = 0.00;
        $discount = 0.00;
        $description = '';
        $serial_number = '';

        // ✅ Get a valid stock location
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


// Redirect back to register

	redirect(site_url("sales?item={$item_id}"));
    }
}
?>
