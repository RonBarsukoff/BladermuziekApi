<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

class Tabel {
    public $items = array();
}

function sendStukTabel() { 
    sendTabel(tblStuk);   
}

function sendStukVersieTabel() {    
    sendTabel(tblStukVersie);   
}

function sendPaginaTabel() {    
    sendTabel(tblPagina);   
}

function sendTabel($aTabelNaam) {
    if ($aTabelNaam == '')
        sendResult(errParameterFout, 'tabelNaam is leeg');
    $conn = getDBConnection();
    $cmd = sprintf('select * from %s order by id', $aTabelNaam);
    $rs = $conn->query($cmd);
    if (!$rs) {
        $conn->close();
        SendResult(1, 'rs is false');
    }
    else {
        $myItems = new Tabel();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myItem = new stdClass();
            foreach ($row as $key => $value) {
                $myItem->$key = $value;
            }
            array_push($myItems->items, $myItem);
        }
        $conn->close();
        SendJsonObject($myItems);
    }
}

function toevoegenRecord($aConn, $aTabelNaam, $aRecord) {
    $velden = '';
    $waarden = '';
    foreach ($aRecord as $key => $value) {
        if ($velden != '') {
            $velden .= ', ';
            $waarden .= ', ';
        }
        $velden .= $value->veldnaam;
        $waarden .= "'" . $aConn->real_escape_string($value->waarde) . "'";
    }
    $cmd = sprintf('insert into %s (%s) values (%s)', 
        $aTabelNaam, 
        $velden,
        $waarden);
    if (!$aConn->query($cmd)) {
        SendResult(1, 'Fout bij insert: ' . $aConn->error);
    }
    else {
        return $aConn->insert_id;
    }
}

function wijzigenRecord($aConn, $aTabelNaam, $aRecord) {
    $veldenSet = '';
    $idWaarde = '';
    foreach ($aRecord as $key => $value) {
        if ($value->veldnaam == 'id') {
            $idWaarde = $value->waarde;
        }
        else {
            if ($veldenSet != '') {
                $veldenSet .= ', ';
            }
            $veldenSet .= sprintf('%s = \'%s\'', 
                $value->veldnaam,
                $aConn->real_escape_string($value->waarde));
        }
    }
    if ($idWaarde == '') {
        $aConn->close();
        SendResult(errParameterFout, 'id ontbreekt bij wijzigen');
    }
    $cmd = sprintf('update %s set %s where id = %s',
        $aTabelNaam,
        $veldenSet,
        $aConn->real_escape_string($idWaarde));
    if (!$aConn->query($cmd)) {
        $aConn->close();
        SendResult(1, 'Fout bij update: ' . $aConn->error);
    }
    else {
        $aantalGewijzigd = $aConn->affected_rows;
        return $aantalGewijzigd;
    }
}

function verwijderenRecord($aConn, $aTabelNaam, $aRecord) {
    $idWaarde = '';
    foreach ($aRecord as $key => $value) {
        if ($value->veldnaam == 'id') {
            $idWaarde = $value->waarde;
            break;
        }
    }
    if ($idWaarde == '') {
        $aConn->close();
        SendResult(errParameterFout, 'id ontbreekt bij verwijderen');
    }
    $cmd = sprintf('delete from %s where id = %s',
        $aTabelNaam,
        $aConn->real_escape_string($idWaarde));
    if (!$aConn->query($cmd)) {
        $aConn->close();
        SendResult(1, 'Fout bij delete: ' . $aConn->error);
    }
    else {
        $aantalVerwijderd = $aConn->affected_rows;
        return $aantalVerwijderd;
    }
}

function bewaarRecord($postData) {
    if ($postData == '')
        sendResult(errParameterFout, 'postData is leeg');
    $postObject = json_decode($postData);
    if (!isset($postObject->tabel->naam))
        sendResult(errParameterFout, 'tabel ontbreekt');
    if (!isset($postObject->tabel->velden))
        sendResult(errParameterFout, 'record ontbreekt');
    $tabelNaam = $postObject->tabel->naam;
    $record = $postObject->tabel->velden;
    $conn = getDBConnection();
    if (isset($postObject->tabel->actie))
        if ($postObject->tabel->actie == 'toevoegen') {
            $nieuwId = toevoegenRecord($conn, $tabelNaam, $record);
            $conn->close();
            $resultObj = new stdClass();
            $resultObj->nieuwId = $nieuwId;
            SendJsonObject($resultObj);
        }
        elseif ($postObject->tabel->actie == 'wijzigen') {
            $aantalGewijzigd = wijzigenRecord($conn, $tabelNaam, $record);
            $conn->close();
            $resultObj = new stdClass();
            $resultObj->aantalGewijzigd = $aantalGewijzigd;
            SendJsonObject($resultObj);
        }
        elseif ($postObject->tabel->actie == 'verwijderen') {
            $aantalVerwijderd = verwijderenRecord($conn, $tabelNaam, $record);
            $conn->close();
            $resultObj = new stdClass();
            $resultObj->aantalVerwijderd = $aantalVerwijderd;
            SendJsonObject($resultObj);
        }
        else {
            SendResult(errParameterFout, 'onbekende actie');
        }
}

?>