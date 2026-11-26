<?php
session_start();
if (!isset($_SESSION['userdesc'])) { die('Unauthorized access'); }
require_once('resources/db_init.php');
require_once('resources/connect4.php');
require_once('resources/lx2.pdodb.php');
require_once('ezpdfclass_new/class/class.ezpdf.php');
require_once('vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
date_default_timezone_set('Asia/Manila');
$date_printed=date('F j, Y h:i:s A');$report_user=isset($_SESSION['userdesc'])?$_SESSION['userdesc']:'';
$critical_only=isset($_REQUEST['chk_critical_only'])&&$_REQUEST['chk_critical_only']==='on';
$output_type=isset($_POST['txt_output_type'])?(string)$_POST['txt_output_type']:'';$date_filter='';
if(!empty($_REQUEST['date_search'])){$timestamp=strtotime($_REQUEST['date_search']);if($timestamp!==false)$date_filter=date('Y-m-d',$timestamp);}
$item_filter=isset($_REQUEST['item'])?trim((string)$_REQUEST['item']):'';
$item_sql='SELECT * FROM itemfile';$item_params=array();if($item_filter!==''){$item_sql.=' WHERE itmcde=?';$item_params[]=$item_filter;}$item_sql.=' ORDER BY itmdsc ASC';
$item_stmt=$link->prepare($item_sql);$item_stmt->execute($item_params);
$balance_sql='SELECT SUM(tranfile2.stkqty) AS xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum=tranfile2.docnum WHERE tranfile2.itmcde=?';if($date_filter!=='')$balance_sql.=' AND tranfile1.trndte<=?';$balance_stmt=$link->prepare($balance_sql);
$po_stmt=$link->prepare('SELECT purchasesorderfile2.itmqty,purchasesorderfile1.trndte AS po1_trndte FROM purchasesorderfile2 LEFT JOIN purchasesorderfile1 ON purchasesorderfile2.docnum=purchasesorderfile1.docnum WHERE purchasesorderfile2.itmcde=? ORDER BY purchasesorderfile1.trndte DESC LIMIT 1');
$recent_po_stmt=$link->prepare('SELECT purchasesorderfile1.ordernum FROM purchasesorderfile2 INNER JOIN purchasesorderfile1 ON purchasesorderfile1.docnum=purchasesorderfile2.docnum WHERE purchasesorderfile2.itmcde=? ORDER BY purchasesorderfile1.trndte DESC, purchasesorderfile2.recid DESC LIMIT 1');
$rows=array();
while($item=$item_stmt->fetch(PDO::FETCH_ASSOC)){
 $params=array($item['itmcde']);if($date_filter!=='')$params[]=$date_filter;$balance_stmt->execute($params);$balance_row=$balance_stmt->fetch(PDO::FETCH_ASSOC);$balance=$balance_row&&$balance_row['xsum']!==null?(float)$balance_row['xsum']:0;$critical_qty=$item['critical_qty']!==null?(float)$item['critical_qty']:0;if($critical_only&&$balance>$critical_qty)continue;
 $po_stmt->execute(array($item['itmcde']));$po=$po_stmt->fetch(PDO::FETCH_ASSOC);$recent_po_stmt->execute(array($item['itmcde']));$recent_po=(string)$recent_po_stmt->fetchColumn();
 $rows[]=array('item'=>(string)$item['itmdsc'],'balance'=>$balance,'critical_qty'=>$critical_qty,'po_date'=>$po&&!empty($po['po1_trndte'])?date('m/d/Y',strtotime($po['po1_trndte'])):'','po_qty'=>$po&&$po['itmqty']!==null?(float)$po['itmqty']:'','minimal'=>flag_is_yes($item['crt_minimal_order']),'offline'=>flag_is_yes($item['crt_offline']),'online'=>flag_is_yes($item['crt_online']),'discontinued'=>flag_is_yes($item['crt_discontinued']),'recent_po'=>$recent_po,'remarks'=>(string)$item['crt_remarks']);
}
if($output_type==='xlsx'){export_xlsx($rows,$report_user,$date_printed,$date_filter,$critical_only);exit;}
ob_start();$pdf=new Cezpdf('Letter','landscape');$pdf->selectFont('ezpdfclass/fonts/Helvetica.afm');$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM} of {TOTALPAGENUM}',1);
$columns=array(array('label'=>'Item','x'=>25,'width'=>143,'align'=>'left'),array('label'=>'Balance','x'=>168,'width'=>55,'align'=>'right'),array('label'=>'Critical Level','x'=>223,'width'=>63,'align'=>'right'),array('label'=>'Last Date P.O.','x'=>286,'width'=>58,'align'=>'left'),array('label'=>'P.O. Quantity','x'=>344,'width'=>55,'align'=>'right'),array('label'=>'Minimal Order','x'=>399,'width'=>52,'align'=>'center'),array('label'=>'Offline','x'=>451,'width'=>43,'align'=>'center'),array('label'=>'Online','x'=>494,'width'=>43,'align'=>'center'),array('label'=>'Discontinued','x'=>537,'width'=>65,'align'=>'center'),array('label'=>'Recent PO Number','x'=>602,'width'=>70,'align'=>'left'),array('label'=>'Remarks','x'=>672,'width'=>98,'align'=>'left'));
$header=$pdf->openObject();$pdf->saveState();$pdf->ezPlaceData(25,580,'<b>Critical Inventory Balance</b>',15,'left');$pdf->ezPlaceData(25,563,'<b>Pdf Report by: '.pdf_text($report_user).' (Summarized)</b>',9,'left');$pdf->ezPlaceData(25,548,'Date Printed : '.$date_printed,9,'left');$pdf->line(25,538,770,538);foreach($columns as $column)draw_header($pdf,$column);$pdf->line(25,505,770,505);$pdf->restoreState();$pdf->closeObject();$pdf->addObject($header,'all');
$y=490;$font_size=7.5;$top_pad=8;$bottom_pad=6;
foreach($rows as $row){
 $reorder=$row['balance']<=$row['critical_qty'];
 $item_lines=wrap_pdf_text($pdf,pdf_text($row['item']),$columns[0]['width']-8,$font_size);$recent_po_lines=wrap_pdf_text($pdf,pdf_text($row['recent_po']),$columns[9]['width']-8,$font_size);$remark_lines=wrap_pdf_text($pdf,pdf_text($row['remarks']),$columns[10]['width']-8,$font_size);$critical_lines=$reorder?2:1;$line_count=max(count($item_lines),count($recent_po_lines),count($remark_lines),$critical_lines,1);$row_height=max(24,$top_pad+($line_count-1)*9+$bottom_pad);if($y-$row_height<42){$pdf->ezNewPage();$y=490;}
 $text_y=$y-$top_pad;
 if($row['discontinued']){$pdf->setColor(.973,.843,.855);$pdf->filledRectangle(25,$y-$row_height,745,$row_height);$pdf->setColor(.518,.126,.161);$pdf->setStrokeColor(.518,.126,.161);}else{$pdf->setColor(0,0,0);$pdf->setStrokeColor(0,0,0);}
 draw_lines($pdf,$columns[0]['x']+3,$text_y,$item_lines,$font_size);draw_cell($pdf,$columns[1],$text_y,format_number($row['balance']),$font_size);draw_cell($pdf,$columns[2],$text_y,format_number($row['critical_qty']),$font_size);if($reorder)draw_cell($pdf,$columns[2],$text_y-9,'REORDER',$font_size);draw_cell($pdf,$columns[3],$text_y,$row['po_date'],$font_size);draw_cell($pdf,$columns[4],$text_y,$row['po_qty']===''?'':format_number($row['po_qty']),$font_size);
 $pdf->setStrokeColor(0,0,0);draw_checkbox($pdf,$columns[5]['x']+$columns[5]['width']/2-4,$text_y-1,$row['minimal']);draw_checkbox($pdf,$columns[6]['x']+$columns[6]['width']/2-4,$text_y-1,$row['offline']);draw_checkbox($pdf,$columns[7]['x']+$columns[7]['width']/2-4,$text_y-1,$row['online']);draw_checkbox($pdf,$columns[8]['x']+$columns[8]['width']/2-4,$text_y-1,$row['discontinued']);draw_lines($pdf,$columns[9]['x']+3,$text_y,$recent_po_lines,$font_size);draw_lines($pdf,$columns[10]['x']+3,$text_y,$remark_lines,$font_size);$pdf->setStrokeColor(.82,.82,.82);$pdf->line(25,$y-$row_height,770,$y-$row_height);$y-=$row_height;
}
$pdf->setColor(0,0,0);$pdf->setStrokeColor(0,0,0);$pdf->line(25,$y-3,770,$y-3);$pdf->addText(30,15,8,'Date Printed : '.date('F j, Y, g:i A'));$pdf->ezStream();ob_end_flush();
function flag_is_yes($value){return strtoupper(trim((string)$value))==='Y';}
function pdf_text($value){$value=str_replace(array("\r","\n","\t"),' ',(string)$value);$value=str_replace('<','‹',$value);return preg_replace('/\s+/',' ',trim($value));}
function format_number($value){return number_format((float)$value,2,'.',',');}
function wrap_pdf_text($pdf,$text,$width,$size){if($text==='')return array('');$words=preg_split('/\s+/',trim($text));$lines=array();$line='';foreach($words as $word){$test=$line===''?$word:$line.' '.$word;if($pdf->getTextWidth($size,$test)<=$width){$line=$test;continue;}if($line!==''){$lines[]=$line;$line='';}while($pdf->getTextWidth($size,$word)>$width&&strlen($word)>1){$piece='';while(strlen($word)>0&&$pdf->getTextWidth($size,$piece.$word[0])<=$width){$piece.=$word[0];$word=substr($word,1);}if($piece===''){$piece=$word[0];$word=substr($word,1);}$lines[]=$piece;}$line=$word;}if($line!=='')$lines[]=$line;return $lines?:array('');}
function draw_lines($pdf,$x,$y,$lines,$size){foreach($lines as $index=>$line)$pdf->addText($x,$y-($index*9),$size,$line);}
function draw_cell($pdf,$column,$y,$text,$size){$x=$column['x']+3;if($column['align']==='right')$x=$column['x']+$column['width']-3;if($column['align']==='center')$x=$column['x']+$column['width']/2;$pdf->ezPlaceData($x,$y,pdf_text($text),$size,$column['align']);}
function draw_header($pdf,$column){$x=$column['x'];$width=$column['width'];$align=$column['align'];$lines=wrap_pdf_text($pdf,$column['label'],$width-5,7);foreach($lines as $i=>$line){$hx=$x+3;if($align==='right')$hx=$x+$width-3;if($align==='center')$hx=$x+$width/2;$pdf->ezPlaceData($hx,527-($i*9),'<b>'.$line.'</b>',7,$align);}}
function draw_checkbox($pdf,$x,$y,$checked){$size=8;$pdf->rectangle($x,$y-2,$size,$size);if($checked){$pdf->setLineStyle(1);$pdf->line($x+1.5,$y+1,$x+3.3,$y-1);$pdf->line($x+3.3,$y-1,$x+7,$y+5);$pdf->setLineStyle(.5);}}
function export_xlsx($rows,$user,$printed,$date_filter,$critical_only){
 $book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle('Critical Inventory');$sheet->mergeCells('A1:K1');$sheet->setCellValue('A1','Critical Inventory Balance');$sheet->setCellValue('A2','Report by: '.$user);$sheet->setCellValue('A3','Date Printed: '.$printed);$sheet->setCellValue('A4','Balance Date: '.($date_filter!==''?date('m/d/Y',strtotime($date_filter)):'All dates'));$sheet->setCellValue('A5','Critical items only: '.($critical_only?'Yes':'No'));
 $header=7;$sheet->fromArray(array('Item','Balance','Critical Level','Last Date P.O.','P.O. Quantity','Minimal Order','Offline','Online','Discontinued','Recent PO Number','Remarks'),null,'A'.$header);$row_num=$header+1;
 foreach($rows as $row){$reorder=$row['balance']<=$row['critical_qty'];$critical_display=$reorder?(format_number($row['critical_qty'])."\nREORDER"):$row['critical_qty'];$sheet->fromArray(array($row['item'],$row['balance'],$critical_display,$row['po_date'],$row['po_qty'],$row['minimal']?'☑':'☐',$row['offline']?'☑':'☐',$row['online']?'☑':'☐',$row['discontinued']?'☑':'☐',$row['recent_po'],$row['remarks']),null,'A'.$row_num);$sheet->setCellValueExplicit('J'.$row_num,$row['recent_po'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);if($row['discontinued']){$sheet->getStyle('A'.$row_num.':K'.$row_num)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8D7DA');$sheet->getStyle('A'.$row_num.':K'.$row_num)->getFont()->getColor()->setARGB('FF842029');}$row_num++;}
 $last=max($header,$row_num-1);$sheet->getStyle('A1:A5')->getFont()->setBold(true);$sheet->getStyle('A'.$header.':K'.$header)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle('A'.$header.':K'.$header)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D6EFD');$sheet->getStyle('A'.$header.':K'.$last)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
 if($last>$header){$sheet->getStyle('B'.($header+1).':C'.$last)->getNumberFormat()->setFormatCode('#,##0.00');$sheet->getStyle('E'.($header+1).':E'.$last)->getNumberFormat()->setFormatCode('#,##0.00');$sheet->getStyle('B'.($header+1).':C'.$last)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);$sheet->getStyle('F'.($header+1).':I'.$last)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);}
 $sheet->getStyle('A'.$header.':K'.$last)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD9DEE3');foreach(array('A'=>34,'B'=>12,'C'=>14,'D'=>15,'E'=>14,'F'=>14,'G'=>11,'H'=>11,'I'=>16,'J'=>20,'K'=>34) as $column=>$width)$sheet->getColumnDimension($column)->setWidth($width);$sheet->freezePane('A8');$sheet->setAutoFilter('A7:K'.$last);
 while(ob_get_level()>0)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="critical_inventory_'.date('Ymd_His').'.xlsx"');header('Cache-Control: max-age=0');(new Xlsx($book))->save('php://output');
}
?>
