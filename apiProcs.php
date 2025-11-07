<?php
require_once('config.php');

function getDataMap()
{
//	return $_SERVER['DOCUMENT_ROOT'] . '/Bladeren/data';
    return DataMap;
}

function getDBConnection() {
    $conn = new mysqli(dbServer, dbUser, dbPassword, dbSchema);

	if ($conn -> connect_errno) {
		SendResult(101, "Fout bij het verbinden met MySQL: " . $conn -> connect_error);
	}
    return $conn;
}

function SendJsonObject($aObject) {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    echo(json_encode($aObject));
}

class Foutmelding {
    public $foutmelding;
    public $id;
    public $omschrijving;
}
function SendResult($aId, $aOmschrijving) {
    $melding = new Foutmelding();
    $melding->foutmelding = true;
    $melding->id = $aId;
    $melding->omschrijving = $aOmschrijving;
    SendJsonObject($melding);
    exit(0);
}

function fullMapPad($aMap) {
    $myFullPath = getDataMap() . DIRECTORY_SEPARATOR . $aMap;
    return $myFullPath;
}
function MapBestaat($aMap) {
    $s = fullMapPad($aMap);
    return is_dir($s);
}

function MaakMap($aMap) {
    mkdir(fullMapPad($aMap), 0777, true);
}

function AddToWhereClause($aClause, $aValue) {
    if ($aClause == '')
        $aClause = 'where ';
    else
        $aClause = $aClause . ' and ';
    return $aClause . $aValue;
}

function MyLog($aMelding)
{
    if (LogFilename != '')
        error_log($aMelding . "\n", 3, LogFilename);
}

function getGetVar($aParam, $aDefault = '')
{
	if (isset($_GET[$aParam]))
		return $_GET[$aParam];
	else
		return $aDefault;
}

function getPostVar($aParam)
{
    if (isset($_POST[$aParam]))
        return $_POST[$aParam];
    else
        return '';
}

function getCookieVar($aParam)
{
    if (isset($_COOKIE[$aParam]))
        return $_COOKIE[$aParam];
    else
        return '';
}

function getServerDatumTijd() {
    header("Content-Type: text/plain; charset=UTF-8");
    echo date("Y-m-d H:i:s");
}

function sendHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    // Handle CORS preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}
?>