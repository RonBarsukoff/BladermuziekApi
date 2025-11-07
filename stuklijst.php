<?php
require_once('apiConstants.php');
require_once('apiProcs.php');
require_once('stuk.php');

class Stukken
{
    public $items = array();
}


function getStukLijst($aVersie, $aSortOrder, $aAlbumFilter, $aAuteurFilter)
{
    $conn = getDBConnection();
    $cmd =
        'select s.id, sv.id as stukVersieId, sv.map, s.titel, s.auteurId, s.albumId, s.nr, s.opmerkingen, a.naam as album, au.naam as auteur, ' .
        sprintf('(select count(*) from %s p where p.stukVersieId = sv.id) as aantalPaginas ', tblPagina) .
        sprintf('from %s sv ', tblStukVersie) .
        sprintf('join %s s on s.id = sv.stukId and sv.versieNr = %d ', tblStuk, $aVersie) .
        sprintf('left join %s a on a.Id = s.albumId ', tblAlbum) .
        sprintf('left join %s au on au.Id = s.auteurId', tblAuteur);
    $myWhere = '';
    if ($aAlbumFilter != '')
        $myWhere = AddToWhereClause($myWhere, sprintf('a.naam = "%s"', $aAlbumFilter));
    if ($aAuteurFilter != '')
        $myWhere = AddToWhereClause($myWhere, sprintf('au.naam = "%s"', $aAuteurFilter));
    $cmd .= ' ' . $myWhere;
    if (!($aAlbumFilter == '')) {
        $cmd .= sprintf(' order by %s', vnmNr);
    }
    elseif (!($aSortOrder == '')) {
        $cmd = $cmd . sprintf(' order by %s', $aSortOrder);
        if ($aSortOrder == 'album')
            $cmd .= sprintf(', %s', vnmNr);
    }

    $rs = $conn->query($cmd);
    if (!$rs)
        SendResult(2, "rs is false in getStukLijst");
    else {
        $myStukken = new Stukken;
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myStuk = new Stuk();
            $myStuk->id = $row["id"];
            $myStuk->titel = $row["titel"];
            $myStuk->map = $row["map"];
            $myStuk->album = $row["album"];
            $myStuk->albumId = $row["albumId"];
            $myStuk->nr = $row["nr"];
            $myStuk->auteur = $row["auteur"];
            $myStuk->auteurId = $row["auteurId"];
            $myStuk->aantalPaginas = $row["aantalPaginas"];
            $myStuk->versie = $aVersie;
            array_push($myStukken->items, $myStuk);
        }
        $conn->close();
        return $myStukken;
    }
}

?>
