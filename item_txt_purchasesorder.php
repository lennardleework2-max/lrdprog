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
    
        $xtable='Item';
        $xmodule='item';

        $arr_header[count($arr_header)]='Doc. Num.';
        $arr_header[count($arr_header)]='Item';
        $arr_header[count($arr_header)]='Tran. Date';
        $arr_header[count($arr_header)]='Order Num.';
        $arr_header[count($arr_header)]='Supplier';
        $arr_header[count($arr_header)]='Order By';
        $arr_header[count($arr_header)]='Unit Price';
        $arr_header[count($arr_header)]='Item Quantity';
        $arr_header[count($arr_header)]='Extended Price';

        $arr_field[count($arr_field)]='docnum';
        $arr_field[count($arr_field)]='itmcde';
        $arr_field[count($arr_field)]='trndte';
        $arr_field[count($arr_field)]='ordernum';
        $arr_field[count($arr_field)]='suppcde';
        $arr_field[count($arr_field)]='orderby';
        $arr_field[count($arr_field)]='untprc';
        $arr_field[count($arr_field)]='itmqty';
        $arr_field[count($arr_field)]='extprc';

        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='date';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='text';
        $arr_type[count($arr_type)]='decimal';
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
    
            $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['date_from']."' AND purchasesorderfile1.trndte<='".$_POST['date_to']."'";
        }
    
        else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
            $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
            $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['date_from']."'";
        }
    
        else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
            $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
            $xfilter .= " AND purchasesorderfile1.trndte<='".$_POST['date_to']."'";
        }
        if(isset($_POST['item']) && !empty($_POST['item'])){
            $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
        }

        $select_db="SELECT purchasesorderfile2.docnum as purchasesorderfile2_docnum, purchasesorderfile1.trndte as purchasesorderfile1_trndte,purchasesorderfile1.orderby as purchasesorderfile1_orderby, purchasesorderfile1.ordernum as purchasesorderfile1_ordernum,
        purchasesorderfile2.extprc as purchasesorderfile2_extprc, supplierfile.suppdsc as supplierfile_suppdsc, purchasesorderfile2.untprc as purchasesorderfile2_untprc, purchasesorderfile2.itmqty as purchasesorderfile2_itmqty,
        itemfile.itmdsc as itemfile_itmdsc FROM purchasesorderfile2 LEFT JOIN purchasesorderfile1 ON purchasesorderfile2.docnum = purchasesorderfile1.docnum LEFT JOIN itemfile 
        ON purchasesorderfile2.itmcde = itemfile.itmcde LEFT JOIN supplierfile ON purchasesorderfile1.suppcde = supplierfile.suppcde
        WHERE true ".$xfilter." ORDER BY itemfile.itmdsc ASC, purchasesorderfile1.trndte ASC";

        $stmt_main	= $link->prepare($select_db);
        $stmt_main->execute();
        $grandtotal = 0;
        while($rs_main=$stmt_main->fetch())
        {

            if(isset($rs_main["purchasesorderfile1_trndte"]) && !empty($rs_main["purchasesorderfile1_trndte"])){
                $rs_main["purchasesorderfile1_trndte"] = date("m-d-Y",strtotime($rs_main["purchasesorderfile1_trndte"]));
                $rs_main["purchasesorderfile1_trndte"] = str_replace('-','/',$rs_main["purchasesorderfile1_trndte"]);
            }

            $grandtotal+=$rs_main['purchasesorderfile2_extprc'];
            
            $xchunk3.=$rs_main['purchasesorderfile2_docnum'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['itemfile_itmdsc'];
            $xchunk3.=$delimeter;
        
            $xchunk3.=$rs_main['purchasesorderfile1_trndte'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['purchasesorderfile1_ordernum'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['supplierfile_suppdsc'];
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['purchasesorderfile1_orderby'];
            $xchunk3.=$delimeter;

            $xchunk3.=number_format($rs_main['purchasesorderfile2_untprc'],2);
            $xchunk3.=$delimeter;

            $xchunk3.=$rs_main['purchasesorderfile2_itmqty'];
            $xchunk3.=$delimeter;

            $xchunk3.=number_format($rs_main['purchasesorderfile2_extprc'],2);
            $xchunk3.=$delimeter;

            $xchunk3.=$xline;
        }

        $xchunk3.=number_format($grandtotal,2);
        

        $xfilename ="purchase_item.xls";

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
