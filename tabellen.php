<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

function getStukTabel() {    
    $conn = getDBConnection();
    $cmd =
        sprintf('select id, naam from %s order by %s', tblAlbum, vnmNaam);
    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(1, 'rs is false');
    else {
        $myAlbums = new Albums();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myAlbum = new Album();
            $myAlbum->id = $row[vnmId];
            $myAlbum->naam = $row[vnmNaam];
            array_push($myAlbums->items, $myAlbum);
        }
        $conn->close();
        SendJsonObject($myAlbums);
    }

}

?>