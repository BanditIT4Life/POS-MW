<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Delivery_report extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Sale');
        $this->load->model('Employee');
    }

    public function index()
    {
        $person_id = $this->session->userdata('person_id');

        if (!$person_id)
        {
            redirect('login');
        }

        $data['user_info'] = $this->Employee->get_info($person_id);

        // ðŸ”¥ Safer way to load allowed_modules
        $allowed_modules = $this->session->userdata('allowed_modules');
        if (empty($allowed_modules) || !is_array($allowed_modules))
        {
            $allowed_modules = array(); // Make sure it's always an array
        }
        $data['allowed_modules'] = $allowed_modules;

        $this->load->view('reports/delivery_report', $data);
    }

public function get_data()
{
    $from_date = $this->input->get('from_date');
    $to_date = $this->input->get('to_date');
    $delivery_from_date = $this->input->get('delivery_from_date');
    $delivery_to_date = $this->input->get('delivery_to_date');
    $local_delivery_from_date = $this->input->get('local_delivery_from_date');
    $local_delivery_to_date = $this->input->get('local_delivery_to_date');

    $limit = $this->input->get('limit') ?? 25; // Default 25
    $offset = $this->input->get('offset') ?? 0; // Default 0

    $sales = $this->Sale->get_delivery_sales($from_date, $to_date, $delivery_from_date, $delivery_to_date, $local_delivery_from_date, $local_delivery_to_date);

    $total = count($sales); // Total records before slicing
    $paged_sales = array_slice($sales, $offset, $limit); // Only return limited slice

    echo json_encode([
        'sales' => $paged_sales,
        'total' => $total
    ]);
}



}
?>
