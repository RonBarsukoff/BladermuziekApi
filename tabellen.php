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
    $velden = array();
    $waarden = array();
    $placeholders = array();
    $types = '';
    
    foreach ($aRecord as $key => $value) {
        $velden[] = $value->veldnaam;
        $waarden[] = $value->waarde;
        $placeholders[] = '?';
        // Bepaal type: i=integer, d=double, s=string
        if (is_int($value->waarde)) {
            $types .= 'i';
        } elseif (is_float($value->waarde)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $cmd = sprintf('insert into %s (%s) values (%s)', 
        $aTabelNaam, 
        implode(', ', $velden),
        implode(', ', $placeholders));
    
    try {
        $stmt = $aConn->prepare($cmd);
        if (!$stmt) {
            SendResult(errDatabase, 'Fout bij prepare insert: ' . $aConn->error);
        }
        
        // Dynamisch bind_param aanroepen
        if (!empty($waarden)) {
            $stmt->bind_param($types, ...$waarden);
        }
        
        $stmt->execute();
        $nieuwId = $stmt->insert_id;
        $stmt->close();
        return $nieuwId;
    } catch (mysqli_sql_exception $e) {
        if (isset($stmt)) {
            $stmt->close();
        }
        SendResult(errDatabase, 'Fout bij insert: ' . $e->getMessage());
    }
}

function wijzigenRecord($aConn, $aTabelNaam, $aRecord) {
    $veldenSet = array();
    $waarden = array();
    $types = '';
    $idWaarde = '';
    
    foreach ($aRecord as $key => $value) {
        if ($value->veldnaam == 'id') {
            $idWaarde = $value->waarde;
        }
        else {
            $veldenSet[] = $value->veldnaam . ' = ?';
            $waarden[] = $value->waarde;
            // Bepaal type: i=integer, d=double, s=string
            if (is_int($value->waarde)) {
                $types .= 'i';
            } elseif (is_float($value->waarde)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
    }
    
    if ($idWaarde == '') {
        $aConn->close();
        SendResult(errParameterFout, 'id ontbreekt bij wijzigen');
    }
    
    // Voeg id toe aan parameters
    $waarden[] = $idWaarde;
    $types .= is_int($idWaarde) ? 'i' : 's';
    
    $cmd = sprintf('update %s set %s where id = ?',
        $aTabelNaam,
        implode(', ', $veldenSet));
    
    try {
        $stmt = $aConn->prepare($cmd);
        if (!$stmt) {
            $aConn->close();
            SendResult(errDatabase, 'Fout bij prepare update: ' . $aConn->error);
        }
        
        // Dynamisch bind_param aanroepen
        if (!empty($waarden)) {
            $stmt->bind_param($types, ...$waarden);
        }
        
        $stmt->execute();
        $aantalGewijzigd = $stmt->affected_rows;
        $stmt->close();
        return $aantalGewijzigd;
    } catch (mysqli_sql_exception $e) {
        if (isset($stmt)) {
            $stmt->close();
        }
        $aConn->close();
        SendResult(errDatabase, 'Fout bij update: ' . $e->getMessage());
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
    
    $cmd = sprintf('delete from %s where id = ?', $aTabelNaam);
    
    try {
        $stmt = $aConn->prepare($cmd);
        if (!$stmt) {
            $aConn->close();
            SendResult(errDatabase, 'Fout bij prepare delete: ' . $aConn->error);
        }
        
        $type = is_int($idWaarde) ? 'i' : 's';
        $stmt->bind_param($type, $idWaarde);
        
        $stmt->execute();
        $aantalVerwijderd = $stmt->affected_rows;
        $stmt->close();
        return $aantalVerwijderd;
    } catch (mysqli_sql_exception $e) {
        if (isset($stmt)) {
            $stmt->close();
        }
        $aConn->close();
        SendResult(errDatabase, 'Fout bij delete: ' . $e->getMessage());
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