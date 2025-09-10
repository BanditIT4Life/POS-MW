<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Delivery_report extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'form', 'date', 'security'));
		$this->load->library(array('session'));
		$this->load->model(array('Sale', 'Appconfig'));
	}

	/**
	 * Report landing — shows filter bar & results table.
	 * Defaults to Delivery Date Range = today (not sale date) on first load with no GET.
	 */
	public function index()
{
	$this->load->helper(array('url', 'form', 'date', 'security'));
	$this->load->model('Sale');
	$this->load->model('Appconfig');

	// --- Read filters (defaults same as before) ---
	$filter_type = $this->input->get('filter_type', TRUE);
	$valid_types = array('delivery_date','sale_date','local_delivery_only');
	if (!in_array($filter_type, $valid_types, true)) {
		$filter_type = 'delivery_date';
	}

	$today = date('Y-m-d');

	$sale_from  = $this->input->get('sale_date_from', TRUE) ?: $today;
	$sale_to    = $this->input->get('sale_date_to', TRUE) ?: $today;

	$deliv_from = $this->input->get('delivery_date_from', TRUE) ?: $today;
	$deliv_to   = $this->input->get('delivery_date_to', TRUE) ?: $today;

	// --- Pagination (25 default; allowed 25/50/100) ---
	$per_page_in = (int)$this->input->get('per_page', TRUE);
	$per_page = in_array($per_page_in, array(25,50,100), true) ? $per_page_in : 25;

	$page_in = (int)$this->input->get('page');
	$page = ($page_in > 0) ? $page_in : 1;
	$offset = ($page - 1) * $per_page;

	$filters = array(
		'filter_type'         => $filter_type,
		'sale_date_from'      => $sale_from,
		'sale_date_to'        => $sale_to,
		'delivery_date_from'  => $deliv_from,
		'delivery_date_to'    => $deliv_to,
		'limit'               => $per_page,
		'offset'              => $offset,
	);
 
 $data['sale_date_from']     = $sale_from;
$data['sale_date_to']       = $sale_to;
$data['delivery_date_from'] = $deliv_from;
$data['delivery_date_to']   = $deliv_to;


	// Display strings for the daterange input (YYYY-MM-DD as used in view)
	$data['active_from_disp'] = ($filter_type === 'sale_date') ? $sale_from : $deliv_from;
	$data['active_to_disp']   = ($filter_type === 'sale_date') ? $sale_to   : $deliv_to;

	// --- Run model (now returns rows + total) ---
	$result = $this->Sale->get_deliveries($filters);

	$data['rows']      = $result['rows'];
	$data['total']     = (int)$result['total'];
  $data['result_count'] = $data['total']; 
	$data['per_page']  = $per_page;
	$data['page']      = $page;
	$data['pages']     = ($per_page > 0) ? (int)ceil($data['total'] / $per_page) : 1;

	// Pass config/flags already used in the view
	$data['gcal_enabled'] = (bool)$this->Appconfig->get('gcal_delivery_enabled');

	// CSRF (used by your AJAX)
	$data['csrf_name'] = $this->security->get_csrf_token_name();
	$data['csrf_hash'] = $this->security->get_csrf_hash();

	// Current filter type (for select)
	$data['filter_type'] = $filter_type;

	// Build a base query string for page links (keep all params except page & per_page)
	$params = $this->input->get();
	unset($params['page'], $params['per_page']);
	$data['query_string_base'] = http_build_query($params);

	$this->load->view('partial/header');
	$this->load->view('reports/delivery_report', $data);
	$this->load->view('partial/footer');
}


	/**
	 * Toggle completion on a single sale by token in sales.comment
	 * POST: sale_id, done (0|1)
	 */
	public function complete()
	{
		$this->_enforce_post_json();

		$sale_id = (int)$this->input->post('sale_id');
		$done    = (int)$this->input->post('done') === 1 ? 1 : 0;

		if ($sale_id <= 0) {
			return $this->_json(array('ok' => false, 'error' => 'Invalid sale_id'), 400);
		}

		$this->Sale->set_delivery_complete_flag($sale_id, $done);
		return $this->_json(array('ok' => true));
	}

	/**
	 * Bulk complete/uncomplete for selected sale_ids (array) or "all visible"
	 * POST: sale_ids[] (required), done (0|1)
	 */
	public function complete_bulk()
	{
		$this->_enforce_post_json();

		$ids  = $this->input->post('sale_ids');
		$done = (int)$this->input->post('done') === 1 ? 1 : 0;

		if (!is_array($ids) || empty($ids)) {
			return $this->_json(array('ok' => false, 'error' => 'No sale_ids provided'), 400);
		}

		// sanitize ids
		$sale_ids = array();
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id > 0) $sale_ids[] = $id;
		}
		if (empty($sale_ids)) {
			return $this->_json(array('ok' => false, 'error' => 'No valid sale_ids'), 400);
		}

		$this->Sale->bulk_set_delivery_complete_flags($sale_ids, $done);
		return $this->_json(array('ok' => true, 'count' => count($sale_ids)));
	}

	/**
	 * Create or update a Google Calendar event for a Local Delivery sale row.
	 * POST: sale_id
	 * Writes [[GCAL:EVENT_ID=...]] token into sales.comment on success.
	 */
	public function gcal_create_event()
	{
		$this->_enforce_post_json();

		// Hard check toggle
		$gcal_enabled = (bool)$this->Appconfig->get('gcal_delivery_enabled');
		if (!$gcal_enabled) {
			return $this->_json(array('ok' => false, 'error' => 'Google Calendar is OFF'));
		}

		$sale_id = (int)$this->input->post('sale_id');
		if ($sale_id <= 0) {
			return $this->_json(array('ok' => false, 'error' => 'Invalid sale_id'), 400);
		}

		// Fetch a single sale delivery row via model (re-using get_deliveries with a single id)
		$filters = array(
			'filter_type'          => 'local_delivery_only', // not relevant; we fetch by id
			'sale_date_from'       => date('Y-m-d', strtotime('-10 years')),
			'sale_date_to'         => date('Y-m-d', strtotime('+10 years')),
			'delivery_date_from'   => date('Y-m-d', strtotime('-10 years')),
			'delivery_date_to'     => date('Y-m-d', strtotime('+10 years')),
			'single_sale_id'       => $sale_id
		);

		$rows = $this->Sale->get_deliveries($filters);
		if (empty($rows)) {
			return $this->_json(array('ok' => false, 'error' => 'Sale not found or not a Local Delivery'));
		}
		$row = $rows[0];

		$calendar_id = trim((string)$this->Appconfig->get('gcal_calendar_id'));
		$auth_blob   = (string)$this->Appconfig->get('gcal_auth_json');

		if ($calendar_id === '' || $auth_blob === '') {
			return $this->_json(array('ok' => false, 'error' => 'GCAL not configured (calendar/credentials)'));
		}

		// Prepare event fields
		$title = 'Local Delivery — ' . $row['customer_name'];
		if (!empty($row['customer_phone'])) {
			$title .= ' — ' . $row['customer_phone'];
		}

		$location_parts = array_filter(array(
			$row['address_1'] . (trim($row['address_2']) !== '' ? ' ' . $row['address_2'] : ''),
			trim($row['city']),
			trim($row['state']),
			trim($row['postal_code'])
		));
		$location = implode(', ', $location_parts);

		$event_id_existing = $row['gcal_event_id'] ?: null;

		// Build event time
		$tz_offset = $this->Sale->php_timezone_offset(); // ±HH:MM
		$start_iso = null;
		$end_iso   = null;
		$all_day   = false;

		if (!empty($row['delivery_date'])) {
			if (!empty($row['delivery_time_start_iso']) && !empty($row['delivery_time_end_iso'])) {
				// timed range
				$start_iso = $row['delivery_time_start_iso'] . $tz_offset;
				$end_iso   = $row['delivery_time_end_iso'] . $tz_offset;
			} else {
				// all-day event
				$all_day = true;
			}
		} else {
			// No delivery date parsed; fallback: all-day on the SALE date
			$all_day = true;
		}

		// Try Google Client (optional dependency). If not present, fail gracefully.
		try {
			if (!class_exists('\\Google_Client')) {
				// If vendor autoload exists, try to load it (user may have installed Google API client)
				$autoload1 = FCPATH . 'vendor/autoload.php';
				$autoload2 = APPPATH . 'third_party/vendor/autoload.php';
				if (file_exists($autoload1)) require_once $autoload1;
				elseif (file_exists($autoload2)) require_once $autoload2;
			}
		} catch (\Throwable $e) {
			// noop — below will handle missing class
		}

		if (!class_exists('\\Google_Client') || !class_exists('\\Google_Service_Calendar')) {
			return $this->_json(array('ok' => false, 'error' => 'Google API client not available on server'));
		}

		try {
			$client = new \Google_Client();
			$client->setApplicationName('OSPOS Local Delivery');
			$client->setScopes(\Google_Service_Calendar::CALENDAR);
			$client->setAccessType('offline');

			// Credentials: service account JSON or OAuth token blob stored in config
			// Expecting a Service Account JSON (recommended). Place JSON content directly in gcal_auth_json.
			$creds = json_decode($auth_blob, true);
			if (!is_array($creds)) {
				return $this->_json(array('ok' => false, 'error' => 'Invalid gcal_auth_json'));
			}
			$client->setAuthConfig($creds);

			$service = new \Google_Service_Calendar($client);

			$event = new \Google_Service_Calendar_Event();
			$event->setSummary($title);
			if ($location) $event->setLocation($location);

			// Description: items list
			$event->setDescription($row['items_text']);

			if ($all_day) {
				$event->setStart(new \Google_Service_Calendar_EventDateTime([
					'date' => !empty($row['delivery_date']) ? $row['delivery_date'] : substr($row['sale_time'], 0, 10)
				]));
				$event->setEnd(new \Google_Service_Calendar_EventDateTime([
					'date' => !empty($row['delivery_date']) ? $row['delivery_date'] : substr($row['sale_time'], 0, 10)
				]));
			} else {
				$event->setStart(new \Google_Service_Calendar_EventDateTime([
					'dateTime' => $start_iso
				]));
				$event->setEnd(new \Google_Service_Calendar_EventDateTime([
					'dateTime' => $end_iso
				]));
			}

			if ($event_id_existing) {
				// Update existing
				$updated = $service->events->patch($calendar_id, $event_id_existing, $event);
				$event_id = $updated->getId();
			} else {
				// Create new
				$created = $service->events->insert($calendar_id, $event);
				$event_id = $created->getId();
			}

			$this->Sale->attach_gcal_event_token($sale_id, $event_id);

			return $this->_json(array('ok' => true, 'event_id' => $event_id));
		} catch (\Throwable $e) {
			return $this->_json(array('ok' => false, 'error' => 'GCAL error: ' . $e->getMessage()));
		}
	}

	/* ------------------------------ Helpers ------------------------------ */

	private function _enforce_post_json()
	{
		if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
			$this->_json(array('ok' => false, 'error' => 'POST required'), 405);
			exit;
		}
		// OSPOS usually sets CSRF via global ajaxSetup; no-op here.
	}

	private function _json($payload, $status_code = 200)
	{
		$this->output
			->set_status_header($status_code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
