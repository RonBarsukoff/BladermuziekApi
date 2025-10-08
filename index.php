<?php
require_once('apiProcs.php');

$pagina = getGetVar('pagina');
if ($pagina == 'stuklijst') {
    require_once('stuklijst.php');
    $myStukken = getStukLijst(getGetVar('versie', 1), getGetVar('sortorder'), getGetVar('album'), getGetVar('auteur'));
    SendJsonObject($myStukken);
} elseif ($pagina == 'albumlijst') {
    require_once('albumlijst.php');
    getAlbumlijst();
} elseif ($pagina == 'getStuk') {
    require_once('stuklijst.php');
    getStuk(getGetVar('stukId', 0), getGetVar('versie', 1));
} elseif ($pagina == 'auteurlijst') {
    require_once('auteurlijst.php');
    getAuteurlijst();
} elseif ($pagina == 'maplijst') {
    require_once('maplijst.php');
    getMaplijst();
} elseif ($pagina == 'paginas') {
    $stukid = getGetVar('stukid');
    if ($stukid != '') {
        require_once('paginas.php');
        SendJsonObject(getPaginas($stukid, getGetVar('versie', 1)));
    }
} elseif ($pagina == 'ImageFilenames') {
    $map = getGetVar('map');
    if ($map != '') {
        require_once('maplijst.php');
        getImageFilenames($map);
    }
} elseif ($pagina == 'pagina') {
    $stukId = getGetVar('stukId');
    $versie = getGetVar('versie', 1);
    $paginaNr = getGetVar('paginaNr');
	require_once('pagina.php');
    getPagina($stukId, $versie, $paginaNr, getGetVar('hoogte'), getGetVar('breedte'));
} elseif ($pagina == 'paginaByName') {
    $naam = getGetVar('naam');
    $map = getGetVar('map');
    require_once('pagina.php');
    getPaginaByName($naam, $map, getGetVar('hoogte'), getGetVar('breedte'));
}
elseif ($pagina == 'postStuk') {
    $postData = getPostVar('stuk');
    require_once('stuk.php');
    PostStuk($postData);
} elseif ($pagina == 'verwijderStuk') {
    $postData = getPostVar('stuk');
    require_once('stuk.php');
    VerwijderStuk($postData);
} elseif ($pagina == 'serverdatumtijd') {
    getServerDatumTijd();
}
elseif ($pagina == 'getPaginasInMap') {
    $map = getGetVar('map');
    if ($map != '') {
        require_once('paginas.php');
        getPaginasInMap($map);
    }
}
elseif ($pagina == 'getVolgendeStuk') {
    $aStukId = getGetVar('StukId');
    $aVorige = getGetVar('Vorige') == 'J';
    require_once('stuk.php');
    SendJsonObject(GetVolgendStuk($aStukId, $aVorige));
} 
elseif ($pagina == 'postAuteur') {
    $postData = getPostVar('auteur');
    require_once('auteurlijst.php');
    postAuteur($postData);
}
elseif ($pagina == 'postAlbum') {
    $postData = getPostVar('album');
    require_once('albumlijst.php');
    postAlbum($postData);
}
else {
#    http_response_code(404);
#    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    SendResult(1, "ongeldige pagina");
}

function getServerDatumTijd() {
    echo date("Y-m-d H:i:s");
}
?>