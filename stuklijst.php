<?php
require_once('apiConstants.php');
require_once('apiProcs.php');
/*
function getLijst()
{
	$files = scandir(getDataMap());
	$r = '';
	for ($i = 0; $i <= count($files) - 1; $i++) {
		$dir = $files[$i];
		if (($dir != '.') && ($dir != '..'))
			$r = $r . $dir . RegelEinde;
	}
	;
	return $r;
}
*/

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

class Stukken
{
    public $items = array();
}


function getStukLijst($aSortOrder, $aAlbumFilter, $aAuteurFilter)
{
    $conn = getDBConnection();
    $cmd =
        'select s.id, s.map, s.titel, s.auteurId, s.albumId, s.nr, s.opmerkingen, a.naam as album, au.naam as auteur, ' .
        sprintf('(select count(*) from %s p where p.stukId = s.id) as aantalPaginas from %s s ', tblPagina, tblStuk) .
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
            array_push($myStukken->items, $myStuk);
        }
        $conn->close();
        return $myStukken;
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

?>
