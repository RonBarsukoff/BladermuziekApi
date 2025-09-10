<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

class Album {
    public $id;
    public $naam;
}

class Albums {
    public $items = array();
}

function getAlbumlijst()
{
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

function PostAlbum($aData) {
    $myAlbum = json_decode($aData);
    $cmd = sprintf('update %s set %s = (?) where id = (?)', tblAlbum, vnmNaam);
    $conn = getDBConnection();
    $statement = $conn->prepare($cmd);
    if ($statement) {
        $statement->bind_param('si', $myAlbum->naam, $myAlbum->id);
        $statement->execute();
        if ($conn->errno)
            SendResult(123, $conn->error);
        else
            SendResult(0, 'ok');
    }
    $conn->close();
}

?>