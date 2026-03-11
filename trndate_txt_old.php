<?php
	ob_start();
	ob_clean();
	
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
	require_once('ezpdfclass/class/class.ezpdf.php');

 	$xline = "\r\n";
    $delimeter = chr(9);
    //$delimeter = 'chr(9)' ;tab 
	$xchunk1 = '';
	$xchunk3 = '';
    ini_set('display_errors', false);
	error_reporting(E_ALL);
    
        $xtable='Tran. Date';
        $xmodule='trandate';

        $arr_header[count($arr_header)]='Doc. Num.';
        $arr_header[count($arr_header)]='Tran. Date';
        $arr_header[count($arr_header)]='Shop Name';
        $arr_header[count($arr_header)]='Ship To';
        $arr_header[count($arr_header)]='Paydate';
        $arr_header[count($arr_header)]='Payment Details';
        $arr_header[count($arr_header)]='Total';

        $arr_field[count($arr_field)]='docnum';
        $arr_field[count($arr_field)]='trndte';
        $arr_field[count($arr_field)]='cusdsc';
        $arr_field[count($arr_field)]='shipto';
        $arr_field[count($arr_field)]='paydate';
        $arr_field[count($arr_field)]='paydetails';
        $arr_field[count($arr_field)]='trntot';

        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='date';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='date';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='decimal';

        
        for($i=0;$i<count($arr_header);$i++)
        {
            $xchunk3.=$arr_header[$i];
            $xchunk3.=$delimeter;
        }

        $xchunk3.=$xline;
        
        $xfilter = '';
        $xorder = '';
    
        if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
        (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){
    
            $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
            $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
    
            $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
        }
    
        else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
            $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
            $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
        }
    
        else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
            $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
            $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
        }
    
        
        if(isset($_POST['cus_from']) && !empty($_POST['cus_from'])){
            $xfilter .= " AND customerfile.cusdsc>='".$_POST['cus_from']."'";
        }
    
        if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
            $xfilter .= " AND customerfile.cusdsc<='".$_POST['cus_to']."'";
        }
            
        $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
        tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid,
        customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
        customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
        tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.trndte ASC, customerfile.cusdsc ASC";
        $stmt_main	= $link->prepare($select_db);
        $stmt_main->execute();
        $grandtotal = 0;
        while($rs_main=$stmt_main->fetch())
        {

            $grandtotal+=$rs_main['tranfile1_trntot'];

            if(isset($rs_main["tranfile1_trndte"]) && !empty($rs_main["tranfile1_trndte"])){
                $rs_main["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main["tranfile1_trndte"]));
                $rs_main["tranfile1_trndte"] = str_replace('-','/',$rs_main["tranfile1_trndte"]);
            }
    
            if(isset($rs_main["tranfile1_paydate"]) && !empty($rs_main["tranfile1_paydate"])){
                $rs_main["tranfile1_paydate"] = date("m-d-Y",strtotime($rs_main["tranfile1_paydate"]));
                $rs_main["tranfile1_paydate"] = str_replace('-','/',$rs_main["tranfile1_paydate"]);
            }

            
            $xchunk3.=$rs_main['tranfile1_docnum'];
            $xchunk3.=$delimeter;
        
            $xchunk3.=$rs_main['tranfile1_trndte'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['customerfile_cusdsc'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['tranfile1_shipto'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['tranfile1_paydate'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['tranfile1_paydetails'];
            $xchunk3.=$delimeter;

            $xchunk3.=number_format($rs_main['tranfile1_trntot'],2);
            $xchunk3.=$delimeter;           

            $xchunk3.=$xline;
        }
        
        $xchunk3.=number_format($grandtotal,2);
        $xfilename ="trn_date.txt";

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
	 	echo $xfileName;
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
