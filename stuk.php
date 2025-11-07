<?php
require_once('apiProcs.php');
require_once('apiConstants.php');
require_once('apiConstants.php');

class Stuk
{
    public $id;
    public $titel;
    public $map;
    public $album;
    public $albumId;
    public $auteur;
    public $auteurId;
    public $aantalPaginas;
}


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
    if (($stuk->auteur != '') && (!isset($stuk->auteurId) || $stuk->auteurId == 0)) {
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
            sprintf(', %s=? ', vnmAuteurId) .
            sprintf(', %s=? ', vnmAlbumId) .
            sprintf(', %s=? ', vnmNr) .
            sprintf('where %s=?', vnmId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('siiii', $stuk->titel, $stuk->auteurId, $stuk->albumId, $stuk->nr, $stuk->id);
            $statement->execute();
            if ($conn->errno)
                SendResult(204, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        } else
            SendResult(201, "Fout in prepare");
    } else {
        $cmd = sprintf('insert into %s (%s, %s, %s, %s) values (?, ?, ?, ?)', tblStuk, vnmTitel, vnmAuteurId, vnmAlbumId, vnmNr);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('siii', $stuk->titel, $stuk->auteurId, $stuk->albumId, $stuk->nr);
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

    $myStukVersieId = bepaalStukVersieId($conn, $stuk->id, $stuk->versie);

    if ($myStukVersieId > 0) { // stukversie wijzigen
        $cmd =
            sprintf('update %s set ', tblStukVersie) .
            sprintf('  %s=? ', vnmMap) .
            sprintf('where %s=?', vnmId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('si', $stuk->map, $myStukVersieId);
            $statement->execute();
            if ($conn->errno)
                SendResult(204, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        } else
            SendResult(201, "Fout in prepare");
    } else { // stukversie toevoegen
        $cmd = sprintf('insert into %s (%s, %s, %s) values (?, ?, ?)', tblStukVersie, vnmStukId, vnmVersieNr, vnmMap);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('iis', $stuk->id, $stuk->versie, $stuk->map);
            $statement->execute();
            if ($conn->errno)
                SendResult(203, 'Fout ' . $conn->errno . ' ' . $conn->error);
            else {
                $rs = $conn->query('select last_insert_id() as id');
                $row = $rs->fetch_array(MYSQLI_ASSOC);
                $myStukVersieId = $row['id'];
            }
            $statement->close();
        } else
            SendResult(202, " Fout in prepare " . $cmd . ' ' . $conn->error);
    }

    if (isset($stuk->paginas)) {
        $cmd = sprintf('delete from %s where %s = ?', tblPagina, vnmStukVersieId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $myStukVersieId);
            $statement->execute();
            if ($conn->errno)
                SendResult(203, 'Fout ' . $conn->errno . ' ' . $conn->error);
            $statement->close();
        }
        $cmd = sprintf('insert into %s (%s, bestandsnaam, paginaNr) values (?, ?, ?)', tblPagina, vnmStukVersieId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            foreach ($stuk->paginas->items as $myPagina) {
                $statement->bind_param('isi', $myStukVersieId, $myPagina->bestandsnaam, $myPagina->paginanr);
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

function VerwijderStukVersie($aData) {
    $stuk = json_decode($aData, false);
    $myResponse = new stdClass();
    $conn = getDBConnection();
    $myStukVersieId = bepaalStukVersieId($conn, $stuk->stukId, $stuk->versie);
    if ($myStukVersieId == 0) 
        SendResult(errNietGevonden, "Stukversie van stuk $stuk->stukId versie $stuk->versie niet gevonden");
    else {
// eerst paginas verwijderen
        $cmd =
            sprintf('delete from %s where %s=? ', tblPagina, vnmStukVersieId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $myStukVersieId);
            $statement->execute();
            if ($conn->errno)
                SendResult(204, 'Fout ' . $conn->errno . ' ' . $conn->error);
            else
                $myResponse->aantalPaginasVerwijderd = $statement->affected_rows;
            $statement->close();
        }

// vervolgens de stukversie verwijderen
        $cmd =
            sprintf('delete from %s where %s=? ', tblStukVersie, vnmId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $myStukVersieId);
            $statement->execute();
            if ($conn->errno)
                SendResult(205, 'Fout ' . $conn->errno . ' ' . $conn->error);
            else
                $myResponse->stukVersieVerwijderd = true;
        }
        $statement->close();

        // controleer of er nog stukversies over zijn
        $cmd = sprintf('select count(*) as aantal from %s where %s=? ', tblStukVersie, vnmStukId);
        $statement = $conn->prepare($cmd);
        if ($statement) {
            $statement->bind_param('i', $stuk->stukId);
            $statement->execute();
            $rs = $statement->get_result();
            if ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
                if ($row['aantal'] == 0) {
                    // geen stukversies meer over, dan ook het stuk verwijderen
                    $cmd =
                        sprintf('delete from %s where %s=? ', tblStuk, vnmId);
                    $statement = $conn->prepare($cmd);
                    if ($statement) {
                        $statement->bind_param('i', $stuk->stukId);
                        $statement->execute();
                        if ($conn->errno)
                            SendResult(205, 'Fout ' . $conn->errno . ' ' . $conn->error);
                        $myResponse->verwijderdStukId = $stuk->stukId;
                        $statement->close();
                    } else
                        SendResult(206, "Fout in prepare");
                } 
            } 
        } else
            SendResult(206, "Fout in prepare");
        SendJsonObject($myResponse);
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

function CreateStukFromRecord($aRow) {
    $myStuk = new Stuk();
    $myStuk->id = $aRow["id"];
    $myStuk->titel = $aRow["titel"];
    $myStuk->map = $aRow["map"];
    $myStuk->album = $aRow["album"];
    $myStuk->albumId = $aRow["albumId"];
    $myStuk->nr = $aRow["nr"];
    $myStuk->auteur = $aRow["auteur"];
    $myStuk->auteurId = $aRow["auteurId"];
    $myStuk->aantalPaginas = $aRow["aantalPaginas"];
    return $myStuk;
}

function getStuk($aStukId, $aVersie)
{
    $conn = getDBConnection();
    if ($conn != null) {
        $sql = 'select s.id, sv.id as stukVersieId, sv.map, s.titel, s.auteurId, s.albumId, s.nr, s.opmerkingen, a.naam as album, au.naam as auteur, ' .
               sprintf('(select count(*) from %s p where p.stukVersieId = sv.id) as aantalPaginas ', tblPagina) .
               sprintf('from %s sv ', tblStukVersie) .
               sprintf('join %s s on s.id = sv.stukId and sv.versieNr = ? ', tblStuk) .
               sprintf('left join %s a on a.Id = s.albumId ', tblAlbum) .
               sprintf('left join %s au on au.Id = s.auteurId ', tblAuteur) .
               'where s.id = ?';
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $aVersie, $aStukId);
            $stmt->execute();
            $rs = $stmt->get_result();
            
            if ($rs) {
                if ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
                    $myStuk = CreateStukFromRecord($row);
                    $myStuk->versie = $aVersie;
                    $myStuk->beschikbareVersies = getBeschikbareVersies($aStukId);
                    SendJsonObject($myStuk);
                } else {
                    SendResult(errNietGevonden, "Stuk $aStukId met versie $aVersie niet gevonden");
                }
            } else {
                SendResult(123, "Fout bij uitvoeren query: " . $conn->error);
            }
            $stmt->close();
        } else {
            SendResult(123, "Fout bij prepare statement: " . $conn->error);
        }
        $conn->close();
    }
}

