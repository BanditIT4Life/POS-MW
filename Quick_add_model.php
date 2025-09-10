<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Universal Quick Add (UQA)
 * Model: application/models/Quick_add_model.php
 *
 * Tables (suggested names; respect dbprefix):
 *  - uqa_flows        : id, name, sort_order, is_enabled, created_at, updated_at
 *  - uqa_steps        : id, flow_id, label, sort_order, depends_on(JSON array of step_ids), join_mode('ALL'|'ANY')
 *  - uqa_options      : id, step_id, label, sort_order
 *  - uqa_scopes       : id, child_step_id, child_option_id, parent_step_id, parent_option_id
 *  - uqa_mappings     : id, flow_id, key_json(JSON array of {step_id, option_id|"*"}), item_id
 *
 * NOTE: This model assumes the tables above exist. We’ll provide migrations if you need them.
 */
class Quick_add_model extends CI_Model
{
    private $t_flows;
    private $t_steps;
    private $t_options;
    private $t_scopes;
    private $t_mappings;
    private $t_items;

    public function __construct()
    {
        parent::__construct();
        $this->t_flows    = $this->db->dbprefix('uqa_flows');
        $this->t_steps    = $this->db->dbprefix('uqa_steps');
        $this->t_options  = $this->db->dbprefix('uqa_options');
        $this->t_scopes   = $this->db->dbprefix('uqa_scopes');
        $this->t_mappings = $this->db->dbprefix('uqa_mappings');
        $this->t_items    = $this->db->dbprefix('items'); // OSPOS items table
    }

    /* ========================== Helpers ========================== */

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    private function json_decode_arr($json, $fallback = array())
    {
        $arr = @json_decode((string)$json, true);
        return is_array($arr) ? $arr : $fallback;
    }

    private function normalize_depends_on($flow_id, $depends_on_ids)
    {
        // Keep only unique positive ints that are steps in this flow
        $ids = array_values(array_unique(array_map('intval', (array)$depends_on_ids)));
        if (empty($ids)) return array();

        // Validate steps belong to same flow
        $rows = $this->db->select('id, sort_order')
            ->from($this->t_steps)
            ->where('flow_id', (int)$flow_id)
            ->where_in('id', $ids)
            ->get()->result_array();

        $valid = array();
        foreach ($rows as $r) $valid[] = (int)$r['id'];
        return $valid;
    }

    private function get_flow($id)
    {
        return $this->db->get_where($this->t_flows, array('id' => (int)$id))->row_array();
    }

    private function get_step($id)
    {
        $row = $this->db->get_where($this->t_steps, array('id' => (int)$id))->row_array();
        if ($row) {
            $row['depends_on'] = $this->json_decode_arr($row['depends_on']);
        }
        return $row;
    }

    private function steps_by_flow($flow_id)
    {
        $rows = $this->db->order_by('sort_order asc, id asc')
            ->get_where($this->t_steps, array('flow_id' => (int)$flow_id))
            ->result_array();
        foreach ($rows as &$r) {
            $r['depends_on'] = $this->json_decode_arr($r['depends_on']);
        }
        return $rows;
    }

    private function options_by_step($step_id)
    {
        return $this->db->order_by('sort_order asc, id asc')
            ->get_where($this->t_options, array('step_id' => (int)$step_id))
            ->result_array();
    }

    private function next_sort($table, $where = array())
    {
        $this->db->select_max('sort_order')->from($table);
        if (!empty($where)) $this->db->where($where);
        $row = $this->db->get()->row_array();
        $max = isset($row['sort_order']) ? (int)$row['sort_order'] : 0;
        return $max + 1;
    }

    private function array_assoc_from_tuple($tuple_rows)
    {
        // tuple_rows: [ [step_id, option_id], ... ]
        $out = array();
        foreach ((array)$tuple_rows as $r) {
            $sid = (int)($r['step_id'] ?? 0);
            $oid = $r['option_id'] ?? null;
            if ($sid > 0 && ($oid === '*' || (int)$oid > 0)) $out[$sid] = $oid;
        }
        return $out;
    }

