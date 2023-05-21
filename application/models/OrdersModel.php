<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class OrdersModel extends CI_Model 
{

    public function get_idle_subordinates($rank, $userid, $post)
    {
        // get all subordinates of the user who are in the same post as the user";
        if($rank == 1)// if user is Brigadier, get id and names of all idle Colonels from Users table, Colonels have Rank_id as 2
        {
            $sql = "SELECT Users.User_id,concat('Col. ',Users.User_name) as User_name
                    FROM Users
                    WHERE Users.Rank_id = 2 AND Users.post = ? AND Users.Cur_status = 'Idle' ";
            $query = $this->db->query($sql,$post);
            $result = $query->result_array();
            return $result;
        }
        else if($rank == 2 || $rank == 3)// if user is Colonel or lt.Colonel get id and names of all idle majors from Users table, majors have Rank_id as 4
        {
            $sql = " SELECT Users.User_id,concat('Maj. ',Users.User_name) as User_name
                    FROM Users
                    WHERE Users.Rank_id = 4 AND Users.post = ? AND Users.Cur_status = 'Idle' ";
            $query = $this->db->query($sql,$post);
            $result = $query->result_array();
            return $result;
        }
        else if ($rank == 4)
        {
            $sql = " SELECT Users.User_id,concat('Hav. ',Users.User_name) as User_name
                    FROM Users
                    WHERE Users.Rank_id = 5 AND Users.post = ? AND Users.Cur_status = 'Idle' ";
            $query = $this->db->query($sql,$post);
            $result = $query->result_array();
            return $result;
        }
        else if ($rank == 5)
        {
            $sql = " SELECT Users.User_id,concat('Sep.  ',Users.User_name) as User_name
                    FROM Users
                    WHERE Users.Rank_id = 6 AND Users.post = ? AND Users.Cur_status = 'Idle' ";
            $query = $this->db->query($sql,$post);
            $result = $query->result_array();
            return $result;
        }
    }

    public function add_subgrp($data)
    {
        $rank = $data['rankId'];
        if(isset($data['leader']) && isset($data['name']))
        {
            $leader = $data['leader'];
            $name = $data['name'];
        }
        $post = $_SESSION['user_info']['post'];
        $userid = $_SESSION['user_info']['User_id'];
        if($rank == 1)
        {
            // Begin a transaction, if any errors occur, then it will rollback all the queries
            $this->db->trans_begin();

            // Get id of 2nd incharge whose rank id is 3 and status must be idle, select only one
            $sql = "SELECT Users.User_id
                    FROM Users
                    WHERE Users.Rank_id = 3 AND Users.post = ? AND Users.Cur_status = 'Idle' LIMIT 1";
            $query = $this->db->query($sql,$post);
            $result = $query->result_array();
            if(isset($result[0]['User_id']))
                $second_incharge = $result[0]['User_id'];
            else
                $second_incharge = null;
            // Query to add batallion into batallion table
            $sql = "INSERT INTO Batallion (Batallion_name,Commanding_officer,Second_IC) VALUES (?,?,?)";
            $query = $this->db->query($sql,array($name,$leader,$second_incharge));
            if ($this->db->trans_status() === FALSE)
            {
                    $this->db->trans_rollback();
                    return false;
            }
            else
                $this->db->trans_commit();

            // Query to add batallion into brigade table
             $i = 1;
             while ($i <= 4)
             {
                $field = 'Batallion'.$i;
                // Insert into Batallion.$i only if it in null
                $sql = "UPDATE Brigade SET $field = (SELECT Batallion_id FROM Batallion WHERE Commanding_officer = ? LIMIT 1) 
                WHERE Brigade_commander = ? AND $field IS NULL";
                $query = $this->db->query($sql,array($leader,$userid));
                // if field is not null, then continue to next field
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return false;
                }
                else
                    $this->db->trans_commit();

                if($this->db->affected_rows() == 0)
                {   
                    $i++;
                    continue;
                }
                else
                {
                    // set leader and second in command status to Deployed from Users table
                    $sql = "UPDATE Users SET Cur_status = 'Deployed' WHERE User_id in (?,?)";
                    $query = $this->db->query($sql,array($leader,$second_incharge));
                    // when all queries are successful, commit them, then return true
                        $this->db->trans_commit();
                        $this->db->trans_complete();
                        return true;
                }
             }
             $this->db->trans_rollback();
             $this->db->trans_complete();
             return false;

        }
        else if($rank == 2 || $rank == 3)
        {
            //Replicate the same as previous case, except no need for second in command, only company commander
            // Begin a transaction, if any errors occur, then it will rollback all the queries
            $this->db->trans_begin();
            $sql = "INSERT INTO Company (Company_name,Company_Commander) VALUES (?,?)";
            $query = $this->db->query($sql,array($name,$leader,));
            if ($this->db->trans_status() === FALSE)
            {
                    $this->db->trans_rollback();
                    return false;
            }
            else
                $this->db->trans_commit();
             $i = 1;
             while ($i <= 4)
             {
                $field = 'Company'.$i;
                // Insert into Company.$i only if it in null
                $sql = "UPDATE Batallion SET $field = (SELECT Company_id FROM Company WHERE Company_Commander = ? LIMIT 1) 
                WHERE Commanding_officer = ? AND $field IS NULL";
                $query = $this->db->query($sql,array($leader,$userid));
                // if field is not null, then continue to next field
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return false;
                }
                else
                    $this->db->trans_commit();

                if($this->db->affected_rows() == 0)
                {   
                    $i++;
                    continue;
                }
                else
                {
                    // set leader status to Deployed from Users table
                    $sql = "UPDATE Users SET Cur_status = 'Deployed' WHERE User_id in (?)";
                    $query = $this->db->query($sql,array($leader));
                    // when all queries are successful, commit them, then return true
                        $this->db->trans_commit();
                        $this->db->trans_complete();
                        return true;
                }
             }
             $this->db->trans_rollback();
             $this->db->trans_complete();
             return false;
        }
        else if($rank == 4)
        {
            $this->db->trans_begin();
            $sql = "INSERT INTO Platoon (Platoon_name,NCO) VALUES (?,?)";
            $query = $this->db->query($sql,array($name,$leader,));
            if ($this->db->trans_status() === FALSE)
            {
                    $this->db->trans_rollback();
                    return false;
            }
            else
                $this->db->trans_commit();
             $i = 1;
             while ($i <= 4)
             {
                $field = 'Platoon'.$i;
                // Insert into Platoon.$i only if it in null
                $sql = "UPDATE Company SET $field = (SELECT Platoon_id FROM Platoon WHERE NCO = ? LIMIT 1) 
                WHERE Company_Commander = ? AND $field IS NULL";
                $query = $this->db->query($sql,array($leader,$userid));
                // if field is not null, then continue to next field
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return false;
                }
                else
                    $this->db->trans_commit();

                if($this->db->affected_rows() == 0)
                {   
                    $i++;
                    continue;
                }
                else
                {
                    // set leader status to Deployed from Users table
                    $sql = "UPDATE Users SET Cur_status = 'Deployed' WHERE User_id in (?)";
                    $query = $this->db->query($sql,array($leader));
                    // when all queries are successful, commit them, then return true
                        $this->db->trans_commit();
                        $this->db->trans_complete();
                        return true;
                }
             }
             $this->db->trans_rollback();
             $this->db->trans_complete();
             return false;
            
        }
        else if($rank == 5)
        {
            $complete = 0;
            $squads = array('Anti_Tank','Medical','Sniper','Assault','Signals','Infantry');
            foreach($squads as $squad)
            {
                $this->db->trans_begin();
                $sql = "SELECT User_id FROM Users WHERE Rank_id = 6 AND Cur_Status = 'Idle' AND post = ? LIMIT 2";
                $query = $this->db->query($sql,$post);
                $result = $query->result_array();
                $members = array($result[0]['User_id'],$result[1]['User_id']);
                $sql = "INSERT INTO $squad (squad_mem1,squad_mem2) VALUES (?,?)";
                $query = $this->db->query($sql,array($result[0]['User_id'],$result[1]['User_id']));
                if ($this->db->trans_status() === FALSE)
                {
                        $this->db->trans_rollback();
                        return false;
                }
                else
                    $this->db->trans_commit();
                $field = $squad.'_id';
                $sql = "UPDATE Platoon SET $field = (SELECT squad_id FROM $squad ORDER BY squad_id DESC LIMIT 1) 
                WHERE NCO = ? AND $field IS NULL";
                $query = $this->db->query($sql,$userid);
                // if field is not null, then continue to next field
                if($this->db->affected_rows() == 0)
                {   
                    continue;
                }
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return false;
                }
                else
                    $this->db->trans_commit();
                // set squad member status to Deployed from Users table
                $sql = "UPDATE Users SET Cur_status = 'Deployed' WHERE User_id in (?,?)";
                $query = $this->db->query($sql,array($result[0]['User_id'],$result[1]['User_id']));
                // when all queries are successful, commit them, then return true
                $this->db->trans_commit();
                $this->db->trans_complete();
                $complete = 1;

            }
            if($complete == 1)
            {
                $this->db->trans_complete();
                return true;
            }
            else
            {
                $this->db->trans_rollback();
                return false;
            }
        }
    }

    public function custom_order($data)
    {
        $from = $data['fromUserId'];
        $to = $data['toUserId'];
        $order = $data['order'];
        //insert into orders table
        $sql = "INSERT INTO Orders (Order_name,Order_description,from_id,to_id) VALUES ('custom',?,?,?)";
        $query = $this->db->query($sql,array($order,$from,$to));
        if($this->db->affected_rows() == 0)
            return false;
        else
            return true;    
    }

    public function get_in_orders($userid)
    {
        //function to get incoming orders from orders table
        $sql = "SELECT * FROM Orders WHERE to_id = ? ORDER BY Start_date DESC";
        $query = $this->db->query($sql,$userid);
        if($query->num_rows() == 0)
            return array();
        else
            return $query->result_array();
    }
    public function get_out_orders($userid)
    {
        //function to get outgoing orders from orders table
        $sql = "SELECT * FROM Orders WHERE from_id = ?";
        $query = $this->db->query($sql,$userid);
        if($query->num_rows() == 0)
            return array();
        else
            return $query->result_array();
    }
    public function change_orders_status($data)
    {
        //function to change status of orders from 1 to 0
        $sql = "UPDATE Orders SET Order_status = 0 WHERE Order_id in (?)";
        $query = $this->db->query($sql,$data);
        if($query)
            return true;
        else
        {
            echo ($this->db->last_query()); die;
        }

    }

    public function remove_subgrp($subuserid)
    {
        // Function to remove subgroup
        $subgroup = '';
        $mygroup = '';
        $my_leader = '';
        $sub_leader = '';
        if($_SESSION['user_info']['Rank_id'] == 1)
        {
            $subgroup =  'Batallion';
            $mygroup = 'Brigade';
            $my_leader = 'Brigade_commander';
            $sub_leader = 'Commanding_officer';
            $sub_rank = 'Col. ';
        }
        else if ($_SESSION['user_info']['Rank_id'] == 2 || $_SESSION['user_info']['Rank_id'] == 3)
        {
            $subgroup =  'Company';
            $mygroup = 'Batallion';
            $my_leader = 'Commanding_officer';
            $sub_leader = 'Company_Commander';
            $sub_rank = 'Maj. ';
        }
        else if ($_SESSION['user_info']['Rank_id'] == 4)
        {
            $subgroup =  'Platoon';
            $mygroup = 'Company';
            $my_leader = 'Company_Commander';
            $sub_leader = 'NCO';
            $sub_rank = 'Hav. ';
        }
        else if($_SESSION['user_info']['Rank_id'] == 5)
        {
            $subgroup =  'Squad';
            $mygroup = 'Platoon';
            $my_leader = 'NCO';
        }
        // Start a transaction
        $this->db->trans_start();
        // Get id of subgroup whose leader's id us $subuserid
        $sql = "SELECT $subgroup"."_id FROM $subgroup WHERE $sub_leader = ?";
        $query = $this->db->query($sql,$subuserid);
        $result = $query->result_array();
        // If no such subgroup exists, return false
        if(isset($result[0][$subgroup.'_id']))
            $id = $result[0][$subgroup.'_id']; 
        else
            return false;
        // Get id of mygroup whose leader's id is $id 
        $sql = "DELETE FROM $subgroup WHERE $subgroup"."_id in (?)";
        $query = $this->db->query($sql,$id);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function promote($id)
    {
        $rank = $_SESSION['user_info']['Rank_id'] + 1;
        $post = $_SESSION['user_info']['post'];
        // Begin a transaction
        $this->db->trans_start();
        // Get Cur_status of user
        $sql = "SELECT Cur_status FROM Users WHERE User_id = ?";
        $query = $this->db->query($sql,$id);
        $result = $query->result_array();
        $status = $result[0]['Cur_status'];
        if($status == 'Deployed')
        {
           $sql = "CALL main_promote(?,?,?)";  
           $query = $this->db->query($sql,array($id,$rank,$post));
        }
        else if($status == 'Idle')
        {
            $sql = "CALL promote(?)";
            $query = $this->db->query($sql,$id);
        }
        $this->db->trans_commit();
        if ($this->db->trans_status() === FALSE)
            return false;
        else
            return true;
    }
    public function demote($id)
    {
        $rank = $_SESSION['user_info']['Rank_id'] + 1;
        $post = $_SESSION['user_info']['post'];
        // Begin a transaction
        $this->db->trans_start();
        // Get Cur_status of user
        $sql = "SELECT Cur_status FROM Users WHERE User_id = ?";
        $query = $this->db->query($sql,$id);
        $result = $query->result_array();
        $status = $result[0]['Cur_status'];
        if($status == 'Deployed')
        {
           $sql = "CALL main_demote(?,?,?)";  
           $query = $this->db->query($sql,array($id,$rank,$post));
        }
        else if($status == 'Idle')
        {
            $sql = "CALL demote(?)";
            $query = $this->db->query($sql,$id);
        }
        $this->db->trans_commit();
        if ($this->db->trans_status() === FALSE)
            return false;
        else
            return true;
    }
}

?>