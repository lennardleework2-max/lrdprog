<?php

    function PDO_InsertRecord(&$link_id,$tablename,$record_parameters,$debug=false,$ignore_errors=false){

        if(!is_array($record_parameters)){

            if ( $debug ){
                echo "No supplied parameters.<br>";
            }

            return false;
        }
        
        $arr_qry_params = array();
        $i=0;
        foreach($record_parameters as $field_value){
            if(get_magic_quotes_gpc()){
                $arr_qry_params[$i++]=stripslashes($field_value);                
            }else{
                $arr_qry_params[$i++]=$field_value;                           
            }
        }        
        
        $fields = '';
        $values = '';
        
        $array_count=count($record_parameters);
        $count=1;
        foreach($record_parameters as $field => $value){
            if ($count==$array_count)
            {
                $fields.="`$field`";
                $values.='?';

            }
            else
            {
                $fields.="`$field`,";
                $values.='?,';
            }
            $count++;
        }
        
        $xlen = strlen($fields)-1;
        //$fields[$xlen] = '';
        $fields = trim($fields);        
        
        $xlen = strlen($values)-1;
        //$values[$xlen] = '';
        $values = trim($values);      
        


        $xqry = "INSERT INTO $tablename ($fields) VALUES($values);";
        //echo "query:".$xqry;

        $stmt = $link_id->prepare($xqry);
        $stmt->execute($arr_qry_params);
        if ( !$ignore_errors )
        {
            //check_qry($stmt);
        }
		
        if ( $debug ){
            echo '<hr><br>Statement : <br>';
            var_dump($stmt);
            echo '<br>Error Information<br>';
            var_dump($stmt->errorInfo());
            echo '<br>Parameters<br>';
            var_dump($arr_qry_params);
			
        }

        return true;

    }
    
    function PDO_UpdateRecord(&$link_id,$tablename,$record_parameters,$condition=' true ',$condition_parameters,$debug=false){
        
        $args = '';
        $i=0;
        
        $update_parameters = array();
        $array_count=count($record_parameters);
        $count=1;
        foreach($record_parameters as $field => $value){
        
            if(get_magic_quotes_gpc()){
                $update_parameters[$i++]=stripslashes($value);
            }else{
                $update_parameters[$i++]=$value;
            }

            if ($count==$array_count){
                $args.="`$field`=?";
            }
            else{
                $args.="`$field`=?,";    
            }
            
            $count++;
        }
        
        $xlen = strlen($args)-1;
        //$args[$xlen] = '';
        $args = trim($args);        

        if(is_array($condition_parameters)){
            foreach($condition_parameters as $field => $value){
            
                if(get_magic_quotes_gpc()){
                    $update_parameters[$i++]=stripslashes($value);
                }else{
                    $update_parameters[$i++]=$value;
                }
            }
        }        
       
        $xqry = "UPDATE $tablename SET $args WHERE $condition;"; 
        $stmt = $link_id->prepare($xqry);
        $stmt->execute($update_parameters);
		//check_qry($stmt);

        if ( $debug ){
            echo '<hr><br>Statement : <br>';
            var_dump($stmt);
            echo '<br>Error Information<br>';
            var_dump($stmt->errorInfo());
            echo '<br>Parameters<br>';
            var_dump($update_parameters);
        }

        return true;
        
    }

    function PDO_Refreshid($link_id,$par_tablename)
    {
        //global $link_id;
        $xcount=0;

        $xqry = "SELECT recid FROM `$par_tablename` ORDER BY recid";
        $stmt = $link_id->prepare($xqry);
        $stmt->execute();
        
        while( $rs = $stmt->fetch() )
        {
            $xcount=$xcount+1;

            $xqry = "UPDATE `$par_tablename` SET recid=? WHERE recid=? ";
            $stmt_upt = $link_id->prepare( $xqry );

            $arr_qry_params = array( $xcount , $rs['recid'] );

            $stmt_upt->execute( $arr_qry_params );
        }

        $xcount++;

	    $xqry = "ALTER TABLE `$par_tablename` AUTO_INCREMENT = $xcount";
	        $stmt_upt = $link_id->prepare( $xqry );
	        $stmt_upt->execute();
	        
	    }
	    function useractivitylog_get_session_username()
	    {
	        return isset($_SESSION['userdesc']) ? trim((string)$_SESSION['userdesc']) : '';
	    }
	    function useractivitylog_get_session_fullname($link_id)
	    {
	        if(!isset($_SESSION['recid']) || empty($_SESSION['recid'])){
	            return '';
	        }
	
	        $select_user = "SELECT full_name FROM users WHERE recid = ? LIMIT 1";
	        $stmt_user = $link_id->prepare($select_user);
	        $stmt_user->execute(array($_SESSION['recid']));
	        $rs_user = $stmt_user->fetch();
	
	        return $rs_user ? trim((string)$rs_user['full_name']) : '';
	    }
	    function useractivitylog_escape_value($value)
	    {
	        return str_replace("'", "\\'", trim((string)$value));
	    }
	    function useractivitylog_lookup_value($link_id, $table_name, $key_field, $value_field, $key_value)
	    {
	        $key_value = trim((string)$key_value);
	        if($key_value === ''){
	            return '';
	        }
	
	        $select_lookup = "SELECT `$value_field` AS display_value FROM `$table_name` WHERE `$key_field` = ? LIMIT 1";
	        $stmt_lookup = $link_id->prepare($select_lookup);
	        $stmt_lookup->execute(array($key_value));
	        $rs_lookup = $stmt_lookup->fetch();
	
	        return $rs_lookup ? trim((string)$rs_lookup['display_value']) : '';
	    }
	    function useractivitylog_format_value($value, $type = 'text')
	    {
	        if($value === null){
	            return '';
	        }
	
	        $value = trim((string)$value);
	        if($value === ''){
	            return '';
	        }
	
	        if($type === 'date'){
	            $time_value = strtotime($value);
	            return ($time_value === false) ? $value : date('Y-m-d', $time_value);
	        }
	
	        if($type === 'number'){
	            $numeric_value = str_replace(',', '', $value);
	            if(!is_numeric($numeric_value)){
	                return $value;
	            }
	
	            $formatted_value = number_format((float)$numeric_value, 2, '.', '');
	            $formatted_value = rtrim(rtrim($formatted_value, '0'), '.');
	
	            return ($formatted_value === '-0') ? '0' : $formatted_value;
	        }
	
	        return $value;
	    }
	    function useractivitylog_field_display_value($link_id, $value, $field_config)
	    {
	        $type = isset($field_config['type']) ? $field_config['type'] : 'text';
	        $display_value = useractivitylog_format_value($value, $type);
	
	        if(isset($field_config['lookup_table'], $field_config['lookup_key'], $field_config['lookup_value'])){
	            $lookup_value = useractivitylog_lookup_value(
	                $link_id,
	                $field_config['lookup_table'],
	                $field_config['lookup_key'],
	                $field_config['lookup_value'],
	                $value
	            );

	            // Always use lookup value when lookup is configured
	            // This ensures we never show internal codes (like BID-0003) in remarks
	            // If the name field is blank/null/not found, show blank instead of the code
	            $display_value = $lookup_value;
	        }
	
	        if(isset($field_config['map'])){
	            $map_key = trim((string)$value);
	            if(array_key_exists($map_key, $field_config['map'])){
	                $display_value = trim((string)$field_config['map'][$map_key]);
	            }
	        }
	
	        return useractivitylog_escape_value($display_value);
	    }
	    function useractivitylog_header_field_config($trncde)
	    {
	        $trncde = strtoupper(trim((string)$trncde));
	
	        $configs = array(
	            'POR' => array(
	                'orderby' => array('label' => 'ordered by'),
	                'shipto' => array('label' => 'ship to'),
	                'suppcde' => array('label' => 'supplier', 'lookup_table' => 'supplierfile', 'lookup_key' => 'suppcde', 'lookup_value' => 'suppdsc'),
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'paydate' => array('label' => 'payment date', 'type' => 'date'),
	                'paydetails' => array('label' => 'payment details'),
	                'ordernum' => array('label' => 'order number'),
	                'remarks' => array('label' => 'remarks'),
	                'po_qr_id' => array('label' => 'purchase order ID')
	            ),
	            'PUR' => array(
	                'orderby' => array('label' => 'ordered by'),
	                'shipto' => array('label' => 'ship to'),
	                'suppcde' => array('label' => 'supplier', 'lookup_table' => 'supplierfile', 'lookup_key' => 'suppcde', 'lookup_value' => 'suppdsc'),
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'paydate' => array('label' => 'payment date', 'type' => 'date'),
	                'paydetails' => array('label' => 'payment details'),
	                'ordernum' => array('label' => 'order number'),
	                'remarks' => array('label' => 'remarks'),
	                'po_qr_id' => array('label' => 'purchase order ID')
	            ),
	            'SOR' => array(
	                'buyer_id' => array('label' => 'buyer', 'lookup_table' => 'mf_buyers', 'lookup_key' => 'buyer_id', 'lookup_value' => 'buyer_name'),
	                'ordernum' => array('label' => 'order number'),
	                'shipto' => array('label' => 'ship to'),
	                'cuscde' => array('label' => 'customer', 'lookup_table' => 'customerfile', 'lookup_key' => 'cuscde', 'lookup_value' => 'cusdsc'),
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'remarks' => array('label' => 'remarks'),
	                'order_status' => array('label' => 'order status')
	            ),
	            'SAL' => array(
	                'buyer_id' => array('label' => 'buyer', 'lookup_table' => 'mf_buyers', 'lookup_key' => 'buyer_id', 'lookup_value' => 'buyer_name'),
	                'salesman_id' => array('label' => 'salesman', 'lookup_table' => 'mf_salesman', 'lookup_key' => 'salesman_id', 'lookup_value' => 'salesman_name'),
	                'route_id' => array('label' => 'route', 'lookup_table' => 'mf_routes', 'lookup_key' => 'route_id', 'lookup_value' => 'route_desc'),
	                'ship_status' => array('label' => 'ship status'),
	                'shipto' => array('label' => 'ship to'),
	                'cuscde' => array('label' => 'customer', 'lookup_table' => 'customerfile', 'lookup_key' => 'cuscde', 'lookup_value' => 'cusdsc'),
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'paydate' => array('label' => 'payment date', 'type' => 'date'),
	                'paydate_salesman' => array('label' => 'salesman payment date', 'type' => 'date'),
	                'com_pay' => array('label' => 'commission pay', 'type' => 'number'),
	                'paydetails' => array('label' => 'payment details'),
	                'remarks' => array('label' => 'remarks'),
	                'ordernum' => array('label' => 'order number')
	            ),
	            'ADJ' => array(
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'ordernum' => array('label' => 'reference number'),
	                'remarks' => array('label' => 'remarks')
	            ),
	            'SRT' => array(
	                'buyer_id' => array('label' => 'buyer', 'lookup_table' => 'mf_buyers', 'lookup_key' => 'buyer_id', 'lookup_value' => 'buyer_name'),
	                'salesman_id' => array('label' => 'salesman', 'lookup_table' => 'mf_salesman', 'lookup_key' => 'salesman_id', 'lookup_value' => 'salesman_name'),
	                'shipto' => array('label' => 'ship to'),
	                'cuscde' => array('label' => 'customer', 'lookup_table' => 'customerfile', 'lookup_key' => 'cuscde', 'lookup_value' => 'cusdsc'),
	                'trntot' => array('label' => 'total amount', 'type' => 'number'),
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'paydate' => array('label' => 'payment date', 'type' => 'date'),
	                'paydetails' => array('label' => 'payment details'),
	                'ordernum' => array('label' => 'order number'),
	                'remarks' => array('label' => 'remarks')
	            ),
	            'STT' => array(
	                'trndte' => array('label' => 'transaction date', 'type' => 'date'),
	                'ordernum' => array('label' => 'reference number'),
	                'remarks' => array('label' => 'remarks')
	            )
	        );
	
	        return isset($configs[$trncde]) ? $configs[$trncde] : array();
	    }
	    function useractivitylog_build_insert_docnum_remark($docnum)
	    {
	        return "Inserted docnum: '" . useractivitylog_escape_value($docnum) . "'";
	    }
	    function useractivitylog_build_delete_docnum_remark($docnum)
	    {
	        return "Deleted docnum: '" . useractivitylog_escape_value($docnum) . "'";
	    }
	    function useractivitylog_build_header_edit_remark($link_id, $trncde, $docnum, $old_row, $new_row)
	    {
	        $field_configs = useractivitylog_header_field_config($trncde);
	        $change_parts = array();
	
	        foreach($field_configs as $field_name => $field_config){
	            $label = isset($field_config['label']) ? $field_config['label'] : $field_name;
	
	            $old_value = array_key_exists($field_name, $old_row) ? $old_row[$field_name] : null;
	            $new_value = array_key_exists($field_name, $new_row) ? $new_row[$field_name] : $old_value;
	
	            $old_display = useractivitylog_field_display_value($link_id, $old_value, $field_config);
	            $new_display = useractivitylog_field_display_value($link_id, $new_value, $field_config);
	
	            if($old_display !== $new_display){
	                $change_parts[] = $label . " from: '" . $old_display . "' to '" . $new_display . "'";
	            }
	        }
	
	        if(empty($change_parts)){
	            return '';
	        }
	
	        return "Edited docnum: '" . useractivitylog_escape_value($docnum) . "', " . implode(', ', $change_parts);
	    }
	     function PDO_UserActivityLog($link_id, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum = '', $upload_filename = '')
	    {
		    	// Keep only last 100 records
		    	$maxcount = 100;
            $log_datetime = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $log_datetime_full = $log_datetime->format("Y-m-d H:i:s");

	    	$xarr_rec = array();
	        $xarr_rec['usrcde'] = $xusrcde;
	    	$xarr_rec['usrname'] = $xusrcde;
	        $xarr_rec['usrdte'] = $log_datetime_full;
	        $xarr_rec['usrtim'] = $log_datetime->format("H:i:s");
	        $xarr_rec['trndte'] = $log_datetime_full;
	        $xarr_rec['module'] = $xprog_module;
        $xarr_rec['activity'] = $xactivity;
    	$xarr_rec['empcode'] = $xusrcde;
    	$xarr_rec['fullname'] = $xfullname;
        $xarr_rec['remarks'] = $xremarks;
        $xarr_rec['linenum'] = $linenum;
        $xarr_rec['parameter'] = $parameter;
        $xarr_rec['trncde'] = $trncde;
        $xarr_rec['trndsc'] = $trndsc;
    	$xarr_rec['compname'] = $compname;
        $xarr_rec['usrnam'] = $xusrnme;
        $xarr_rec['docnum'] = $docnum;
        $xarr_rec['upload_filename'] = $upload_filename;
    	PDO_InsertRecord($link_id,'useractivitylogfile',$xarr_rec, false);

    	$qry_chkcount = "SELECT count(*) as xcount FROM useractivitylogfile";
    	$stmt_chkcount = $link_id->prepare($qry_chkcount);
    	$stmt_chkcount->execute();
    	$rs_chkcount = $stmt_chkcount->fetch();
	    	if($rs_chkcount['xcount']>$maxcount)
	    	{
	    	    $xecess = $rs_chkcount['xcount'] - $maxcount;
	    	    $xqry_del = "DELETE FROM useractivitylogfile ORDER BY recid ASC LIMIT $xecess";
	    	    $xstmt_del = $link_id->prepare($xqry_del);
	    	    $xstmt_del->execute();
	    	}
	    }

?>
