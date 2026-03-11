<?php
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    

    echo "HELLO BETCH <br>";

if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }
    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itmcde='".$_POST['item']."'";
    }

    
    
    // $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    // tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid,
    // customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    // customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
    // tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC";

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute(array($_POST['item']));
    $grand_total = 0;

    while($rs_main = $stmt_main->fetch()){  
        $select_db2 = "SELECT * FROM tranfile2 LEFT JOIN tranfile1 ON tranfile2.docnum= tranfile1.docnum WHERE itmcde='".$rs_main['itmcde']."' ".$xfilter2." AND trncde='".$_POST['trncde_hidden']."' ORDER BY tranfile1.trndte ASC";
        echo  $select_db2."<br>";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $subtotal = 0;
    
        while($rs_main2 = $stmt_main2->fetch()){   

            $select_db3 = "SELECT * FROM tranfile1 LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde WHERE tranfile1.docnum='".$rs_main2['docnum']."'";
            $stmt_main3	= $link->prepare($select_db3);
            $stmt_main3->execute();
            $rs_main3 = $stmt_main3->fetch();


        }
    }

?>