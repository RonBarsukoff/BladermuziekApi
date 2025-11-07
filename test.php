<?php
require_once('apiProcs.php');
require_once('apifuncties.php');

sendHeaders();

$myPagina = getGetVar('pagina');    
$myPostdataFilename = './testdata/' . $myPagina . '.json';
if (!file_exists($myPostdataFilename)) {
    echo "Testdata file not found: " . $myPostdataFilename;
}
else {
    $myPostdata = LeesPostData($myPostdataFilename);
    verwerkRequest($myPagina, $myPostdata);
}

function LeesPostData($aFilename) {
    $myFileHandle = fopen($aFilename, 'r');
    $myContent = fread($myFileHandle, filesize($aFilename));
    fclose($myFileHandle);
    return $myContent;
}

?>