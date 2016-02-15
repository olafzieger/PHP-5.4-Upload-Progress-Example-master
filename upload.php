<?php
session_start();
header('Content-type: application/json');
/* Die vom Server zugelassene Größe der uploadbaren
 * Dateimenge geben lassen.
 * Und die Menge der zugelassenen Dateien für einen
 * Upload geben lassen (max_file_uploads).
 * */
$displayMaxSize = ini_get('post_max_size');
$displayMaxFileUploads = ini_get('max_file_uploads');

/* Ersetzung durch eine übliche Einheitsangabe. */
switch(substr($displayMaxSize,-1))
{
    case 'G':
        $displayMaxSize = substr($displayMaxSize, 0, -1) . ' Gigabyte';
        break;
    case 'M':
        $displayMaxSize = substr($displayMaxSize, 0, -1) . ' Megabyte';
        break;
    case 'K':
        $displayMaxSize = substr($displayMaxSize, 0, -1) . ' Kilobyte';
        break;
}

/* Meldungstexte für $_FILES['userfile']['error']. */
$uploadMeldung = array(
    0   => 'Datei wurde erfolgreich hochgeladen.',
    1   => 'Die hochgeladene Datei überschreitet die festgelegte Größe von ' . $displayMaxSize . '.',
    2   => 'Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigröße.',
    3   => 'Die Datei wurde leider nur teilweise hochgeladen und ist beschädigt.',
    4   => 'Es wurde keine Datei hochgeladen, scheinbar ist keine ausgewählt.',
    5   => 'Sie haben versucht Dateien mit ungültigen Zeichen in den Dateinamen hochzuladen. Bitte verwenden Sie keine Sonderzeichen in Ihren Dateinamen.',
    6   => 'Der temoräre Ordner des Webservers konnte nicht gefunden werden.',
    7   => 'Das Speichern der Datei auf der Serverfestplatte ist fehlgeschlagen.',
    8   => 'Eine Servererweiterung hat den Uploadvorgang der Datei gestopt.',
    9   => 'Das Zielverzeichnis zum speichern Iherer Daten konnte nicht gefunden werden.',
    10  => ''
);

/* Absoluter Pfad zum Upload Zielverzeichnis */
/*$target_path = "/Library/Server/Web/Data/Sites/pdf_xml_bilder/";*/
$target_path = "/Users/olaf/uploads/";

/* Nach einem erfolgreichem Upload in das temporäre Verzeichnis
 * die Dateien weiterverarbeiten, z. B. bewegen an den Zielort etc.
 * */
$uploadedFiles   =   array(
    'files'      =>  array(),
    'moveError'  =>  '',
    'uploadError'=>  ''
);

if(isset($_FILES['files'])) {
    $name_array = $_FILES['files']['name'];
    $tmp_name_array = $_FILES['files']['tmp_name'];
    $type_array = $_FILES['files']['type'];
    $size_array = $_FILES['files']['size'];
    $error_array = $_FILES['files']['error'];

    for($i = 0; $i < count($tmp_name_array); $i++){

        /* TODO: Parameter für den Dateinamen aus URL abfragen z.B.:
         * dev.runze-casper.de/xmlbildereutin/index.php?nummer=2.5
         * Und wenn dann in $uploadedFiles['files'][$i]['fileName']
         * für das Response in das UI übergeben.
         * */
        if(move_uploaded_file($tmp_name_array[$i], $target_path . $name_array[$i])){
            /* Bei einem erfolgreichem Speicher/Verschieben wird
             * es an dieser Stelle im Arry $uploadedFiles notiert.
             * Ansonsten wird im Elsezweig die entsprechende Fehler-
             * meldung gespeichert und der index.php übergeben.
             * ************************************************ */

            /* TODO: erlaubte Dateitypen für InDesign mitels ($type_array) abfragen und
             * in $uploadedFiles['files'][$i]['fileStatus'] als Fehler ausgeben.
             * */

            $uploadedFiles['files'][$i]['fileName']     = $name_array[$i];
            $uploadedFiles['files'][$i]['fileTmpName']  = $tmp_name_array[$i];
            $uploadedFiles['files'][$i]['fileType']     = $type_array[$i];
            $uploadedFiles['files'][$i]['fileSize']     = $size_array[$i];
            $uploadedFiles['files'][$i]['fileStatus']   = $uploadMeldung[$error_array[$i]];

        } else {
            /* Gibt den Grund als Fehlermeldungaus weshalb diese
             * Datei nicht auf dem Server gespeichert werden konnte
             * */
            $uploadedFiles['moveError'] = $uploadMeldung[7];

            /*echo json_encode($uploadedFiles['moveError']);*/

            $uploadedFiles['files'][$i] = $name_array[$i] . ' '
                . round(($size_array[$i]/1024/1024), 2) . ' MB, '
                . 'Die Datei konnte zwar hochgeladen werden, es ist aber '
                . 'leider folgender Fehler aufgetreten: <br>' . $uploadMeldung[7];
        }
    }
    /* Übergabe der Meldungen zum Upload. */
    echo json_encode($uploadedFiles);
}

/* Wenn der Server den Upload nicht ausführt und dieses nur als Warnung
 * in das Serverlog schreibt wie z. B. so:
 *      "PHP Warning:  POST Content-Length of 1047900745 bytes exceeds
 *       the limit of 838860800 bytes in Unknown on line 0"
 * wird dies als Information hier im JSON an die index.php zurückgegeben.
 * */
if($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) &&
    empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {

    $error  =   'Die von Ihnen gesendeten Daten haben aber ' .
        round(($_SERVER['CONTENT_LENGTH']/1024/1024), 2) .
        ' Megabyte.';

    $uploadedFiles['uploadError'] = $uploadMeldung[1] . ' ' . $error;

    echo json_encode($uploadedFiles);
    /*print_r($uploadedFiles);*/
}
?>