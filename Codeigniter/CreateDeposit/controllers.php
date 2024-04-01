<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Leads extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
    }
    public function add_deposit()
    {
        if (is_admin() || has_permission('finance', '', 'create_deposit')) {

            $url = FRONT_URL . '/crm/create-deposit';
            $key = FRONT_KEY;

            $this->load->library('form_validation');

            $this->form_validation->set_rules('userID', 'UserID', 'required');
            $this->form_validation->set_rules('deposit-amount', 'deposit-amount', 'required');

            if ($this->form_validation->run() != FALSE) {

                $userID = $this->input->post('userID');
                $amount = $this->input->post('deposit-amount');
                $comment = $this->input->post('deposit-comment');
                $paymentSystem = $this->input->post('deposit-payment-system') ?: 'crm : ' . $this->session->userdata('staff_user_id');
                $to_profile = !!$this->input->post('to-profile-check');

                $data = [
                    'user_id' => $userID,
                    'amount' => $amount,
                    'comment' => $comment,
                    'payment_system' => $paymentSystem,
                    'to_profile' => $to_profile
                ];

                $ch = curl_init($url);

                curl_setopt_array($ch, array(
                    CURLOPT_POST => TRUE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_HTTPHEADER => array(
                        'X-API-KEY: ' . $key,
                        'Content-Type: application/json'
                    ),
                    CURLOPT_POSTFIELDS => json_encode($data)
                ));

                $response = curl_exec($ch);
                curl_close($ch);
                if ($response === FALSE) {
                    return [
                        'status' => 'danger',
                        'message' => _l('failed')
                    ];
                }

                $response_data = json_decode($response, TRUE);
                if ($response_data) {
                    $this->session->set_flashdata('success_message', 'Deposit added successfully.');
                    redirect('admin/leads');
                }
            }
        } else {
            redirect('admin/leads');
        }
    }
}
