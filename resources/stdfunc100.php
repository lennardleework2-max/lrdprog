<?php
	
	$default_sort_order = 'ASC';

	$MYSQL_ERRNO = '';
	$MYSQL_ERROR = '';

	//$dbname=$_SESSION['dbname'];
	
	$validate_field_number=array();
	$validate_field_date=array();
	$validate_field_presence=array();
	$validate_message=array();
	$validate_result="true";
	
	function db_connect($dbname='') 
	{		
	   global $dbhost, $dbusername, $dbuserpassword, $default_dbname;
	   global $MYSQL_ERRNO, $MYSQL_ERROR;
	   
	    //require_once('./adodb/adodb.inc.php'); # load code common to ADOdb 
	   
		$db_id = ADONewConnection('mysql'); # create a connection 
		$db_id->PConnect($dbhost,$dbusername,$dbuserpassword,$dbname); # connect to mysql, northwind DSN 
	   
		if(!$db_id)
		{
			echo "Error Connecting $dbhost";
			return 0;
		}
		else
		{ 
            return $db_id;
        }
	}
	

    function ListOption_pdo($db_id,$optTableName,$optselected,$blank=true,$xfilter='')
    { 
        $local_query = "SELECT * FROM ".$optTableName." ".$xfilter;
        $stmt = $db_id->prepare($local_query);
        $stmt->execute();
    
        if($stmt->rowCount()!=0 and $blank==true)
        {      
            echo "<option>";
            echo "</option>";
        }
        
        while($rs = $stmt->fetch()) 
        {
            $xvalue=$rs[$optField];
            
            if (trim(strtoupper($xvalue))==trim(strtoupper($optselected)))
                {
                    //msgbox($xvalue);
                    $sel="selected";
                }
            else    
                {$sel="";}                
            echo "<option $sel value='".$rs[$optcode]."'>";
            echo $rs[$optDesc];        
            echo "</option>";        
        }    
    }

    function display_date($date)
    {
        $date=trim($date);
        $xlen=strlen($date);
        if ($xlen==0)
        {return "";}

        $type=1; // 1 is year, 2 is month, 3 is day;
        $mchar="";
        $dchar="";
        $ychar="";
        $m=0;
        $d=0;
        $y=0;
        for ($i=0;$i<$xlen;$i++)
        {
            $char=substr($date,$i,1);
            if ($char=="/" or $char=="-")
            {
                $type=$type+1;
            } 
            else
            {
                switch ($type)
                {
                case 1:
                    $ychar=$ychar.$char;
                    break;
                case 2:
                    $mchar=$mchar.$char;
                    break;
                case 3:
                    $dchar=$dchar.$char;
                    break;
                }
                
            }
        }
        //msgbox("m$mchar d$dchar y$ychar");
        $m=(integer) $mchar;
        $d=(integer) $dchar;
        $y=(integer) $ychar;
        if ($y==0 or $m==0 or $d==0)
        {
            return "";
        }
        else
        {
            return "$m-$d-$y";
        }
    }   

    function UpdateAllTables($xmasfile , $xfieldcde , $xfielddsc , $xUpdCde , $xUpdDsc , $xoldcde , $xnewcde , $xolddsc , $xnewdsc )
    {
        $xdbname=$_SESSION['dbname'];
        global $db_id;

        $xpfile = trim($xpfile);
        $xlen = strlen($xpfile);
        $rsList=$db_id->Execute("Show Tables");    

        if ($xUpdCde and $xfieldcde) 
        {
            $xfieldcde_lower = strtolower($xfieldcde);
            $rsList = $db_id->Execute(
                "select table_name, column_name from information_schema.columns " .
                "where table_schema='$xdbname' and lower(column_name) = '$xfieldcde_lower'");
            while (!$rsList->EOF) 
            {
                $xfileName = $rsList->fields[0];
                $xfieldcde2 = $rsList->fields[1];
                $query="update $xfileName set $xfieldcde2='$xnewcde' where $xfieldcde2='$xoldcde'";            
                $rs=$db_id->Execute($query);
                if(!$rs )     {error_message(sql_error());} 
                $rsList->MoveNext();
            }
        }
        
        if ($xUpdDsc and $xfielddsc) 
        {
            $xfielddsc_lower = strtolower($xfielddsc);
            $rsList = $db_id->Execute(
                "select table_name, column_name from information_schema.columns " .
                "where table_schema='$xdbname' and lower(column_name) = '$xfielddsc_lower'");
            while (!$rsList->EOF) 
            {
                $xfileName = $rsList->fields[0];
                $xfielddsc2 = $rsList->fields[1];
                $query="update $xfileName set $xfielddsc2='$xnewcde' where $xfielddsc2='$xoldcde'";                    
                $rs=$db_id->Execute($query);
                if(!$rs )     {error_message(sql_error());} 
                $rsList->MoveNext();
            }
        }
    }

    function UserActivityLog($Activity,$Remarks,$WebPage)
    {
        global $link_id,$xg_appkey;
        
        require_once('lx.pdodb.php');
        $arr_record = array();
        $arr_record['usrcde'] = $_SESSION[$xg_appkey]['usrcde'];
        $arr_record['usrdte'] =date('Y-m-d');
        $arr_record['usrtim'] =date('H:i:s');
        $arr_record['activity'] =$Activity;
        $arr_record['remarks'] = $Remarks;
        $arr_record['webpage'] = $WebPage;
        PDO_InsertRecord($link_id,'useractivitylogfile',$arr_record);

        
    }

    function LNexts($xp_string )
    {
        $xp_len = strlen($xp_string);
        $xp_result = "";
        $xp_chr = "";
        $xp_next = true;
        $xp_count = $xp_len-1;
        $xp_asc = 0;

        while ($xp_count >= 0 )
        {
        if ($xp_next==true)
        {
            $xp_chr = substr($xp_string, $xp_count, 1);
            $xp_asc = ord($xp_chr);

            switch ($xp_asc)
            {
                case ($xp_asc>=48 && $xp_asc<= 57):   
                if ($xp_chr == "9")
                {
                    $xp_next = true;
                }
                else
                {
                    $xp_next = false;
                }
                
                $x=$xp_chr;
                settype($x, "float");
                $x=$x+1;
                settype($x,"string");
                $xp_chr = substr($x,-1, 1);

                break;
                case ($xp_asc>=65 && $xp_asc<= 90):         //&& "A" - "Z"
                if ($xp_asc == 90)
                {
                    $xp_chr =chr(97);
                }
                else
                {
                    $xp_chr =chr($xp_asc + 1);
                }
                $xp_next = false;
                break;
                case ($xp_asc>=97 && $xp_asc<= 122  ) :           //&& "a" - "z"
                if ($xp_asc == 122)
                {
                    $xp_next =  true;
                }
                else
                {
                    $xp_next =  false;
                }
                
                if($xp_next==true)
                {
                    $xp_chr = chr(65);
                }
                else
                {
                    $xp_chr =chr($xp_asc + 1);
                }
                break;
            }

            $xp_result = $xp_chr . $xp_result;

        }
        else
        {
            $xp_result = substr($xp_string,0, $xp_count+1) . $xp_result;
            break ;
        }

            $xp_count = $xp_count - 1;
        }

        return $xp_result;

    }

    //custom str cut used in pager :lennard
    function remove_xfields($xstr,$xremove){
    
        $str = str_replace("_displayData", "",$xstr);
        $str = str_replace("fields", "",$str);
        $str = str_replace($xremove, "",$str);
        $str = str_replace("[", "",$str);
        $str = str_replace("]", "",$str);

        return $str;
    }

    // Cache for unit costs to avoid repeated queries
    $GLOBALS['unitcost_cache'] = array();

    function get_unitcost($itmcde,$trndte,$sal_recid=null)
    {
        global $link;

        // Create cache key (PHP 5.x compatible)
        $trndte_key = isset($trndte) ? $trndte : 'null';
        $recid_key = isset($sal_recid) ? $sal_recid : 'null';
        $cache_key = $itmcde . '_' . $trndte_key . '_' . $recid_key;

        // Check cache first
        if(isset($GLOBALS['unitcost_cache'][$cache_key])) {
            return $GLOBALS['unitcost_cache'][$cache_key];
        }

        $result = 0;

        // If trndte is provided and not empty, use date-based query
        if(!empty($trndte)) {
            $select_db4="SELECT tranfile2.untprc as tranfile2_untprc FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile2.itmcde=? AND tranfile1.trndte<=?
             AND (tranfile1.trncde='ADJ' OR tranfile1.trncde='PUR') AND tranfile2.stkqty > 0 ORDER BY tranfile1.trndte DESC, tranfile2.recid DESC LIMIT 1";
            $stmt_main4	= $link->prepare($select_db4);
            $stmt_main4->execute(array($itmcde, $trndte));
            $rs_main4 = $stmt_main4->fetch();

            if($rs_main4 && !empty($rs_main4["tranfile2_untprc"])) {
                $result = $rs_main4["tranfile2_untprc"];
                $GLOBALS['unitcost_cache'][$cache_key] = $result;
                return $result;
            }
        }

        // If no date or no result found with date, use recid-based query (fallback)
        if(!empty($sal_recid)) {
            $select_db4="SELECT tranfile2.untprc as tranfile2_untprc FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile2.itmcde=?
             AND (tranfile1.trncde='ADJ' OR tranfile1.trncde='PUR') AND tranfile2.stkqty > 0 AND tranfile2.recid < ? ORDER BY tranfile2.recid DESC LIMIT 1";
            $stmt_main4	= $link->prepare($select_db4);
            $stmt_main4->execute(array($itmcde, $sal_recid));
        } else {
            $select_db4="SELECT tranfile2.untprc as tranfile2_untprc FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile2.itmcde=?
             AND (tranfile1.trncde='ADJ' OR tranfile1.trncde='PUR') AND tranfile2.stkqty > 0 ORDER BY tranfile2.recid DESC LIMIT 1";
            $stmt_main4	= $link->prepare($select_db4);
            $stmt_main4->execute(array($itmcde));
        }

        $rs_main4 = $stmt_main4->fetch();
        if($rs_main4 && isset($rs_main4["tranfile2_untprc"])) {
            $result = $rs_main4["tranfile2_untprc"];
        }

        // Store in cache
        $GLOBALS['unitcost_cache'][$cache_key] = $result;

        return $result;
    }
  
?>
