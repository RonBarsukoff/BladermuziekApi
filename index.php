<?php
require_once('apiProcs.php');

// Log incoming headers voor debugging
// MyLog("=== REQUEST DEBUG ===");
// MyLog("Request method: " . $_SERVER['REQUEST_METHOD']);
// MyLog("Content-Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'NOT SET'));
// MyLog("Content-Length: " . (isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 'NOT SET'));
// MyLog("POST data count: " . count($_POST));
// if (!empty($_POST)) {
//     MyLog("POST keys: " . implode(', ', array_keys($_POST)));
// }
// MyLog("Raw input length: " . strlen(file_get_contents('php://input')));

$pagina = getGetVar('pagina');
if ($pagina == 'stuklijst') {
    require_once('stuklijst.php');
    $myStukken = getStukLijst(getGetVar('versie', 1), getGetVar('sortorder'), getGetVar('album'), getGetVar('auteur'));
    SendJsonObject($myStukken);
} elseif ($pagina == 'albumlijst') {
    require_once('albumlijst.php');
    getAlbumlijst();
} elseif ($pagina == 'getStuk') {
    require_once('stuk.php');
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
    if ($postData != '') {
        require_once('stuk.php');
        PostStuk($postData);
    }
    else {
        http_response_code(406);
    }
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
elseif ($pagina == 'stukTabel') {
    require_once('tabellen.php');
    sendStukTabel();
}
elseif ($pagina == 'stukVersieTabel') {
    require_once('tabellen.php');
    sendStukVersieTabel();
}
elseif ($pagina == 'paginaTabel') {
    require_once('tabellen.php');
    sendPaginaTabel();
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