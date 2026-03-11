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
     function PDO_UserActivityLog($link_id, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum = '', $upload_filename = '')
    {
    	// Keep only last 100 records
    	$maxcount = 100;

    	$xarr_rec = array();
        $xarr_rec['usrcde'] = $xusrcde;
    	$xarr_rec['usrname'] = $xusrcde;
        $xarr_rec['usrdte'] = date("Y-m-d H:i:s");
        $xarr_rec['usrtim'] = date("H:i:s");
        $xarr_rec['trndte'] = $xtrndte;
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
    	    PDO_Refreshid($link_id,"useractivitylogfile");
    	}
    }

?>