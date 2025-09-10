<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

class Auteur
{
    public $id;
    public $naam;
}

class Auteurs
{
    public $items = array();
}

function getAuteurlijst() {
    $conn = getDBConnection();
    $cmd =
        sprintf('select id, naam from %s order by %s', tblAuteur, vnmNaam);
    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(1, 'rs is false');
    else {
        $myAuteurs = new Auteurs();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myAuteur = new Auteur();
            $myAuteur->id = $row[vnmId];
            $myAuteur->naam = $row[vnmNaam];
            array_push($myAuteurs->items, $myAuteur);
        }
        $conn->close();
        SendJsonObject($myAuteurs);
    }
}

function PostAuteur($aData) {
    $myAuteur = json_decode($aData);
    $cmd = sprintf('update %s set %s = (?) where id = (?)', tblAuteur, vnmNaam);
    $conn = getDBConnection();
    $statement = $conn->prepare($cmd);
    if ($statement) {
        $statement->bind_param('si', $myAuteur->naam, $myAuteur->id);
        $statement->execute();
        if ($conn->errno)
            SendResult(123, $conn->error);
        else
            SendResult(0, 'ok');
    }
    $conn->close();
}

?>