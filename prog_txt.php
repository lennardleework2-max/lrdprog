<?php
	ob_start();
    ob_clean();
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");

	//require_once('ezpdfclass/class/class.ezpdf.php');
 	$xline = "\r\n";
    $delimeter = chr(9);
    //$delimeter = 'chr(9)' ;tab 
	$xchunk1 = '';
    $xchunk3 = '';
    ini_set('display_errors', '1');
    //ini_set('display_startup_errors', '1');
    error_reporting(E_ALL); 


    
        $xtable=$_POST["tablename_hidden"];
        $xmodule=$_POST["main_header_hidden"];

        //getting header fields
        $fields_count = 0;
        $fields = '';
        foreach($_POST["fields"] as $arr_key => $arr_val){

            if($fields_count == 0){
                $fields = $arr_val["fname"];
            }else{
                $fields .= ",".$arr_val["fname"];
            }

            if($arr_val["fname"] == $_POST['search_hidden_dd']){
                $search_dd_field = $_POST["search_hidden_dd"];
                $_POST["search_hidden_dd"] = $arr_val["fheader"];
            }
            $fields_count++;
        }
        $fields.=",recid";

        //looping through to get the header
        $xfields_heaeder_counter = 0;
        $arr_header = array();
        foreach($_POST["fields"] as $fields_arr => $fields_arr_val){
            
            $arr_header[count($arr_header)] = $fields_arr_val["fheader"];
            $xfields_heaeder_counter++;
        }

        for($i=0;$i<count($arr_header);$i++)
        {
            $xchunk3.=$arr_header[$i];
            $xchunk3.=$delimeter;
        }

        $xchunk3.=$xline;

        $xfilter = '';
        $xorder = '';
        $search_text_input = $_POST['search_hidden_value'];
    
        if((isset($search_dd_field) && !empty($search_dd_field)) && isset($_POST['search_hidden_value']) &&
           (!isset($_POST["first_load_hidden"]) || $_POST["first_load_hidden"] !== "Y")){
    
            $xorder = "ORDER BY ".$search_dd_field." ASC";
    
            if($_POST["search_hidden_type"] == "date"){
                $search_text_input  = (empty($search_text_input))   ? NULL :  date("Y-m-d", strtotime($search_text_input));
    
                if($search_text_input == NULL){
                    $xfilter = "";
                }else{
                    $xfilter = "AND ".$search_dd_field." LIKE '%".$search_text_input."%'";
                }
            }   
            else if($_POST["search_hidden_type"] == "dropdown_custom"){
    
                $fields_count_search = 0;
                foreach($_POST["fields"] as $key_select => $value_select_search){
            
                    $value_select_name =$value_select_search["fname"];
            
                        if($fields_count_search == 0){
                            $fields_search_cus_dd = $_POST["tablename_hidden"].".".$value_select_name;
                        }else{
                            $fields_search_cus_dd .= ",".$_POST["tablename_hidden"].".".$value_select_name;
                        }
            
                    $fields_count_search++;
                }
                $fields_search_cus_dd.=",".$_POST["tablename_hidden"]."."."recid";
            
                foreach($_POST["fields"] as $key_select => $fields_arr_val){
            
                    if($fields_arr_val["ftype"] == "dropdown_custom"){
            
                        $dropdown_field_name        = $fields_arr_val["f_dd_fieldname"]; 
                        $dropdown_field_name_value  = (isset($fields_arr_val["f_dd_fieldname_val"]))?($fields_arr_val["f_dd_fieldname_val"]) : ""; 
                        $dropdown_tablename         = $fields_arr_val["f_dd_tablename"];
                
                        if($dropdown_field_name_value !== ""){
                            $select_db_main = "SELECT ".$fields_search_cus_dd." FROM ".$_POST["tablename_hidden"]." INNER JOIN ".$dropdown_tablename
                            ." ON ".$_POST["tablename_hidden"].'.'.$dropdown_field_name.'  = '.$dropdown_tablename.'.'.$dropdown_field_name."
                                WHERE ".$dropdown_field_name_value." LIKE '%".$search_text_input."%'"." ORDER BY ".$_POST["tablename_hidden"].".".$dropdown_field_name;
                        }else{
                            $select_db_main = "SELECT ".$fields." FROM ".$_POST['tablename_hidden']." WHERE ".$dropdown_field_name." LIKE '%".$search_text_input."%' ORDER BY ".$dropdown_field_name." ASC";
                        }
                
                        
                    }
            
                }
                
            
            }
            else{
                $_POST['search_hidden_value']  = (empty($_POST['search_hidden_value']))   ? NULL :  $_POST['search_hidden_value'];

                if($_POST['search_hidden_value'] == NULL){
                    $xfilter = "";
                }else{
                    $xfilter = "AND ".$search_dd_field." LIKE '%".$_POST['search_hidden_value']."%'";
                }
            }
        }else{
            $xorder = "ORDER BY ".$_POST["table_order_field"]." ".$_POST["table_order_type"];
        }
    
        if(!isset($_POST["search_hidden_type"]) || $_POST["search_hidden_type"] !== "dropdown_custom"){
            $select_db_main="SELECT ".$fields." FROM ".$_POST["tablename_hidden"]." WHERE true " .$xfilter." ".$xorder;
        }else if($_POST["search_hidden_type"] == "dropdown_custom" && (isset($_POST["first_load_hidden"]) && $_POST["first_load_hidden"] == "Y")){

            $fields_count_search = 0;
            foreach($_POST["fields"] as $key_select => $value_select_search){
        
                $value_select_name =$value_select_search["fname"];
        
                    if($fields_count_search == 0){
                        $fields_search_cus_dd = $_POST["tablename_hidden"].".".$value_select_name;
                    }else{
                        $fields_search_cus_dd .= ",".$_POST["tablename_hidden"].".".$value_select_name;
                    }
        
                $fields_count_search++;
            }
            $fields_search_cus_dd.=",".$_POST["tablename_hidden"]."."."recid";
         
            $dropdown_field_name        = $_POST["dd_field_name_search"]; 
            $dropdown_field_name_value  = (isset($_POST["dd_field_name_value_search"]))?($_POST["dd_field_name_value_search"]) : ""; 
            $dropdown_tablename         = $_POST["dd_tablename_search"];
    
            if($dropdown_field_name_value !== ""){
    
                $select_db_main = "SELECT ".$fields_search_cus_dd." FROM ".$_POST["tablename_hidden"]." INNER JOIN ".$dropdown_tablename
                ." ON ".$_POST["tablename_hidden"].'.'.$dropdown_field_name.'  = '.$dropdown_tablename.'.'.$dropdown_field_name."
                    WHERE ".$dropdown_field_name_value." LIKE '%".$search_text_input."%'"." ORDER BY ".$_POST["tablename_hidden"].".".$_POST["table_order_field"]. " ".$_POST["table_order_type"];
            }else{
                $select_db_main = "SELECT ".$fields." FROM ".$_POST['tablename_hidden']." WHERE ".$dropdown_field_name." LIKE '%".$search_text_input."%' ORDER BY ".$_POST["table_order_field"]." ".$_POST["table_order_type"];
            }  
    
            
        }        

        $stmt	= $link->prepare($select_db_main);
        $stmt->execute();
        while($rs_main=$stmt->fetch())
        {

            foreach($_POST["fields"] as $fields_arr => $fields_arr_val){

                if($fields_arr_val["ftype"] == "date"){
                    if(!empty($rs_main[$fields_arr_val["fname"]]) && $rs_main[$fields_arr_val["fname"]] !== NULL &&  $rs_main[$fields_arr_val["fname"]]!=="1970-01-01"){
                        $rs_main[$fields_arr_val["fname"]] = date("m-d-Y",strtotime($rs_main[$fields_arr_val["fname"]]));
                        $rs_main[$fields_arr_val["fname"]] = str_replace('-','/',$rs_main[$fields_arr_val["fname"]]);
                    }else{
                        $rs_main[$fields_arr_val["fname"]] = NULL;
                    }
                    
                }
    
                else if($fields_arr_val["ftype"] == "number"){
                    $rs_main[$fields_arr_val["fname"]] = number_format($rs_main[$fields_arr_val["fname"]],2);
                    $xalign = 'right';
    
                }
    
                else if($fields_arr_val["ftype"] == "checkbox"){
                    if($rs_main[$fields_arr_val["fname"]] == 1 || $rs_main[$fields_arr_val["fname"]] == "1"){
                        $rs_main[$fields_arr_val["fname"]] = "checked";
                    }else{
                        $rs_main[$fields_arr_val["fname"]] = "unchecked";
                    }
                }
    
                else if($fields_arr_val["ftype"] == "dropdown_custom"){
    
                    $dropdown_field_name        = $fields_arr_val["f_dd_fieldname"]; 
                    $dropdown_field_name_value  = (isset($fields_arr_val["f_dd_fieldname_val"]))?($fields_arr_val["f_dd_fieldname_val"]) : ""; 
                    $dropdown_tablename         = $fields_arr_val["f_dd_tablename"];
    
                    if($dropdown_field_name_value !== ""){
                        $select_db_dd="SELECT ".$dropdown_field_name_value.", ".$dropdown_field_name." FROM ".$dropdown_tablename." where ".$dropdown_field_name."='".$rs_main[$dropdown_field_name]."'";
                    }else{
                        $select_db_dd="SELECT ".$dropdown_field_name." FROM ".$_POST["tablename_hidden"]." where ".$dropdown_field_name."='".$rs_main[$dropdown_field_name]."'";
                    }
    
                    $stmt_dd	= $link->prepare($select_db_dd);
                    $stmt_dd->execute();
                    $rs_main[$fields_arr_val["fname"]] = $select_db_dd;
                    while($rs_dd = $stmt_dd->fetch()){
    
                        if($dropdown_field_name_value !== ""){
                            $rs_main[$fields_arr_val["fname"]] = $rs_dd[$dropdown_field_name_value];
                        }
                        else{
                            $rs_main[$fields_arr_val["fname"]] = $rs_dd[$dropdown_field_name];
                        }
                    }
                }


                $xchunk3.=$rs_main[$fields_arr_val["fname"]];
                $xchunk3.=$delimeter;

            }
            $xchunk3.=$xline;

        }
        
        $_POST["main_header_hidden"] = str_replace(' ', '', $_POST["main_header_hidden"]);
        $xfilename = $_POST["main_header_hidden"].".txt";
        //file_put_contents($xfilename, "") or die("Could not clear file!");
        $xfhndl = fopen($xfilename, 'w+');

        //if (fwrite($xfhndl, $xchunk3) == FALSE) {
        //    echo "Cannot write to file ($xfilename)";
        //    exit;
        //} 



        if ($xfhndl === false) {
            echo "Cannot write to file3 ($xfilename)";
            exit;
	    }   
	    if(fwrite($xfhndl, $xchunk3)){
	 	    //echo $xfileName;
	    }

        fclose($xfhndl);

        // Write $somecontent to our opened file.
           
    
	    

	header("Content-Disposition: attachment; filename=$xfilename");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/force-download");
	header("Content-Type: application/download");
	header("Content-Transfer-encoding: binary");
	header("Pragma:no-cache");
	header("Expires:0");
	readfile($xfilename);
    
    unlink($xfilename);
    ob_end_flush();
?>