    private function canonicalize_key($flow_id, $key_assoc)
    {
        // Produce a stable, ordered array of {step_id, option_id} following step order in the flow
        $steps = $this->steps_by_flow($flow_id);
        $ordered = array();
        foreach ($steps as $s) {
            $sid = (int)$s['id'];
            if (array_key_exists($sid, $key_assoc)) {
                $oid = $key_assoc[$sid];
                $ordered[] = array('step_id' => $sid, 'option_id' => $oid);
            }
        }
        return $ordered;
    }

    private function keys_overlap($a_assoc, $b_assoc)
    {
        // Two mapping keys overlap if for every step in their union:
        // - values are equal, or
        // - one of them is '*'
        $union_steps = array_unique(array_merge(array_keys($a_assoc), array_keys($b_assoc)));
        foreach ($union_steps as $sid) {
            $a = array_key_exists($sid, $a_assoc) ? $a_assoc[$sid] : '*';
            $b = array_key_exists($sid, $b_assoc) ? $b_assoc[$sid] : '*';
            if ($a !== '*' && $b !== '*' && (int)$a !== (int)$b) {
                return false; // mismatch -> no overlap
            }
        }
        return true; // every compared position compatible
    }

    private function specificity_score($key_assoc)
    {
        // Higher = more specific (non-wildcards)
        $score = 0;
        foreach ($key_assoc as $v) if ($v !== '*') $score++;
        return $score;
    }

    /* ========================== FLOWS ========================== */

    public function flows_list($enabled_only = false)
    {
        if ($enabled_only) $this->db->where('is_enabled', 1);
        return $this->db->order_by('sort_order asc, id asc')
            ->get($this->t_flows)->result_array();
    }

    public function flow_create($name, $sort_order = null, $is_enabled = false)
    {
        $ins = array(
            'name'       => $name,
            'sort_order' => is_null($sort_order) ? $this->next_sort($this->t_flows) : (int)$sort_order,
            'is_enabled' => $is_enabled ? 1 : 0,
            'created_at' => $this->now(),
            'updated_at' => $this->now()
        );
        $this->db->insert($this->t_flows, $ins);
        $id = (int)$this->db->insert_id();
        return $this->get_flow($id);
    }

    public function flow_update($id, $attrs)
    {
        if (isset($attrs['name']))        $upd['name']        = (string)$attrs['name'];
        if (isset($attrs['sort_order']))  $upd['sort_order']  = (int)$attrs['sort_order'];
        if (isset($attrs['is_enabled']))  $upd['is_enabled']  = $attrs['is_enabled'] ? 1 : 0;
        if (!empty($upd)) {
            $upd['updated_at'] = $this->now();
            $this->db->update($this->t_flows, $upd, array('id' => (int)$id));
        }
        return $this->get_flow($id);
    }

    public function flow_delete($id)
    {
        $id = (int)$id;
        $this->db->trans_start();

        // Steps
        $steps = $this->db->get_where($this->t_steps, array('flow_id' => $id))->result_array();
        $step_ids = array_map(function($r){ return (int)$r['id']; }, $steps);

        if (!empty($step_ids)) {
            // Options
            $opts = $this->db->where_in('step_id', $step_ids)->get($this->t_options)->result_array();
            $opt_ids = array_map(function($r){ return (int)$r['id']; }, $opts);

            if (!empty($opt_ids)) {
                // Scopes touching these options either as child or parent
                $this->db->where_in('child_option_id', $opt_ids)->delete($this->t_scopes);
                $this->db->where_in('parent_option_id', $opt_ids)->delete($this->t_scopes);
            }
            // Scopes by step relation too (defensive)
            $this->db->where_in('child_step_id', $step_ids)->delete($this->t_scopes);
            $this->db->where_in('parent_step_id', $step_ids)->delete($this->t_scopes);

            // Options
            if (!empty($step_ids)) $this->db->where_in('step_id', $step_ids)->delete($this->t_options);

            // Steps
            $this->db->where_in('id', $step_ids)->delete($this->t_steps);
        }

        // Mappings for this flow
        $this->db->delete($this->t_mappings, array('flow_id' => $id));

        // Flow
        $this->db->delete($this->t_flows, array('id' => $id));

        $this->db->trans_complete();
    }

