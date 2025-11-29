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


function voegMappenToe($pad, $relatiefPad, &$items) {
    $directories = array();
    
    // Verzamel eerst alle directories
    foreach (new DirectoryIterator($pad) as $fileInfo) {
        if (!$fileInfo->isDot() && $fileInfo->isDir()) {
            $directories[] = $fileInfo->getFilename();
        }
    }
    
    // Sorteer alfabetisch
    sort($directories);
    
    // Voeg toe in alfabetische volgorde
    foreach ($directories as $mapNaam) {
        $volgendeRelatiefPad = $relatiefPad === '' ? $mapNaam : $relatiefPad . '/' . $mapNaam;
        $myMap = new Map();
        $myMap->naam = $volgendeRelatiefPad;
        array_push($items, $myMap);
        voegMappenToe($pad . '/' . $mapNaam, $volgendeRelatiefPad, $items);
    }
}

function getMaplijst()
{
    $myMappen = new Mappen();
    $myDir = getDataMap();
    voegMappenToe($myDir, '', $myMappen->items);
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