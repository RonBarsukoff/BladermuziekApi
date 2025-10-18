<?php
require_once('apiProcs.php');
require_once('apifuncties.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$myPostdata = LeesPostData(
     'postAlbum.json'
);

$myPagina = getGetVar('pagina');
verwerkRequest($myPagina, $myPostdata);

function LeesPostData($aOpdracht) {
    $myFileName = './testdata/' . $aOpdracht;
    $myFileHandle = fopen($myFileName, 'r');
    $myContent = fread($myFileHandle, filesize($myFileName));
    fclose($myFileHandle);
    return $myContent;
}

?>