    /* ========================== STEPS (dropdown-only) ========================== */

    public function steps_list($flow_id)
    {
        return $this->steps_by_flow((int)$flow_id);
    }

    public function step_create($flow_id, $label, $sort_order = null, $depends_on = array(), $join_mode = 'ALL')
    {
        $flow_id = (int)$flow_id;
        $depends = $this->normalize_depends_on($flow_id, $depends_on);
        $ins = array(
            'flow_id'    => $flow_id,
            'label'      => $label,
            'sort_order' => is_null($sort_order) ? $this->next_sort($this->t_steps, array('flow_id' => $flow_id)) : (int)$sort_order,
            'depends_on' => json_encode($depends),
            'join_mode'  => ($join_mode === 'ANY') ? 'ANY' : 'ALL'
        );
        $this->db->insert($this->t_steps, $ins);
        $id = (int)$this->db->insert_id();
        return $this->get_step($id);
    }

    public function step_update($id, $attrs)
    {
        $step = $this->get_step($id);
        if (!$step) return null;

        $upd = array();
        if (isset($attrs['label']))      $upd['label'] = (string)$attrs['label'];
        if (isset($attrs['sort_order'])) $upd['sort_order'] = (int)$attrs['sort_order'];
        if (isset($attrs['join_mode']))  $upd['join_mode'] = ($attrs['join_mode'] === 'ANY') ? 'ANY' : 'ALL';
        if (isset($attrs['depends_on'])) {
            $upd['depends_on'] = json_encode($this->normalize_depends_on($step['flow_id'], $attrs['depends_on']));
        }
        if (!empty($upd)) {
            $this->db->update($this->t_steps, $upd, array('id' => (int)$id));
        }
        return $this->get_step($id);
    }

    public function steps_reorder($flow_id, $ordered_ids)
    {
        $flow_id = (int)$flow_id;
        $ordered_ids = array_values(array_map('intval', (array)$ordered_ids));

        // Validate: all ids belong to flow
        $existing = $this->db->select('id')->from($this->t_steps)
            ->where('flow_id', $flow_id)->get()->result_array();
        $existing_ids = array_map(function($r){ return (int)$r['id']; }, $existing);
        sort($existing_ids);
        $sorted_input = $ordered_ids; sort($sorted_input);
        if ($existing_ids !== $sorted_input) {
            // Allow partial reorder too: apply to provided ids only
        }

        // Check dependency direction: parent must appear before child in the new order
        $order_index = array();
        foreach ($ordered_ids as $pos => $sid) $order_index[(int)$sid] = $pos;

        $steps = $this->steps_by_flow($flow_id);
        foreach ($steps as $s) {
            $sid = (int)$s['id'];
            if (!isset($order_index[$sid])) continue; // not reordering this one
            foreach ((array)$s['depends_on'] as $pid) {
                $pid = (int)$pid;
                if (isset($order_index[$pid]) && $order_index[$pid] > $order_index[$sid]) {
                    // Parent would come after child -> invalid
                    throw new Exception('Invalid reorder: parent must precede dependent step');
                }
            }
        }

        $this->db->trans_start();
        $pos = 1;
        foreach ($ordered_ids as $sid) {
            $this->db->update($this->t_steps, array('sort_order' => $pos++), array('id' => (int)$sid, 'flow_id' => $flow_id));
        }
        $this->db->trans_complete();
    }

