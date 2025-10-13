<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

class Tabel {
    public $items = array();
}

function sendStukTabel() {    
    $conn = getDBConnection();
    $cmd =
        sprintf('select * from %s order by id', tblStuk);
    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(1, 'rs is false');
    else {
        $myStukken = new Tabel();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myStuk = new stdClass();
            $myStuk->id = $row[vnmId];
            $myStuk->titel = $row[vnmTitel];
            $myStuk->auteurId = $row[vnmAuteurId];
            $myStuk->albumId = $row[vnmAlbumId];
            $myStuk->opmerkingen = $row[vnmOpmerkingen];
            array_push($myStukken->items, $myStuk);
        }
        $conn->close();
        SendJsonObject($myStukken);
    }
}

function sendStukVersieTabel() {    
    $conn = getDBConnection();
    $cmd =
        sprintf('select * from %s order by id', tblStukVersie);
    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(1, 'rs is false');
    else {
        $myStukVersies = new Tabel();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myStukVersie = new stdClass();
            $myStukVersie->id = $row[vnmId];
            $myStukVersie->stukId = $row[vnmStukId];
            $myStukVersie->versieNr = $row[vnmVersieNr];
            $myStukVersie->map = $row[vnmMap];
            array_push($myStukVersies->items, $myStukVersie);
        }
        $conn->close();
        SendJsonObject($myStukVersies);
    }
}

function sendPaginaTabel() {    
    $conn = getDBConnection();
    $cmd =
        sprintf('select * from %s order by id', tblPagina);
    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(1, 'rs is false');
    else {
        $myPaginas = new Tabel();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myPagina = new stdClass();
            $myPagina->id = $row[vnmId];
            $myPagina->stukVersieId = $row[vnmStukVersieId];
            $myPagina->paginaNr = $row[vnmPaginaNr];
            $myPagina->naam = $row[vnmBestandsnaam];
            array_push($myPaginas->items, $myPagina);
        }
        $conn->close();
        SendJsonObject($myPaginas);
    }
}

?>