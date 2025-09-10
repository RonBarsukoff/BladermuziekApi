<?php
require_once('apiConstants.php');
require_once('apiProcs.php');

class Map
{
    public $naam;
}

class Mappen
{
    public $items = array();
}

function getMaplijst()
{
    $myMappen = new Mappen();
    $myDir = getDataMap();
    foreach (new DirectoryIterator($myDir) as $fileInfo) {
        if (!$fileInfo->isDot()) {
            $myMap = new Map();
            $myMap->naam = $fileInfo->getFilename();
            array_push($myMappen->items, $myMap);

        }
    }
    SendJsonObject($myMappen);
}

class Filenames
{
    public $items = array();
}

function getImageFilenames($aMap) {
    $myMap = getDataMap() . '/' . $aMap;
    $myFilenames = new Filenames();
    $myFilenames->items = scandir($myMap);
    array_splice($myFilenames->items, 0, 2);
    SendJsonObject($myFilenames);
}

?>