    public function step_delete($id)
    {
        $id = (int)$id;
        $step = $this->get_step($id);
        if (!$step) return;

        $this->db->trans_start();

        // Delete options for this step
        $opts = $this->db->get_where($this->t_options, array('step_id' => $id))->result_array();
        $opt_ids = array_map(function($r){ return (int)$r['id']; }, $opts);

        if (!empty($opt_ids)) {
            // Remove scopes where this option appears as child or parent
            $this->db->where_in('child_option_id', $opt_ids)->delete($this->t_scopes);
            $this->db->where_in('parent_option_id', $opt_ids)->delete($this->t_scopes);
        }

        // Remove scopes by step relation
        $this->db->delete($this->t_scopes, array('child_step_id' => $id));
        $this->db->delete($this->t_scopes, array('parent_step_id' => $id));

        // Options
        $this->db->delete($this->t_options, array('step_id' => $id));

        // Delete mappings that reference this step in their key
        $like = '"step_id":'.$id;
        $this->db->like('key_json', $like)->delete($this->t_mappings);

        // Delete the step
        $this->db->delete($this->t_steps, array('id' => $id));

        $this->db->trans_complete();
    }

    /* ========================== OPTIONS ========================== */

    /**
     * options_list
     * If selections include the required parent picks, returns filtered child options;
     * otherwise returns [] for dependent steps, or global options for non-dependent steps.
     */
    public function options_list($step_id, $flow_id = null, $selections = array())
    {
        $step = $this->get_step((int)$step_id);
        if (!$step) return array();

        $deps = (array)$step['depends_on'];
        if (empty($deps)) {
            return $this->options_by_step($step['id']);
        }

        // dependent step:
        // if parents not selected yet, no options
        $chosen = array();
        foreach ($deps as $pid) {
            $pid = (int)$pid;
            if (isset($selections[$pid]) && (int)$selections[$pid] > 0) {
                $chosen[$pid] = (int)$selections[$pid];
            }
        }
        if (empty($chosen)) return array();

        $allowed_child_option_ids = $this->compute_allowed_child_options($step, $chosen);
        if (empty($allowed_child_option_ids)) return array();

        if (!empty($allowed_child_option_ids)) {
            $this->db->where_in('id', $allowed_child_option_ids);
        } else {
            $this->db->where('id', 0); // none
        }
        return $this->db->order_by('sort_order asc, id asc')
            ->get_where($this->t_options, array('step_id' => (int)$step['id']))
            ->result_array();
    }

    public function option_create($step_id, $label, $sort_order = null)
    {
        $step_id = (int)$step_id;
        $ins = array(
            'step_id'    => $step_id,
            'label'      => $label,
            'sort_order' => is_null($sort_order) ? $this->next_sort($this->t_options, array('step_id' => $step_id)) : (int)$sort_order
        );
        $this->db->insert($this->t_options, $ins);
        $id = (int)$this->db->insert_id();
        return $this->db->get_where($this->t_options, array('id' => $id))->row_array();
    }

    public function option_update($id, $attrs)
    {
        $upd = array();
        if (isset($attrs['label']))      $upd['label'] = (string)$attrs['label'];
        if (isset($attrs['sort_order'])) $upd['sort_order'] = (int)$attrs['sort_order'];
        if (!empty($upd)) $this->db->update($this->t_options, $upd, array('id' => (int)$id));
        return $this->db->get_where($this->t_options, array('id' => (int)$id))->row_array();
    }

    public function options_reorder($step_id, $ordered_ids)
    {
        $step_id = (int)$step_id;
        $ordered_ids = array_values(array_map('intval', (array)$ordered_ids));
        $this->db->trans_start();
        $pos = 1;
        foreach ($ordered_ids as $oid) {
            $this->db->update($this->t_options, array('sort_order' => $pos++), array('id' => (int)$oid, 'step_id' => $step_id));
        }
        $this->db->trans_complete();
    }