function getBeschikbareVersies($aStukId)
{
    $conn = getDBConnection();
    if ($conn != null) {
        $sql = sprintf('select versieNr from %s ', tblStukVersie) .
               sprintf('where %s = ? ', vnmStukId) .
               'order by versieNr';
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $aStukId);
            $stmt->execute();
            $rs = $stmt->get_result();
            
            if ($rs) {
                $myVersies = array();
                while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
                    array_push($myVersies, $row['versieNr']);
                }
                $stmt->close();
                $conn->close();
                return $myVersies;
            } else {
                $stmt->close();
                $conn->close();
                return null;
            }
        } else {
            $conn->close();
            return null;
        }
    }
}   

function bepaalStukVersieId($aConn, $aStukId, $aVersieNr)
{
    $sql = sprintf('select %s from %s ', vnmId, tblStukVersie) .
            sprintf('where (%s = ?) and (%s = ?)', vnmStukId, vnmVersieNr);
    
    $stmt = $aConn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $aStukId, $aVersieNr);
        $stmt->execute();
        $stmt->bind_result($myStukVersieId);
        $myResult = 0;
        if ($stmt->fetch()) 
            $myResult = $myStukVersieId;
        $stmt->close();
            return $myResult;
        }
    else 
        SendResult(errDatabase, "Fout bij prepare statement in bepaalStukVersieId: " . $aConn->error);
}   

?>