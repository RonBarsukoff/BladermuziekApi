<?php
require_once('apiProcs.php');
require_once('apifuncties.php');

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

sendHeaders();

$pagina = getGetVar('pagina');
$postData = file_get_contents('php://input');
verwerkRequest($pagina, $postData);

?>