    public function option_delete($id)
    {
        $id = (int)$id;
        $this->db->trans_start();

        // Remove scopes touching this option (as child or parent)
        $this->db->delete($this->t_scopes, array('child_option_id' => $id));
        $this->db->delete($this->t_scopes, array('parent_option_id' => $id));

        // Remove mappings referencing this option
        $like = '"option_id":'.$id;
        $this->db->like('key_json', $like)->delete($this->t_mappings);

        // Delete the option
        $this->db->delete($this->t_options, array('id' => $id));

        $this->db->trans_complete();
    }

    /* ============== Scoped options (dependencies) ============== */

    public function scope_attach($child_step_id, $child_option_id, $parents)
    {
        // parents: [ {step_id, option_id}, ... ]
        $child = $this->get_step($child_step_id);
        if (!$child) return;

        $parent_ids = array_map(function($p){ return (int)$p['step_id']; }, $parents);
        // Ensure provided parents are defined as dependencies of the child
        foreach ($parent_ids as $pid) {
            if (!in_array($pid, (array)$child['depends_on'])) {
                throw new Exception('Parent step not in child depends_on');
            }
        }

        $this->db->trans_start();
        foreach ($parents as $p) {
            $ps = (int)$p['step_id'];
            $po = (int)$p['option_id'];
            if ($ps <= 0 || $po <= 0) continue;

            // Upsert-like behavior: avoid duplicates
            $exists = $this->db->get_where($this->t_scopes, array(
                'child_step_id'  => $child_step_id,
                'child_option_id'=> $child_option_id,
                'parent_step_id' => $ps,
                'parent_option_id'=> $po
            ))->row_array();

            if (!$exists) {
                $this->db->insert($this->t_scopes, array(
                    'child_step_id'   => $child_step_id,
                    'child_option_id' => $child_option_id,
                    'parent_step_id'  => $ps,
                    'parent_option_id'=> $po
                ));
            }
        }
        $this->db->trans_complete();
    }

    public function scope_detach($child_step_id, $child_option_id, $parents)
    {
        $this->db->trans_start();
        foreach ($parents as $p) {
            $ps = (int)$p['step_id'];
            $po = (int)$p['option_id'];
            if ($ps <= 0 || $po <= 0) continue;

            $this->db->delete($this->t_scopes, array(
                'child_step_id'    => (int)$child_step_id,
                'child_option_id'  => (int)$child_option_id,
                'parent_step_id'   => $ps,
                'parent_option_id' => $po
            ));
        }
        $this->db->trans_complete();
    }

    private function compute_allowed_child_options($child_step, $chosen_parents_assoc)
    {
        // chosen_parents_assoc: [ parent_step_id => parent_option_id ]
        // join_mode: ALL -> intersection across parents; ANY -> union
        $child_step_id = (int)$child_step['id'];
        $parent_step_ids = (array)$child_step['depends_on'];
        $join_mode = ($child_step['join_mode'] === 'ANY') ? 'ANY' : 'ALL';

        // If none of the declared parents are chosen, no options
        $chosen_pairs = array();
        foreach ($parent_step_ids as $psid) {
            if (isset($chosen_parents_assoc[$psid]) && (int)$chosen_parents_assoc[$psid] > 0) {
                $chosen_pairs[(int)$psid] = (int)$chosen_parents_assoc[$psid];
            }
        }
        if (empty($chosen_pairs)) return array();

        // For each chosen parent, get set of child_option_ids attached to (parent_step_id, parent_option_id)
        $sets = array();
        foreach ($chosen_pairs as $ps => $po) {
            $rows = $this->db->select('child_option_id')
                ->from($this->t_scopes)
                ->where('child_step_id', $child_step_id)
                ->where('parent_step_id', (int)$ps)
                ->where('parent_option_id', (int)$po)
                ->get()->result_array();
            $ids = array_map(function($r){ return (int)$r['child_option_id']; }, $rows);
            $sets[] = array_unique($ids);
        }

        if (empty($sets)) return array();

        // Intersect (ALL) or union (ANY)
        if ($join_mode === 'ALL') {
            $allowed = array_shift($sets);
            foreach ($sets as $s) {
                $allowed = array_values(array_intersect($allowed, $s));
            }
            return $allowed;
        } else { // ANY
            $allowed = array();
            foreach ($sets as $s) $allowed = array_merge($allowed, $s);
            return array_values(array_unique($allowed));
        }
    }

