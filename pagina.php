<?php
require_once('apiProcs.php');
require_once('apiConstants.php');

function getPagina($aStukId, $aVersieNr, $aPaginaNr, $aHoogte, $aBreedte)
{
    if (($aStukId == '') or ($aPaginaNr == '')) {
        http_response_code(406);
    } else
        doGetPagina($aStukId, $aVersieNr, $aPaginaNr, $aHoogte, $aBreedte);
}

function getPaginaByName($aNaam, $aMap, $aHoogte, $aBreedte) {
    verstuurImage($aNaam, $aMap, $aHoogte, $aBreedte);
}

function verstuurImage($aFilename, $aMap, $aHoogte, $aBreedte) {
    $full_file_name = getDataMap() . '/' . $aMap . '/' . $aFilename;
    if ($aBreedte == '')
        $aBreedte = 0;
    if ($aHoogte == '')
        $aHoogte = 0;
    if (($aHoogte == 0) and ($aBreedte == 0)) {
        header("Content-Type: image/jpg");
        header('Content-Length: ' . filesize($full_file_name));
        header("Content-Disposition: inline; filename=$aFilename");  
        readfile($full_file_name);

    }
    else {
        error_reporting(E_ERROR | E_PARSE);
    // schaal het plaatje zo dat de afmetingen niet boven de opgegeven hoogte en/of breedte komen
        $src = imagecreatefromjpeg($full_file_name);
        list($mySrcBreedte, $mySrcHoogte) = @getimagesize($full_file_name); // getimagesize geeft (mogelijk onterecht?) een warning/
//        print($mySrcBreedte . ', ' . $mySrcHoogte);
        $mySchaalBreedte = $aBreedte / (int)$mySrcBreedte;
        $mySchaalHoogte = $aHoogte / (int)$mySrcHoogte;
        if ($mySchaalHoogte == 0)
            $mySchaal = $mySchaalBreedte;
        elseif ($mySchaalBreedte == 0)
            $mySchaal = $mySchaalHoogte;
        else
            $mySchaal = min($mySchaalHoogte, $mySchaalBreedte);
        $myDstBreedte = $mySrcBreedte * $mySchaal;
        $myDstHoogte = $mySrcHoogte * $mySchaal;
        $dst = imagecreatetruecolor($myDstBreedte, $myDstHoogte);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $myDstBreedte, $myDstHoogte, $mySrcBreedte, $mySrcHoogte);
//        imagecopyresized($dst, $src, 0, 0, 0, 0, $myDstBreedte, $myDstHoogte, $mySrcBreedte, $mySrcHoogte);
        header("Content-Type: image/jpg");
//        header('Content-Length: ' . filesize($full_file_name));
        header("Content-Disposition: inline; filename=$aFilename");
//        error_reporting(E_ALL);
        imagejpeg($dst, null, 100);  
    }

}
function doGetPagina($aStukId, $aVersieNr, $aPaginaNr, $aHoogte, $aBreedte)
{
    $conn = getDBConnection();
    if ($conn != null) {
        $rs = $conn->query(
            sprintf('select p.*, sv.map from %s p ', tblPagina) .
            sprintf('join %s sv on sv.id = p.stukVersieId ', tblStukVersie) .
            sprintf('where (sv.stukId = %s) and (sv.versieNr = %s) and (p.paginaNr = %s) ', $aStukId, $aVersieNr, $aPaginaNr)
        );
        if ($rs) {
            if ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
                verstuurImage($row['bestandsnaam'], $row['map'], $aHoogte, $aBreedte);
            } else {
                http_response_code(406);
            }
        } else
            SendResult(123, $conn->error);
        $conn->close();
    }
}

?>