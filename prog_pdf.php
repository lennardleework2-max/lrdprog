<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
	require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    // Log export activity
    $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $select_db_session_user='SELECT * FROM users where recid=?';
        $stmt_session_user = $link->prepare($select_db_session_user);
        $stmt_session_user->execute(array($_SESSION['recid']));
        $rs_session_user = $stmt_session_user->fetch();
        if($rs_session_user){
            $username_full_name = $rs_session_user["full_name"];
        }
    }
    $xtrndte_log = date("Y-m-d H:i:s");
    $xprog_module_log = isset($_POST["main_header_hidden"]) ? strtoupper($_POST["main_header_hidden"]) : '';
    $xactivity_log = ($_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".(($_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from ".$_POST["main_header_hidden"];
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $xreport_title = "List of items";
		

    if ($_POST['txt_output_type']=='tab')
	{
		$pdf = new tab_ezpdf('Letter','landscape');
	}
	else
	{
		$pdf = new Cezpdf('Letter','landscape');

	}

    $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 580;
    $xleft = 25;

    /**header**/
    
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

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>".$_POST["main_header_hidden"]."</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;


        if((isset($_POST["search_hidden_value"]) && !empty($_POST["search_hidden_value"])) && isset($_POST['search_hidden_dd'])){
            $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
            $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
            $xtop   -= 15;
        }
        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 18;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        

        $xfields_heaeder_counter = 0;
        foreach($_POST["fields"] as $fields_arr => $fields_arr_val){
            if($xfields_heaeder_counter == 0){
                $pdf->ezPlaceData($xleft,$xtop,"<b>".trim_str($fields_arr_val["fheader"],100,10)."</b>",10,'left');
            }else{
                $pdf->ezPlaceData($xleft+=115,$xtop,"<b>".trim_str($fields_arr_val["fheader"],100,10)."</b>",10,'left');
            }
            $xfields_heaeder_counter++;
        }
        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

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


    //$pdf->ezPlaceData($xleft, $xtop-100, $select_db_main, 7, 'left' );
   
    $stmt_main	= $link->prepare($select_db_main);
    $stmt_main->execute();
    while($rs_main = $stmt_main->fetch()){    

        $fields_count_data = 0;

        foreach($_POST["fields"] as $fields_arr => $fields_arr_val){

            $xalign = 'left';
            $x_add_space = 0;

            if($fields_arr_val["ftype"] == "date"){
                if(!empty($rs_main[$fields_arr_val["fname"]]) && $rs_main[$fields_arr_val["fname"]] !== NULL &&  $rs_main[$fields_arr_val["fname"]]!=="1970-01-01"){
                    $rs_main[$fields_arr_val["fname"]] = date("m-d-Y",strtotime($rs_main[$fields_arr_val["fname"]]));
                    $rs_main[$fields_arr_val["fname"]] = str_replace('-','/',$rs_main[$fields_arr_val["fname"]]);
                }else{
                    $rs_main[$fields_arr_val["fname"]] = NULL;
                }
                $xalign = 'left';
                
            }

            else if(!empty($fields_arr_val["fdecimal"])){
                $rs_main[$fields_arr_val["fname"]] = number_format($rs_main[$fields_arr_val["fname"]],$fields_arr_val["fdecimal"]);
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


            if($fields_count !== 1){
                $xmaxwidth = 115;
            }else if($fields_count == 1){
                $xmaxwidth = 750;
            }


            
            if($fields_count_data == 0){
                $pdf->ezPlaceData(dynamic_width($fields_arr_val["fheader"],25,$x_add_space,$xalign),$xtop,trim_str($rs_main[$fields_arr_val["fname"]],$xmaxwidth, 9),9,$xalign);
            }else{
                $pdf->ezPlaceData(dynamic_width($fields_arr_val["fheader"],$xleft+=115,$x_add_space,$xalign),$xtop,trim_str($rs_main[$fields_arr_val["fname"]],$xmaxwidth,9),9,$xalign);
            }

            $fields_count_data++;
            if($fields_count_data == $fields_count){
                $xleft = 25;
            }
        }
        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 565;
        }
        
    }

   
    $pdf->line(25, $xtop-10, 770, $xtop-10); 
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    function trim_str($string,$max_wid,$fsize)
    {   
        global $pdf;
        if(  get_class($pdf) == 'tab_ezpdf')
        {
            return $string;
        }
        $xarr_str = str_split($string);
        $max_wid -= 5;
        $xxstr = "";
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $max_wid)
            {   
                $xcut = true;
                break;
            }
            $xxstr = $xxstr.$value;
        }
        if($xcut)
        {   
            $xxstr = $xxstr.'...';
        }
        return $xxstr;
    }

    //returns dynamic width
    function dynamic_width($xstr_chk, $xleft , $spaces ,$xalign_chk){

        if($xalign_chk == "right"){
            $str_count = strlen($xstr_chk);
            $xleft_new = $xleft + ($str_count * 4.2) - ($spaces * 4.2);
            return $xleft_new+5;
        }else if($xalign_chk == "left"){

            $xleft_new = $xleft + ($spaces * 4.2);
            return $xleft_new;
        }

        else if($xalign_chk == "cus_left"){
            $str_count = strlen($xstr_chk);
            $xleft_new = $xleft + ($str_count * 4.2) + ($spaces * 4.2);
            return $xleft_new;
        }
        


    }


?>