<?php
require_once('apiProcs.php');
require_once('apiConstants.php');


class Pagina
{
    public $paginanr;
    public $bestandsnaam;
    public $datumtijd;
    public $hoogte;
    public $breedte;
}

class Paginas
{
    public $paginas;

    function __construct()
    {
        $this->paginas = array();
    }
}

function getPaginas($aStukId, $aVersie = 1)
{
    $conn = getDBConnection();
    if ($conn == null) {
        echo 'Fout bij het openen van de connectie';
    } else {
        $rs = $conn->query("
            select p.paginanr, p.bestandsnaam, s.map from pagina p
            join stukVersie sv on sv.id = p.stukVersieId and sv.versieNr = $aVersie
            join stuk s on s.id = sv.stukId
            where sv.stukId = $aStukId
        ");
        $myPaginas = new Paginas();
        while ($row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $myPagina = new Pagina();
            $myPagina->paginanr = $row[vnmPaginaNr];
            $myPagina->bestandsnaam = $row['bestandsnaam'];
            $full_file_name = getDataMap() . '/' . $row['map'] . '/' . $row['bestandsnaam'];
            getImageFileInfo($full_file_name, $myPagina);
            array_push($myPaginas->paginas, $myPagina);
        }
        $conn->close();
        return $myPaginas;
    }
}

function getPaginasInMap($aMap)
{
    $myMap = getDataMap() . '/' . $aMap;
    $myFilenames = scandir($myMap);
    $myPaginas = new Paginas();
    foreach ($myFilenames as $myFilename) {
        if (($myFilename != '.') && ($myFilename != '..')) {
            $myPagina = new Pagina();
            $myPagina->bestandsnaam = $myFilename;
            $full_file_name = getDataMap() . '/' . $aMap . '/' . $myFilename;
            getImageFileInfo($full_file_name, $myPagina);
            array_push($myPaginas->paginas, $myPagina);
        }
    }
    SendJsonObject($myPaginas);
}

function getImageFileInfo($aFilename, $aPagina) {
    $aPagina->datumtijd = date("Y-m-d H:i:s", filemtime($aFilename));
//    ini_set("gd.jpeg_ignore_warning", 1); // werkt niet 
    $myPreviousErrorReporting = error_reporting();
    error_reporting(E_ERROR);
    $a = getimagesize($aFilename);
    if ($a) {
        $aPagina->hoogte = $a[1];
        $aPagina->breedte = $a[0];
    }
    error_reporting($myPreviousErrorReporting);
}

?>
