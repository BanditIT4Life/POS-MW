<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Universal Quick Add (UQA)
 * Controller: application/controllers/Quick_add.php
 *
 * JSON endpoints for:
 *  - Flows (tabs): list/create/update/delete, enable/disable, reorder
 *  - Steps (dropdown-only): list/create/update/delete, reorder, set dependencies (ALL/ANY)
 *  - Options (for dropdowns): list/create/update/delete, reorder
 *  - Scoped options (dependent steps): attach/detach child options to parent option combos
 *  - Mappings (selection tuple -> existing OSPOS item): list/create/update/delete
 *  - Runtime helpers: get filtered options given current selections; resolve selections -> item
 *
 * All responses are JSON: { ok: bool, data: mixed|null, message: string, csrf: { name, hash } }
 * Mutations require Items or Config module grants.
 */
class Quick_add extends CI_Controller
{
    const JOIN_ALL = 'ALL';
    const JOIN_ANY = 'ANY';

    public function __construct()
    {
        parent::__construct();

        // Models
        $this->load->model('Quick_add_model');
        $this->load->model('Employee');

        // Helpers & libs
        $this->load->helper(array('url', 'security'));
        $this->load->library('form_validation');

        // Force JSON for all endpoints
        $this->output->set_content_type('application/json');
    }

    /* --------------------------- Utilities --------------------------- */

    private function csrf_payload()
    {
        return array(
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash()
        );
    }

    private function respond($ok, $data = null, $message = '', $http_code = 200)
    {
        $payload = array('ok' => (bool)$ok, 'data' => $data, 'message' => $message, 'csrf' => $this->csrf_payload());
        $this->output->set_status_header($http_code)->set_output(json_encode($payload));
    }

    private function bad_request($message = 'Bad request')
    {
        $this->respond(false, null, $message, 400);
    }

    private function forbidden($message = 'Forbidden')
    {
        $this->respond(false, null, $message, 403);
    }

