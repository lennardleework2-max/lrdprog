<?php
class tab_ezpdf extends Cezpdf
{

	function ezPlaceData($xpos,$ypos,$data,$font_size,$align)
    {

	   global $glo_arr;
	   global $glo_top;
	   $top=$glo_top + 700;
        switch ($align){
        case "right":
            //if (is_numeric($data)){
            //    $formatted=number_format($data,$decimal);
            //    } else {
                $formatted=$data;
            //    }

        $formatted_width=$this->getTextWidth($font_size, $formatted);
        $x=$xpos-$formatted_width;
        $this->addText($x,$ypos,$font_size,$formatted,$angle=0,$wordSpaceAdjust=1);

        $glo_arr[$top - $ypos][$x]=$formatted;
        break;

        case "centre":
        case "center":
            // if (is_numeric($data)){
            //    $formatted=number_format($data,$decimal);
            //    } else {
        $formatted=$data;
            //    }

        $formatted_width= $this->getTextWidth($font_size, $formatted);
        $x=$xpos-($formatted_width/2);
        $this->addText($x,$ypos,$font_size,$formatted,$angle=0,$wordSpaceAdjust=1);
         $glo_arr[$top - $ypos][$x]=$formatted;
        break;

        default:
        case "left":
        $this->addText($xpos,$ypos,$font_size,$data."m",$angle=0,$wordSpaceAdjust=1);
         $glo_arr[$top - $ypos][$xpos]=$data;
        break;



    	}

	}

    function ezStream($xtype = "xls",$xfilename ="")
    {
        global $glo_arr;
        ksort($glo_arr);


        foreach ($glo_arr as $key => $value) {
            ksort($glo_arr[$key]);
        }

        ksort($glo_arr);
        // echo "<pre>";
        // print_r($glo_arr);
        $xchunk3 = '';
        $xline = "\r\n";
        $xtab = chr(9);


        foreach ($glo_arr as $key => $value)
        {
            foreach ($glo_arr[$key] as $key2 => $value2)
            {
                    $xchunk3 .= $glo_arr[$key][$key2]. $xtab;
            }
             $xchunk3 .=  $xline;
        }


        # ADD by Genesis 6/26/2018
        $xchunk3 = str_replace("<b>", "", $xchunk3);
        $xchunk3 = str_replace("</b>", "", $xchunk3);
        $xchunk3 = str_replace("<i>", "", $xchunk3);
        $xchunk3 = str_replace("</i>", "", $xchunk3);
        // echo "<pre>";
        // var_dump($xchunk3);
        // die();
        # Change by Genesis 6/26/2018
        $xfilename  = $xfilename == "" ? uniqid(rand(1, 100000), true) :  $xfilename;
        $xfilename  .= ".".$xtype;

        header("Cache-control: private");

        $xfhndl = fopen($xfilename, 'w+');

            // Write $somecontent to our opened file.
        if (fwrite($xfhndl, $xchunk3) == FALSE) {
        	echo "Cannot write to file ($xfilename)";
        	exit;
        }

        fclose($xfhndl);

        header("Content-Disposition: attachment; filename=$xfilename");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-encoding: binary");
        header("Pragma:no-cache");
        header("Expires:0");
        readfile($xfilename);

        unlink($xfilename);
    }

    function ezNewPage()
	{
		global $glo_top;
		 $glo_top =  $glo_top + 1000;
		  $pageRequired=1;
		  if (isset($this->ez['columns']) && $this->ez['columns']['on']==1)
		  {
		    // check if this is just going to a new column
		    // increment the column number
			//echo 'HERE<br>';
		    $this->ez['columns']['colNum']++;
			//echo $this->ez['columns']['colNum'].'<br>';
		    if ($this->ez['columns']['colNum'] <= $this->ez['columns']['options']['num']){
		      // then just reset to the top of the next column
		      $pageRequired=0;
		    } else {
		      $this->ez['columns']['colNum']=1;
		      $this->ez['topMargin']=$this->ez['columns']['margins'][2];
		    }

		    $width = $this->ez['columns']['width'];
		    $this->ez['leftMargin']=$this->ez['columns']['margins'][0]+($this->ez['columns']['colNum']-1)*($this->ez['columns']['options']['gap']+$width);
		    $this->ez['rightMargin']=$this->ez['pageWidth']-$this->ez['leftMargin']-$width;
		  }
			//echo 'left='.$this->ez['leftMargin'].'   right='.$this->ez['rightMargin'].'<br>';

		  if ($pageRequired)
		  {
		    // make a new page, setting the writing point back to the top
		    $this->y = $this->ez['pageHeight']-$this->ez['topMargin'];
		    // make the new page with a call to the basic class.
		    $this->ezPageCount++;
		    if (isset($this->ez['insertMode']) && $this->ez['insertMode']==1){
		      $id = $this->ezPages[$this->ezPageCount] = $this->newPage(1,$this->ez['insertOptions']['id'],$this->ez['insertOptions']['pos']);
		      // then manipulate the insert options so that inserted pages follow each other
		      $this->ez['insertOptions']['id']=$id;
		      $this->ez['insertOptions']['pos']='after';
		    } else {
		      $this->ezPages[$this->ezPageCount] = $this->newPage();
		    }
		  } else {
		    $this->y = $this->ez['pageHeight']-$this->ez['topMargin'];
		  }
	}



}

?>
