<?php
require_once('apiProcs.php');
require_once('apiConstants.php');
require_once('apiConstants.php');

function PostStuk($aData) {
    $stuk = json_decode($aData, false);
    $conn = getDBConnection();

    // evt album toevoegen
    if (($stuk->album != '') && !isset($stuk->albumId)) {
        $cmd = sprintf('insert into %s (%s) values (?)', tblAlbum, vnmNaam);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('s', $stuk->album);
            $statement->execute();
            if ($conn->errno)
              SendResult(205, $conn->error);
            else {
                $rs = $conn->query('select last_insert_id() as id');
                $row = $rs->fetch_array(MYSQLI_ASSOC);
                $stuk->albumId = $row['id'];
            }
        }
    }

    // evt auteur toevoegen
    if (($stuk->auteur != '') && !isset($stuk->auteurId)) {
        $cmd = sprintf('insert into %s (%s) values (?)', tblAuteur, vnmNaam);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('s', $stuk->auteur);
            $statement->execute();
            if ($conn->errno)
                SendResult(206, $conn->error);
            else {
                $rs = $conn->query('select last_insert_id() as id');
                $row = $rs->fetch_array(MYSQLI_ASSOC);
                $stuk->auteurId = $row['id'];
            }
        }
    }

    // evt map maken
    if ($stuk->map != '') {
        if (!MapBestaat($stuk->map))
            MaakMap($stuk->map);
    }

    // evt stuknummer ophogen
    if (isset($tuk->albumId)) {
        if (isset($stuk->nr)) {
            if (($stuk->albumId > 0) && ($stuk->nr > 0))
                OphogenAchterliggendeNummers($stuk->albumId, $stuk->nr, $stuk->id);
        }
    }

    if ($stuk->id > 0) {
        $cmd =
            sprintf('update %s set ', tblStuk) .
            sprintf('  %s=? ', vnmTitel) .
            sprintf(', %s=? ', vnmMap) .
            sprintf(', %s=? ', vnmAuteurId) .
            sprintf(', %s=? ', vnmAlbumId) .
            sprintf(', %s=? ', vnmNr) .
            sprintf('where %s=?', vnmId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('ssiiii', $stuk->titel, $stuk->map, $stuk->auteurId, $stuk->albumId, $stuk->nr, $stuk->id);
            $statement->execute();
            if ($conn->errno)
                SendResult(204, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        } else
            SendResult(201, "Fout in prepare");
    } else {
        $cmd = sprintf('insert into %s (%s, %s, %s, %s, %s) values (?, ?, ?, ?, ?)', tblStuk, vnmTitel, vnmMap, vnmAuteurId, vnmAlbumId, vnmNr);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('ssiii', $stuk->titel, $stuk->map, $stuk->auteurId, $stuk->albumId, $stuk->nr);
            $statement->execute();
            if ($conn->errno)
                SendResult(203, 'Fout ' . $conn->errno . ' ' . $conn->error);
            else {
                $rs = $conn->query('select last_insert_id() as id');
                $row = $rs->fetch_array(MYSQLI_ASSOC);
                $stuk->id = $row['id'];
            }
            $statement->close();
        } else
            SendResult(202, " Fout in prepare " . $cmd . ' ' . $conn->error);
    }
    if (isset($stuk->paginas)) {
        $cmd = sprintf('delete from %s where stukId = ?', tblPagina);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $stuk->id);
            $statement->execute();
            if ($conn->errno)
                SendResult(203, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        }
        $cmd = sprintf('insert into %s (stukId, bestandsnaam, paginaNr) values (?, ?, ?)', tblPagina);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            foreach ($stuk->paginas->items as $myPagina) {
                $statement->bind_param('isi', $stuk->id, $myPagina->bestandsnaam, $myPagina->paginanr);
                $statement->execute();
                if ($conn->errno)
                    SendResult(203, 'Fout ' . $conn->errno . ' ' . $conn->error);
            }
            $statement->close();
        }
    }
    SendJsonObject($stuk);

    $conn->close();
}

function VerwijderStuk($aData)
{
    $stuk = json_decode($aData, false);
    $conn = getDBConnection();
    if ($stuk->id > 0) {
        // eerst paginas verwijderen
        $cmd =
            sprintf('delete from %s where %s=? ', tblPagina, vnmStukId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $stuk->id);
            $statement->execute();
            if ($conn->errno)
                SendResult(204, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        }
        // dan het stuk
        $cmd =
            sprintf('delete from %s where %s=? ', tblStuk, vnmId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $stuk->id);
            $statement->execute();
            if ($conn->errno)
                SendResult(205, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
            SendJsonObject($stuk);
        } else
            SendResult(206, "Fout in prepare");
    }
    $conn->close();
}

function OphogenAchterliggendeNummers($aAlbumId, $aNr, $stukId) {
    // selecteer alle achterliggende stukken
    $conn = getDBConnection();
    if ($conn->errno)
        SendResult(207, 'Fout ' . $conn->errno . ' ' . $conn->error);
    else {
        $cmd = sprintf('select %s from %s where (%s = ?) and (%s >= ?) and (%s <> %d) order by %s', vnmId, tblStuk, vnmAlbumId, vnmNr, vnmId, $stukId, vnmNr);
        $statement = $conn->prepare($cmd);
        $statement->bind_param('ii', $aAlbumId, $aNr);
        $statement->execute();
        $rs = $statement->get_result();
        $updatestatement = $conn->prepare(sprintf('update %s set %s = ? where %s = ?', tblStuk, vnmNr, vnmId));
        $updatestatement->bind_param('ii', $nr, $id);
        $nr = $aNr;
        while ($row = $rs->fetch_array(MYSQLI_NUM)) {
            $id = $row[0];
            $nr = $nr + 1;
            $updatestatement->execute();
        }
        $updatestatement->close();
        $statement->close();
    }
    $conn->close();
}

class StukId
{
    public $stukId;
}


function GetVolgendStuk($aStukId, $aVorige) {
    $conn = getDBConnection();
    if ($conn->errno)
        SendResult(208, 'Fout ' . $conn->errno . ' ' . $conn->error);
    else {
        $cmd = sprintf('select %s, %s from %s where %s = ?', vnmAlbumId, vnmNr, tblStuk, vnmId);
        $statement = $conn->prepare($cmd);
        $statement->bind_param('i', $aStukId);
        $statement->execute();
        $rs = $statement->get_result();
        if ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myStuk = new StukId();
            $cmd2 = sprintf('select * from %s where (%s = ?) and ', tblStuk, vnmAlbumId);
            if ($aVorige)
                $cmd2 .= sprintf('(%s < ?) order by %s desc', vnmNr, vnmNr);
            else
                $cmd2 .= sprintf('(%s > ?) order by %s', vnmNr, vnmNr);
            $cmd2 .= ' limit 1';
//            error_log($cmd2 . "\n", 3, 'c:\tijdelijk\phplog.txt');
            MyLog($cmd2);
            $statement2 = $conn->prepare($cmd2);
            $statement2->bind_param('ii', $row[vnmAlbumId], $row[vnmNr]);
            $statement2->execute();
            $rs2 = $statement2->get_result();
            if ($row2 = $rs2->fetch_array(MYSQLI_ASSOC))
                $myStuk->stukId = $row2[vnmId];
            else
                $myStuk->stukId = -1;
            $statement2->close();
            return $myStuk;
        } else
            SendResult(209, sprintf('Stuk met id %d niet gevonden ', $aStukId));
        $statement->close();
    }
}

?>