    /* ========================== MAPPINGS ========================== */

    public function mappings_list($flow_id)
    {
        $rows = $this->db->order_by('id asc')
            ->get_where($this->t_mappings, array('flow_id' => (int)$flow_id))
            ->result_array();

        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id'      => (int)$r['id'],
                'flow_id' => (int)$r['flow_id'],
                'key'     => $this->json_decode_arr($r['key_json']),
                'item_id' => (int)$r['item_id'],
            );
        }
        return $out;
    }

    public function mapping_create($flow_id, $key_tuple, $item_id)
    {
        $flow_id = (int)$flow_id;
        $item_id = (int)$item_id;

        // Validate steps belong to flow, canonicalize order
        $assoc = $this->array_assoc_from_tuple($key_tuple);
        $ordered = $this->canonicalize_key($flow_id, $assoc);
        if (empty($ordered)) throw new Exception('Invalid key');

        // Conflict detection: disallow overlaps (ambiguity)
        $this->assert_no_overlap($flow_id, $assoc, null);

        $ins = array(
            'flow_id'  => $flow_id,
            'key_json' => json_encode($ordered),
            'item_id'  => $item_id
        );
        $this->db->insert($this->t_mappings, $ins);
        $id = (int)$this->db->insert_id();
        return array('id' => $id, 'flow_id' => $flow_id, 'key' => $ordered, 'item_id' => $item_id);
    }

    public function mapping_update($id, $attrs)
    {
        $row = $this->db->get_where($this->t_mappings, array('id' => (int)$id))->row_array();
        if (!$row) return null;

        $flow_id = (int)$row['flow_id'];

        $upd = array();
        if (isset($attrs['item_id'])) $upd['item_id'] = (int)$attrs['item_id'];

        if (isset($attrs['key'])) {
            $assoc = $this->array_assoc_from_tuple($attrs['key']);
            $ordered = $this->canonicalize_key($flow_id, $assoc);
            if (empty($ordered)) throw new Exception('Invalid key');

            // Conflict detection (ignore self)
            $this->assert_no_overlap($flow_id, $assoc, (int)$id);

            $upd['key_json'] = json_encode($ordered);
        }

        if (!empty($upd)) {
            $this->db->update($this->t_mappings, $upd, array('id' => (int)$id));
        }

        $fresh = $this->db->get_where($this->t_mappings, array('id' => (int)$id))->row_array();
        return array(
            'id'      => (int)$fresh['id'],
            'flow_id' => (int)$fresh['flow_id'],
            'key'     => $this->json_decode_arr($fresh['key_json']),
            'item_id' => (int)$fresh['item_id'],
        );
    }

    public function mapping_delete($id)
    {
        $this->db->delete($this->t_mappings, array('id' => (int)$id));
    }

    private function assert_no_overlap($flow_id, $new_key_assoc, $ignore_mapping_id = null)
    {
        $rows = $this->db->get_where($this->t_mappings, array('flow_id' => (int)$flow_id))->result_array();
        foreach ($rows as $r) {
            if ($ignore_mapping_id !== null && (int)$r['id'] === (int)$ignore_mapping_id) continue;

            $existing = $this->array_assoc_from_tuple($this->json_decode_arr($r['key_json']));
            if ($this->keys_overlap($existing, $new_key_assoc)) {
                // If exact duplicate to same item, allow? Keep strict to avoid ambiguity.
                throw new Exception('Mapping overlaps with existing mapping #'.$r['id'].'; please refine or remove the conflict');
            }
        }
    }

    /* ========================== RUNTIME ========================== */

    /**
     * Returns each step with currently valid options under the given selections.
     * Structure:
     * {
     *   steps: [
     *     { id, label, depends_on: [ids], join_mode: 'ALL'|'ANY', options: [ {id, label}, ... ] }
     *   ]
     * }
     */
    public function runtime_filtered_options($flow_id, $selections)
    {
        $flow_id = (int)$flow_id;
        $steps = $this->steps_by_flow($flow_id);

        $out_steps = array();
        foreach ($steps as $s) {
            $opts = $this->options_list((int)$s['id'], $flow_id, $selections);
            // Minimal payload for speed
            $opts_min = array_map(function($o){
                return array('id' => (int)$o['id'], 'label' => $o['label']);
            }, $opts);

            $out_steps[] = array(
                'id'         => (int)$s['id'],
                'label'      => $s['label'],
                'depends_on' => array_map('intval', (array)$s['depends_on']),
                'join_mode'  => ($s['join_mode'] === 'ANY') ? 'ANY' : 'ALL',
                'options'    => $opts_min
            );
        }

        return array('steps' => $out_steps);
    }

    /**
     * Resolve a full tuple of selections to an item_id using mappings.
     * If multiple mappings match, choose the most specific (fewest '*', i.e., highest specificity score).
     */
    public function resolve_item($flow_id, $tuple_rows)
    {
        $flow_id = (int)$flow_id;
        $tuple_assoc = $this->array_assoc_from_tuple($tuple_rows);

        $rows = $this->db->get_where($this->t_mappings, array('flow_id' => $flow_id))->result_array();

        $best = null;
        $best_score = -1;

        foreach ($rows as $r) {
            $key = $this->array_assoc_from_tuple($this->json_decode_arr($r['key_json']));
            if ($this->tuple_matches_key($tuple_assoc, $key)) {
                $score = $this->specificity_score($key);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best = (int)$r['item_id'];
                }
            }
        }

        if ($best > 0) return array('item_id' => $best);
        return null;
    }

    private function tuple_matches_key($tuple_assoc, $key_assoc)
    {
        // A tuple matches a mapping key if:
        // for every step in key_assoc,
        //  - key value is '*' OR equals tuple value.
        foreach ($key_assoc as $sid => $kval) {
            if ($kval === '*') continue;
            if (!isset($tuple_assoc[$sid])) return false;
            if ((int)$tuple_assoc[$sid] !== (int)$kval) return false;
        }
        return true;
    }

    /* ========================== Item Search (for mapping UI) ========================== */

    public function items_search($q, $limit = 10)
    {
        $q = trim((string)$q);
        $limit = (int)$limit;

        $this->db->select('item_id, name, item_number');
        $this->db->from($this->t_items);
        $this->db->limit($limit);

        if ($q !== '') {
            $this->db->group_start()
                ->like('name', $q)
                ->or_like('item_number', $q)
            ->group_end();
        }

        // Exclude deleted items if the column exists in your schema
        if ($this->db->field_exists('deleted', $this->t_items)) {
            $this->db->where('deleted', 0);
        }

        $rows = $this->db->get()->result_array();
        $out = array();
        foreach ($rows as $r) {
            $text = '['.($r['item_number'] ?? '').'] '.$r['name'];
            $out[] = array('id' => (int)$r['item_id'], 'text' => trim($text));
        }
        return $out;
    }
    
    
    
    
    
    // Return ALL options for a step (no dependency filtering) — for admin UI
public function options_all($step_id)
{
    return $this->db->order_by('sort_order asc, id asc')
        ->get_where($this->t_options, array('step_id' => (int)$step_id))
        ->result_array();
}


}
