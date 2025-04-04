<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Item_kit_items model
 */
class Item_kit_items extends CI_Model
{
    /**
     * Get all items for a given item kit ID or kit number
     */
    public function get_info($item_kit_id)
    {
        $this->db->select('item_kits.item_kit_id, item_kit_items.item_id, quantity, kit_sequence, unit_price, item_type, stock_type');
        $this->db->from('item_kit_items as item_kit_items');
        $this->db->join('items as items', 'item_kit_items.item_id = items.item_id');
        $this->db->join('item_kits as item_kits', 'item_kits.item_kit_id = item_kit_items.item_kit_id');
        $this->db->where('item_kits.item_kit_id', $item_kit_id);
        $this->db->or_where('item_kit_number', $item_kit_id);
        $this->db->order_by('kit_sequence', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Get items for sale context
     */
    public function get_info_for_sale($item_kit_id)
    {
        $this->db->from('item_kit_items');
        $this->db->where('item_kit_id', $item_kit_id);
        $this->db->order_by('kit_sequence', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Save all items in a kit (replaces existing ones)
     */
    public function save(&$item_kit_items_data, $item_kit_id)
    {
        $success = TRUE;

        if (!is_numeric($item_kit_id)) {
            log_message('error', '❌ Invalid item_kit_id passed to Item_kit_items::save');
            return FALSE;
        }

        if (!is_array($item_kit_items_data)) {
            log_message('error', '❌ Invalid item_kit_items_data passed to Item_kit_items::save — expected array');
            return FALSE;
        }

        $this->db->trans_start();

        $this->delete($item_kit_id);

        if (!empty($item_kit_items_data)) {
            foreach ($item_kit_items_data as $row) {
                if (!isset($row['item_id'])) continue;

                $row['item_kit_id'] = $item_kit_id;

                // Ensure defaults for quantity and sequence
                if (!isset($row['quantity'])) $row['quantity'] = 1;
                if (!isset($row['kit_sequence'])) $row['kit_sequence'] = 0;

                $success &= $this->db->insert('item_kit_items', $row);
            }
        }

        $this->db->trans_complete();

        $success &= $this->db->trans_status();

        return $success;
    }

    /**
     * Delete all items for a specific kit
     */
    public function delete($item_kit_id)
    {
        if (!is_numeric($item_kit_id)) {
            log_message('error', '❌ Invalid item_kit_id passed to Item_kit_items::delete');
            return FALSE;
        }

        return $this->db->delete('item_kit_items', array('item_kit_id' => $item_kit_id));
    }
}