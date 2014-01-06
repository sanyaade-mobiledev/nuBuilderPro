<?php
 require_once 'conv-140.inc.php'; require_once 'encodation-140.inc.php'; require_once 'bit-placement-bin-140.inc.php'; require_once 'master-rnd-140.inc.php'; require_once 'crc-ccitt.inc.php'; class Datamatrix_140 { var $iSize = -1; var $iErrLevel = ECC_050; var $iEncodation = null; var $iCRC_CCITT = null; var $iConv = null; var $iTilde = false; var $iError = 0 ; function Datamatrix_140($aSize=-1,$aDebug=false) { $this->iEncodation = new Encodation_140(); $this->iEncodation->iSelectSchema = ENCODING_BASE11; $this->iCRC_CCITT = new CRC_CCITT(); $this->iMasterRand = new MasterRandom(); if( $this->iMasterRand === false ) { $this->iError = -32; return false; } $this->iBitPlacement = new BitPlacement_140(); if( $aSize >= 0 ) { $aSize -= 50; if( $aSize < 0 || $aSize > 20 ) { $this->iError = -30; return false; } $this->iSize = $aSize*2+7; } } function SetEncoding($aEncoding=ENCODING_BASE11) { $this->iEncodation->iSelectSchema = $aEncoding; } function SetSize($aSize=-1) { if( $aSize >= 0 ) { $aSize -= 50; if( $aSize < 0 || $aSize > 20 ) { $this->iError = -30; return false; } $this->iSize = $aSize*2+7; } } function SetErrLevel($aErrLevel) { $this->iErrLevel = $aErrLevel ; } function SetTilde($aFlg=true) { $this->iTilde = $aFlg; } function Enc($aData,$aDebug=false) { if( $this->iTilde ) { $r = tilde_process($aData); if( $r === false ) { $this->iError = -10; return false; } $aData = $r; } $this->iConv = null; $this->iConv = ECC_Factory::Create($this->iErrLevel); $data = str_split($aData); $ndata = count($data); $this->iEncodation->AutoSelect($data); $bits = array(); $bits = $this->iEncodation->GetPrefix(); $bidx = 5; $crc_prefix = array(chr($this->iEncodation->GetCRCPrefix()),chr(0)); $crc_data = array_merge($crc_prefix,$data); $crc = $this->iCRC_CCITT->Get($crc_data); $crcbits=array(); Word2Bits($crc,$crcbits,16); for( $i=0; $i < 16; ++$i ) { $bits[$bidx++] = $crcbits[$i]; } $lenbits = array(); Word2Bits($ndata,$lenbits,9); $lenbits = array_reverse($lenbits); for($i=0; $i < 9; ++$i) { $bits[$bidx++] = $lenbits[$i]; } $databits = array(); $this->iEncodation->Encode($data,$databits); $m = count($databits); for($i=0; $i < $m; ++$i ) { $databits[$i] = array_reverse($databits[$i]); } for($i=0; $i < $m; ++$i ) { $k = count($databits[$i]); for($j=0; $j < $k; ++$j ) { $bits[$bidx++] = $databits[$i][$j]; } } $protectedbits = array(); $this->iConv->_Get($bits,$protectedbits); $headerbits = $this->iConv->GetHeader(); $totBits = count($headerbits) + count($protectedbits); if( $this->iSize == -1 ) { $mat_size = 7; $mat_idx = 0; while( ($mat_size <= 47) && ($mat_size*$mat_size < $totBits) ) { $mat_idx++; $mat_size += 2 ; } if( $mat_size > 47 ) { $this->iError = -31; return false; } $this->iSize = $mat_size ; $ntrailerbits = $mat_size*$mat_size - $totBits; } else{ $mat_size = $this->iSize; if( $mat_size*$mat_size < $totBits ) { $this->iError = -31; return false; } $ntrailerbits = $mat_size*$mat_size - $totBits; $mat_idx = ($mat_size-7)/2; } $trailerbits = array_fill(0,$ntrailerbits,0); $bits = array_merge($headerbits,$protectedbits,$trailerbits); $ret = $this->iMasterRand->Randomize($bits); if( $ret === false ) { $this->iError = -33; return false; } $outputMatrix=array(array(),array()); $this->iBitPlacement->Set($mat_idx,$bits,$outputMatrix); $pspec = new PrintSpecification(DM_TYPE_140,$data,$outputMatrix,$this->iEncodation->iSelectSchema,$this->iErrLevel); return $pspec; } } ?>