    private function method_post_only()
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            $this->bad_request('POST required');
            exit;
        }
    }

    private function require_admin_grant()
    {
        $pid = (int)$this->session->userdata('person_id');
        $has = $this->Employee->has_module_grant('items', $pid) || $this->Employee->has_module_grant('config', $pid);
        if (!$has) {
            $this->forbidden('Items or Config permission required');
            exit;
        }
    }

    private function json_body()
    {
        $raw = $this->input->raw_input_stream;
        if (!$raw) return array();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function sanitize_int_array($arr)
    {
        $out = array();
        foreach ((array)$arr as $v) $out[] = (int)$v;
        return $out;
    }

    /* --------------------------- Index / CSRF --------------------------- */

    public function index()
    {
        $this->respond(false, null, 'No endpoint. See /flows, /steps, /options, /mappings, /runtime.');
    }

    public function csrf()
    {
        // Handy endpoint to fetch a fresh token
        $this->respond(true, null, 'CSRF token');
    }

    /* --------------------------- FLOWS --------------------------- */

    /**
     * GET /quick_add/flows
     * List flows (tabs).
     * Optional query params: enabled=1 to filter enabled only
     */
    public function flows()
    {
        $enabled_only = (int)$this->input->get('enabled') === 1;
        $flows = $this->Quick_add_model->flows_list($enabled_only);
        $this->respond(true, array('flows' => $flows));
    }

    /**
     * POST /quick_add/flows_create
     * { name: string, sort_order?: int, is_enabled?: bool }
     */
    public function flows_create()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $name = trim((string)($b['name'] ?? ''));
        $sort = isset($b['sort_order']) ? (int)$b['sort_order'] : null;
        $enabled = isset($b['is_enabled']) ? (bool)$b['is_enabled'] : false;

        if ($name === '') return $this->bad_request('name required');

        $flow = $this->Quick_add_model->flow_create($name, $sort, $enabled);
        $this->respond(true, array('flow' => $flow), 'Flow created');
    }

    /**
     * POST /quick_add/flows_update
     * { id: int, name?: string, sort_order?: int, is_enabled?: bool }
     */
    public function flows_update()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $attrs = array();
        if (isset($b['name'])) $attrs['name'] = trim((string)$b['name']);
        if (isset($b['sort_order'])) $attrs['sort_order'] = (int)$b['sort_order'];
        if (isset($b['is_enabled'])) $attrs['is_enabled'] = (bool)$b['is_enabled'];

        $updated = $this->Quick_add_model->flow_update($id, $attrs);
        $this->respond(true, array('flow' => $updated), 'Flow updated');
    }

    /**
     * POST /quick_add/flows_delete
     * { id: int }
     */
    public function flows_delete()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $this->Quick_add_model->flow_delete($id);
        $this->respond(true, null, 'Flow deleted');
    }

    /* --------------------------- STEPS (Dropdown-only) --------------------------- */

    /**
     * GET /quick_add/steps?flow_id=#
     * List steps for a flow (ordered).
     */
    public function steps()
    {
        $flow_id = (int)$this->input->get('flow_id');
        if ($flow_id <= 0) return $this->bad_request('flow_id required');

        $steps = $this->Quick_add_model->steps_list($flow_id);
        $this->respond(true, array('steps' => $steps));
    }

    /**
     * POST /quick_add/steps_create
     * {
     *   flow_id: int,
     *   label: string,
     *   sort_order?: int,
     *   depends_on?: int[] (parent step IDs),
     *   join_mode?: "ALL"|"ANY"   // default ALL
     * }
     * Step type is implicitly "dropdown" (no free-text).
     */
    public function steps_create()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $flow_id = (int)($b['flow_id'] ?? 0);
        $label = trim((string)($b['label'] ?? ''));
        $sort = isset($b['sort_order']) ? (int)$b['sort_order'] : null;
        $depends_on = $this->sanitize_int_array($b['depends_on'] ?? array());
        $join_mode = strtoupper((string)($b['join_mode'] ?? self::JOIN_ALL));
        if ($join_mode !== self::JOIN_ALL && $join_mode !== self::JOIN_ANY) $join_mode = self::JOIN_ALL;

        if ($flow_id <= 0) return $this->bad_request('flow_id required');
        if ($label === '') return $this->bad_request('label required');

        $step = $this->Quick_add_model->step_create($flow_id, $label, $sort, $depends_on, $join_mode);
        $this->respond(true, array('step' => $step), 'Step created');
    }

    /**
     * POST /quick_add/steps_update
     * {
     *   id: int,
     *   label?: string,
     *   sort_order?: int,
     *   depends_on?: int[],
     *   join_mode?: "ALL"|"ANY"
     * }
     */
    public function steps_update()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $attrs = array();
        if (isset($b['label'])) $attrs['label'] = trim((string)$b['label']);
        if (isset($b['sort_order'])) $attrs['sort_order'] = (int)$b['sort_order'];
        if (array_key_exists('depends_on', $b)) $attrs['depends_on'] = $this->sanitize_int_array($b['depends_on']);
        if (isset($b['join_mode'])) {
            $jm = strtoupper((string)$b['join_mode']);
            if ($jm === self::JOIN_ALL || $jm === self::JOIN_ANY) $attrs['join_mode'] = $jm;
        }

        $updated = $this->Quick_add_model->step_update($id, $attrs);
        $this->respond(true, array('step' => $updated), 'Step updated');
    }

    /**
     * POST /quick_add/steps_reorder
     * { flow_id: int, ordered_ids: int[] }
     */
    public function steps_reorder()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $flow_id = (int)($b['flow_id'] ?? 0);
        $ordered_ids = $this->sanitize_int_array($b['ordered_ids'] ?? array());

        if ($flow_id <= 0) return $this->bad_request('flow_id required');
        if (empty($ordered_ids)) return $this->bad_request('ordered_ids required');

        $this->Quick_add_model->steps_reorder($flow_id, $ordered_ids);
        $this->respond(true, null, 'Steps reordered');
    }

    /**
     * POST /quick_add/steps_delete
     * { id: int }
     */
    public function steps_delete()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $this->Quick_add_model->step_delete($id);
        $this->respond(true, null, 'Step deleted');
    }

    /* --------------------------- OPTIONS (Dropdown values) --------------------------- */

    /**
     * GET /quick_add/options?step_id=# [&flow_id=#&selections_json={step_id:option_id,...}]
     * - If the step has dependencies, parent selections can be provided to filter the options.
     */
    public function options()
{
    $step_id = (int)$this->input->get('step_id');
    if ($step_id <= 0) return $this->bad_request('step_id required');

    // NEW: admin/raw flag
    $raw = ((int)$this->input->get('raw') === 1);
    if ($raw) {
        $opts = $this->Quick_add_model->options_all($step_id);
        return $this->respond(true, array('options' => $opts));
    }

    $flow_id = (int)$this->input->get('flow_id'); // optional
    $selections = array();
    $sj = $this->input->get('selections_json');
    if ($sj) {
        $decoded = json_decode($sj, true);
        if (is_array($decoded)) {
            foreach ($decoded as $k => $v) $selections[(int)$k] = (int)$v;
        }
    }

    $opts = $this->Quick_add_model->options_list($step_id, $flow_id, $selections);
    $this->respond(true, array('options' => $opts));
}


    /**
     * POST /quick_add/options_create
     * { step_id: int, label: string, sort_order?: int }
     * For dropdown-only steps.
     */
    public function options_create()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $step_id = (int)($b['step_id'] ?? 0);
        $label = trim((string)($b['label'] ?? ''));
        $sort = isset($b['sort_order']) ? (int)$b['sort_order'] : null;

        if ($step_id <= 0) return $this->bad_request('step_id required');
        if ($label === '') return $this->bad_request('label required');

        $opt = $this->Quick_add_model->option_create($step_id, $label, $sort);
        $this->respond(true, array('option' => $opt), 'Option created');
    }

    /**
     * POST /quick_add/options_update
     * { id: int, label?: string, sort_order?: int }
     */
    public function options_update()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $attrs = array();
        if (isset($b['label'])) $attrs['label'] = trim((string)$b['label']);
        if (isset($b['sort_order'])) $attrs['sort_order'] = (int)$b['sort_order'];

        $opt = $this->Quick_add_model->option_update($id, $attrs);
        $this->respond(true, array('option' => $opt), 'Option updated');
    }

    /**
     * POST /quick_add/options_reorder
     * { step_id: int, ordered_ids: int[] }
     */
    public function options_reorder()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $step_id = (int)($b['step_id'] ?? 0);
        $ordered_ids = $this->sanitize_int_array($b['ordered_ids'] ?? array());

        if ($step_id <= 0) return $this->bad_request('step_id required');
        if (empty($ordered_ids)) return $this->bad_request('ordered_ids required');

        $this->Quick_add_model->options_reorder($step_id, $ordered_ids);
        $this->respond(true, null, 'Options reordered');
    }

    /**
     * POST /quick_add/options_delete
     * { id: int }
     */
    public function options_delete()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $this->Quick_add_model->option_delete($id);
        $this->respond(true, null, 'Option deleted');
    }

    /* -------- Scoped options for dependent steps (attach/detach to parent combos) -------- */

    /**
     * POST /quick_add/scope_attach
     * {
     *   child_step_id: int,
     *   child_option_id: int,
     *   parents: [ { step_id: int, option_id: int }, ... ] // 1..N parents (MANY means combo)
     * }
     * Attaches a child option to the given parent option combo (for JOIN_ALL logic).
     * For JOIN_ANY, attach the child option once per parent option (model enforces semantics).
     */
    public function scope_attach()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $child_step_id  = (int)($b['child_step_id'] ?? 0);
        $child_option_id= (int)($b['child_option_id'] ?? 0);
        $parents_in = (array)($b['parents'] ?? array());

        if ($child_step_id <= 0 || $child_option_id <= 0) return $this->bad_request('child_step_id and child_option_id required');

        $parents = array();
        foreach ($parents_in as $row) {
            $ps = (int)($row['step_id'] ?? 0);
            $po = (int)($row['option_id'] ?? 0);
            if ($ps > 0 && $po > 0) $parents[] = array('step_id' => $ps, 'option_id' => $po);
        }
        if (empty($parents)) return $this->bad_request('parents required (at least one)');

        $this->Quick_add_model->scope_attach($child_step_id, $child_option_id, $parents);
        $this->respond(true, null, 'Scope attached');
    }

    /**
     * POST /quick_add/scope_detach
     * { child_step_id: int, child_option_id: int, parents: [ {step_id:int, option_id:int}, ... ] }
     */
    public function scope_detach()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $child_step_id  = (int)($b['child_step_id'] ?? 0);
        $child_option_id= (int)($b['child_option_id'] ?? 0);
        $parents_in = (array)($b['parents'] ?? array());

        if ($child_step_id <= 0 || $child_option_id <= 0) return $this->bad_request('child_step_id and child_option_id required');

        $parents = array();
        foreach ($parents_in as $row) {
            $ps = (int)($row['step_id'] ?? 0);
            $po = (int)($row['option_id'] ?? 0);
            if ($ps > 0 && $po > 0) $parents[] = array('step_id' => $ps, 'option_id' => $po);
        }
        if (empty($parents)) return $this->bad_request('parents required (at least one)');

        $this->Quick_add_model->scope_detach($child_step_id, $child_option_id, $parents);
        $this->respond(true, null, 'Scope detached');
    }

    /* --------------------------- MAPPINGS (Selections -> Item) --------------------------- */

    /**
     * GET /quick_add/mappings?flow_id=#
     * List mappings for a flow
     */
    public function mappings()
    {
        $flow_id = (int)$this->input->get('flow_id');
        if ($flow_id <= 0) return $this->bad_request('flow_id required');

        $list = $this->Quick_add_model->mappings_list($flow_id);
        $this->respond(true, array('mappings' => $list));
    }

    /**
     * POST /quick_add/mappings_create
     * {
     *   flow_id: int,
     *   key: [ { step_id:int, option_id:int|"*" }, ... ],   // tuple; "*" allowed for wildcard
     *   item_id: int
     * }
     */
    public function mappings_create()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $flow_id = (int)($b['flow_id'] ?? 0);
        $key_in  = (array)($b['key'] ?? array());
        $item_id = (int)($b['item_id'] ?? 0);

        if ($flow_id <= 0) return $this->bad_request('flow_id required');
        if ($item_id <= 0) return $this->bad_request('item_id required');

        $key = array();
        foreach ($key_in as $row) {
            $sid = (int)($row['step_id'] ?? 0);
            $oid_raw = $row['option_id'] ?? null;
            if ($sid <= 0) continue;
            if ($oid_raw === '*') {
                $key[] = array('step_id' => $sid, 'option_id' => '*');
            } else {
                $oid = (int)$oid_raw;
                if ($oid > 0) $key[] = array('step_id' => $sid, 'option_id' => $oid);
            }
        }
        if (empty($key)) return $this->bad_request('key required');

        $mapping = $this->Quick_add_model->mapping_create($flow_id, $key, $item_id);
        $this->respond(true, array('mapping' => $mapping), 'Mapping created');
    }

    /**
     * POST /quick_add/mappings_update
     * { id: int, key?: [...], item_id?: int }
     */
    public function mappings_update()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $attrs = array();

        if (isset($b['item_id'])) {
            $attrs['item_id'] = (int)$b['item_id'];
            if ($attrs['item_id'] <= 0) return $this->bad_request('item_id invalid');
        }

        if (isset($b['key'])) {
            $key = array();
            foreach ((array)$b['key'] as $row) {
                $sid = (int)($row['step_id'] ?? 0);
                $oid_raw = $row['option_id'] ?? null;
                if ($sid <= 0) continue;
                if ($oid_raw === '*') {
                    $key[] = array('step_id' => $sid, 'option_id' => '*');
                } else {
                    $oid = (int)$oid_raw;
                    if ($oid > 0) $key[] = array('step_id' => $sid, 'option_id' => $oid);
                }
            }
            if (empty($key)) return $this->bad_request('key invalid/empty');
            $attrs['key'] = $key;
        }

        $updated = $this->Quick_add_model->mapping_update($id, $attrs);
        $this->respond(true, array('mapping' => $updated), 'Mapping updated');
    }

    /**
     * POST /quick_add/mappings_delete
     * { id: int }
     */
    public function mappings_delete()
    {
        $this->method_post_only();
        $this->require_admin_grant();

        $b = $this->json_body() ?: $this->input->post();
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) return $this->bad_request('id required');

        $this->Quick_add_model->mapping_delete($id);
        $this->respond(true, null, 'Mapping deleted');
    }

    /* --------------------------- RUNTIME --------------------------- */

    /**
     * POST /quick_add/runtime_options
     * {
     *   flow_id: int,
     *   selections: { [step_id:int]: option_id:int }  // current picks; may be partial
     * }
     * Returns for each step: its filtered options (based on Depends On and scoped attachments).
     */
    public function runtime_options()
    {
        $this->method_post_only();

        $b = $this->json_body() ?: $this->input->post();
        $flow_id = (int)($b['flow_id'] ?? 0);
        $selections_in = (array)($b['selections'] ?? array());

        if ($flow_id <= 0) return $this->bad_request('flow_id required');

        $selections = array();
        foreach ($selections_in as $k => $v) {
            $sid = (int)$k;
            $oid = (int)$v;
            if ($sid > 0 && $oid > 0) $selections[$sid] = $oid;
        }

        $result = $this->Quick_add_model->runtime_filtered_options($flow_id, $selections);
        // Expected structure:
        // {
        //   steps: [
        //     { id, label, depends_on: [ids], join_mode: 'ALL'|'ANY', options: [ {id, label}, ... ] },
        //     ...
        //   ]
        // }
        $this->respond(true, $result);
    }

    /**
     * POST /quick_add/resolve
     * {
     *   flow_id: int,
     *   selections: [ { step_id:int, option_id:int }, ... ] // complete tuple
     * }
     * Returns: { item_id:int } on success, or ok:false with message if no mapping.
     */
    public function resolve()
    {
        $this->method_post_only();

        $b = $this->json_body() ?: $this->input->post();
        $flow_id = (int)($b['flow_id'] ?? 0);
        $tuple_in = (array)($b['selections'] ?? array());

        if ($flow_id <= 0) return $this->bad_request('flow_id required');

        $tuple = array();
        foreach ($tuple_in as $row) {
            $sid = (int)($row['step_id'] ?? 0);
            $oid = (int)($row['option_id'] ?? 0);
            if ($sid > 0 && $oid > 0) $tuple[] = array('step_id' => $sid, 'option_id' => $oid);
        }
        if (empty($tuple)) return $this->bad_request('selections required');

        $resolved = $this->Quick_add_model->resolve_item($flow_id, $tuple);
        if ($resolved && isset($resolved['item_id']) && (int)$resolved['item_id'] > 0) {
            $this->respond(true, $resolved, 'Resolved');
        } else {
            $this->respond(false, null, 'No mapping configured for the selected combination', 404);
        }
    }

    /* --------------------------- OPTIONAL: item search for mapping UI --------------------------- */

    /**
     * GET /quick_add/items_search?q=term&limit=10
     * Lightweight search passthrough to help mapping UI find an item quickly.
     * Returns: [ { id:item_id, text:"[item_number] name" }, ... ]
     */
    public function items_search()
    {
        $q = trim((string)$this->input->get('q'));
        $limit = (int)$this->input->get('limit');
        if ($limit <= 0 || $limit > 50) $limit = 10;

        $results = $this->Quick_add_model->items_search($q, $limit);
        $this->respond(true, array('results' => $results));
    }
}
