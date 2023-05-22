<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct(); 
        user_login();
        $this->load->model('OperationModel');
        $this->load->model('OrdersModel');
    }
    public function index()
    {
        // $bat = $this->OperationModel->get_batallions();
        $this->load->model('DashboardModel');
        $depcount = $this->DashboardModel->get_deployedcount();
        $res = $this->DashboardModel->get_subgroup($_SESSION['userid'],$_SESSION['user_info']['Rank_id'],$_SESSION['user_info']['post']);
        $ops = array();
        $in_orders = $this->OrdersModel->get_in_orders($_SESSION['userid']);
        $out_orders = $this->OrdersModel->get_out_orders($_SESSION['userid']);
        if($_SESSION['user_info']['Rank_id'] == 1)
            $ops = $this->OperationModel->get_operations_brig($_SESSION['userid']);
        else if ($_SESSION['user_info']['Rank_id'] == 2)
            $ops = $this->OperationModel->get_operations_bat($_SESSION['userid']);
        $this->load->view("dashboard_view.php",array('sub_list' => $res,'ops' => $ops,'in_orders' => $in_orders,'out_orders' => $out_orders, 'depcount' => $depcount));
    }
    public function profile()
    {
        $this->load->view("profile_view.php");
    }
    public function test_proc()
    {
        $this->load->model('DashboardModel');
        $res = $this->DashboardModel->get_subgroup(18,5,$_SESSION['user_info']['post']);
        print_r($res);
    }
    public function create_operation()
    {
        //allow access only if Rank_id is 1 or redirect after 2 seconds
        if($_SESSION['user_info']['Rank_id']!=1)
        {
            echo 'You are not authorized to access this page. You will be redirected to dashboard in 2 seconds.';
            header("refresh:2;url=".base_url()."index.php/Dashboard");
        }
        else 
        {
            // get all batallions under the user's brigade
            $bat = $this->OperationModel->get_batallions();
            $this->load->view("create_operation_view.php",array('battalion_list' => $bat));
        }
    }
    public function add_operation()
    {
        //function to receive data from create_operation_view.php and add it to database
        $data = $_POST;
        if($_SESSION['user_info']['Rank_id'] != 1)
            return false;
        if($this->OperationModel->add_operation($data))
        {
            echo(json_encode(array(
                'status'=> 200,
                'message'=> 'Operation added successfully'
            )));
        }
        else
        {
            echo(json_encode(array(
                'status'=> 500,
                'message'=> 'Battalion is already part of an operation'
            )));
        }
    }

    public function recent_operations()
    {
        $ops = array();
        if($_SESSION['user_info']['Rank_id'] == 1)
            $ops = $this->OperationModel->get_operations_brig($_SESSION['userid']);
        else if ($_SESSION['user_info']['Rank_id'] == 2)
            $ops = $this->OperationModel->get_operations_bat($_SESSION['userid']);
        $this->load->view('recent_operations_view.php',array('ops' => $ops));
    }
}