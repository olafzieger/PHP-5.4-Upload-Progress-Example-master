<?php
session_start();
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
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="css/bootstrap-theme.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Bootstrap E N D E -->
    <style>
        progress {
            margin:10px 0 10px 0;
        }
        body {
            /* Abstand oben wegen der festen Navbar */
            padding-top: 40px;
        }
        .glyphicon-spin {
            -webkit-animation: spin 1000ms infinite linear;
            animation: spin 1000ms infinite linear;
        }
        @-webkit-keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(359deg);
                transform: rotate(359deg);
            }
        }
        @keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(359deg);
                transform: rotate(359deg);
            }
        }
        .status {
            width: 30px;
            white-space: nowrap;
        }
        .filesize {
            width: 90px;
        }
    </style>
        <title>Relaunch des R&C Datentransferservers</title>
</head>
<body>

<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Menü ein/aus</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Runze & Casper GmbH</a>
        </div>
        <!-- Die Nav-Links, Formulare und andere Inhalte für das Umschalten zu sammeln -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li><a href="admin">Transferübersicht</a></li>
                <li><a href="#">Hilfe/FAQ</a></li>
                <li><a href="#">Kontakt zum Support</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12 col-md-8">

            <h2>Datentransferserver</h2>
            <p>Select one file to upload (Max total size <?=$displayMaxSize;?>).</p>
            <p>
                Bitte nur Maximal <?=$displayMaxFileUploads;?> Dateien auswählen. Um mehrere Dateien zu selektieren
                halten Sie in OS&nbsp;X&nbsp;<kbd>cmd&nbsp;⌘</kbd> bzw. in Windows&nbsp;<kbd>Strg</kbd> beim auswählen gedrückt.
            </p>
            <form action="upload.php" method="POST" enctype="multipart/form-data" id="upload" class="form-horizontal">
                <input type="hidden" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="upload" />

                <div class="form-group">
                    <label for="files" class="col-sm-3 control-label">Bildatei für Ihren Eintrag</label>

                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-btn">
                                <input id="submit" type="submit" class="btn btn-primary" value="Upload"/>
                            </span>
                            <input class="form-control" type="file" name="files[]" id="files" multiple/>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="progress" class="col-sm-3 control-label">Fortschritt</label>

                    <div class="col-sm-9">

                            <div class="progress">
                                <div class="progress-bar" id="progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>

                    </div>
                </div>

            </form>

            <div id="error" role="alert"></div>

            <div id="progress-txt" role="alert"></div>
            <div>
                <table id="fileslist" class="table table-striped"></table>
            </div>
        </div>
    </div>
</div>




<!-- File containing Jquery and the Jquery form plugin-->
<script src="js/jquery-1.11.3.min.js"></script>
<script src="js/jquery.form.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>
<script>

    // Holds the id from set interval
    var interval_id = 0;
    var uploadCallback = '';
    var progressRepeats = 0;
    var displayMaxFileUploads = <?=$displayMaxFileUploads;?>;

    $(document).ready(function(){

        $('[data-toggle="tooltip"]').tooltip();

        $('#files').change(function(){
            if($(this).val() != ''){
                /* Prüfen ob Daten vom Benutzer ausgewählt worden sind.
                 * Wenn dann die Daten vom Benutzer ausgewählt wurden
                 * Fehlermeldung wieder leeren. Und eine Liste der Auswahl
                 * ausgeben.
                 * */
                $('#error').html('').removeClass();

                /* TODO: erlaubte Dateitypen für InDesign mitels ('Dateityp: ' + selectedfiles[f].type)
                 * abfragen und in der #filesList als glyphicon-alert als nicht erlaubt markieren.
                 * */

                var selectedFileList = '';
                var fileListSize = 0;
                var selectedfiles = $('#files')[0].files;
                for(var f = 0; f < selectedfiles.length; f++) {
                    selectedFileList    += '<tr id="' + f + '">'
                                            +'<td class="text-left status">' +
                                                '<span class="glyphicon glyphicon-cloud-upload text-info" aria-hidden="true"></span>'
                                            +'</td>'
                                            +'<td class="text-right filesize">' +
                                                 extround((selectedfiles[f].size/1024/1024), 100) + '&nbsp;MB'
                                            +'</td>'
                                            +'<td>' +
                                                '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' +
                                                '&nbsp;' + selectedfiles[f].name
                                            +'</td>'
                                        +'</tr>';

                    fileListSize += selectedfiles[f].size;
                }
                $('#fileslist').html(selectedFileList).parent().addClass('table-responsive');
                $('#progress-txt').html('Die von Ihnen agewählten Dateien haben gesamt ' + extround((fileListSize/1024/1024), 100) + ' MB.' +
                                        ' Klicken Sie auf Upload um den Prozess zu starten oder ändern Sie Ihre Auswahl.')
                                  .removeClass().addClass('alert alert-info');
            }
            progressRepeats = 0;

        });

        // Add the submit handler to the form
        $('#upload').submit(function(){

            $('#submit').attr('disabled', true);

            $('#upload').ajaxSubmit({
                // Optionen für den jQuery-Ajax-Commit:
                beforeSubmit:   beforeSubmit,
                dataType:       'json',
                success:        uploadResponse,
                resetForm:      true,
                error:          $('#progress-txt').load('upload.php', function(response, status, xhr) {
                    if(status == 'error') {
                        var msg = '<span class="glyphicon glyphicon-alert"></span> Leider ist folgender Fehler (#upload42) aufgetreten:  ';

                        $('#progress-txt').html(msg + '<strong>' + xhr.status + ' - ' + xhr.statusText + '</strong>')
                                          .removeClass().addClass('alert alert-danger');
                        $('#fileslist').html('').parent().removeClass();
                    }
                })
            });

            function beforeSubmit() {
                /* Prüfen ob Daten vom Benutzer ausgewählt worden sind. */
                if ($('#files').val() == '') {
                    $('#error').html('<span class="glyphicon glyphicon-alert"></span> Bitte wählen Sie Daten von Ihrem Rechner aus die hochgeladen werden sollen.')
                               .removeClass().addClass('alert alert-danger');
                    $('#progress-txt').removeClass();

                    return false;
                }
            }

            function uploadResponse(data) {
                /* Response der upload.php (JSON) auswerten
                 * und prüfen ob alles hochgeladen wurde.
                 * ggf. entsprechende Fehlermeldung ausgeben.  <span class="glyphicon glyphicon-info-sign text-danger" aria-hidden="true"></span>
                 * ****************************************** */
                if(data && $('#error').html() == ''){
                    for(var i = 0; i < data.files.length; i++){
                        $('#'+i+' .status').append(' <span class="glyphicon glyphicon-info-sign text-info" data-toggle="tooltip" title="" aria-hidden="true"></span>');
                        $('#'+i+' .status .glyphicon-info-sign').attr('title', data.files[i]['fileStatus']);
                    }
                    $('[data-toggle="tooltip"]').tooltip();

                    uploadCallback = 'okay';
                    /* uploadCallback = 'okay'; das bedeutet upload.php ist erreichbar, [data] ist true
                     * wenn nicht wird weiter unten mit --> if($('#error').html() == '' && uploadCallback == 'okay'){ …
                     * das progress-script ohne Erfolgsmeldung abgebrochen */
                    if(data.uploadError != ''){
                        $('#error').html(data.uploadError)
                                   .prepend('<span class="glyphicon glyphicon-alert"></span> Folgender Fehler ist aufgetreten: ')
                                   .removeClass().addClass('alert alert-danger');
                        $('#progress').width('0%');
                        $('#progress').html('');
                        stopProgress();
                        $('#fileslist').html('').parent().removeClass();
                        $('#progress-txt').html('').removeClass();
                    }
                    if(data.moveError != ''){
                        $('#error').html(data.moveError)
                                   .prepend('<span class="glyphicon glyphicon-alert"></span> Folgender Fehler ist aufgetreten: ')
                                   .removeClass().addClass('alert alert-danger');
                        stopProgress();
                        $('#fileslist').html('').parent().removeClass();
                        $('#progress-txt').html('').removeClass();
                    }
                }
            }

            //Poll the server for progress
            interval_id = setInterval(function() {
                $.getJSON('progress.php', function(data){

                    progressRepeats++;

                    //if there is some progress then update the status
                    if(data && !data.noProgress)
                    {
                        $('#progress').width((data.bytes_processed / data.content_length) * 100 + '%');
                        $('#progress').html(Math.round((data.bytes_processed / data.content_length) * 100) + '% ');

                        if(progressRepeats < 2){
                            $('#progress-txt').html('<strong>Bitte warten!</strong> Es sind <strong id="bytesLoaded"></strong> bereits hochgeladen.')
                                              .removeClass().addClass('alert alert-warning')
                                              .prepend('<span class="glyphicon glyphicon-repeat glyphicon-spin"></span> ');
                        } else {
                            $('#bytesLoaded').html(extround((data.bytes_processed/1024/1024), 100) + ' MB');
                        }

                        var done = "";
                        for(var i = 0; i < data.files.length; i++) {

                            $('#'+i+' .status span:first-child').removeClass().addClass('glyphicon glyphicon-repeat glyphicon-spin text-warning');
                            /* Wenn data.files[i]['done'] true ist der Upload in das temporäre Verzeichnis
                             * des Webservers fertig und kann als okay markiert werden.
                             * */
                            if(data.files[i]['done']) {
                                $('#'+i+' .status span:first-child').removeClass().addClass('glyphicon glyphicon-saved text-success');
                            }
                        }

                    }

                    //if noProgress then this part
                    if(data && data.noProgress) {
                        /* Wenn von der upload.php kein moveError, uploadError in #error
                         * ausgegeben wird und von upload.php der uploadCallback okay ist
                         * wird eine Erfolgsmeldung ausgegeben.
                         * ************************************** */
                        if($('#error').html() == '' && uploadCallback == 'okay'){
                            $('#progress').width('100%');
                            $('#progress').html('100%');
                            if($('table#fileslist tr').length > displayMaxFileUploads){
                                /* 'Sie haben zu viele Dateien ausgewählt, es konnten leider nur die zulässigen ' + displayMaxFileUploads + ' Dateien hochgeladen werden.'; */
                                $('#progress-txt').html('Sie haben zu viele Dateien ausgewählt, es' +
                                                        ' konnten leider nur die' +
                                                        ' zulässigen ' + displayMaxFileUploads + ' Dateien' +
                                                        ' hochgeladen werden. Die übrigen Dateien' +
                                                        ' wurden ignoriert und mit <span class="glyphicon glyphicon-alert text-danger"></span>' +
                                                        ' markiert.')
                                                  .prepend('<span class="glyphicon glyphicon-alert"></span> ')
                                                  .removeClass().addClass('alert alert-danger');

                                var restFiles   = $('table#fileslist tr').length - displayMaxFileUploads;
                                var fileID      = displayMaxFileUploads;
                                for(restFiles > -1; restFiles--;){
                                    $('#'+fileID+' .status span').replaceWith('<span class="glyphicon glyphicon-alert text-danger" ' +
                                                                              'data-toggle="tooltip" title="Diese Datei konnte nicht ' +
                                                                              'hochgeladen werden. Es wurden mehr Dateien als zulässig ' +
                                                                              'ausgewählt"></span>');
                                    fileID++;
                                }
                                $('[data-toggle="tooltip"]').tooltip();
                            } else {
                                $('#progress-txt').html('Alle Daten erfolgreich hochgeladen.')
                                                  .removeClass().addClass('alert alert-success')
                                                  .prepend('<span class="glyphicon glyphicon-saved"></span> ');
                            }
                            $('.status span:first-child').removeClass('glyphicon-repeat glyphicon-spin text-warning')
                                                         .removeClass('glyphicon-cloud-upload text-info')
                                                         .addClass('glyphicon-saved text-success');
                            $('#submit').attr('disabled', false);
                        } else {
                            if($('#files').val() != ''){

                                $('#progress-txt').html('<span class="glyphicon glyphicon-repeat glyphicon-spin"></span> Bitte warten! ' +
                                                        '<span class="glyphicon glyphicon-alert"></span> Es ist ein Fehler aufgetreten.')
                                                  .removeClass().addClass('alert alert-danger');
                            }
                        }
                        stopProgress();
                    }
                }).fail(function(jqxhr, textStatus, error){

                    var msg = 'Leider ist folgender Fehler (#progress42) aufgetreten: <strong>'+ textStatus + ', ' + error + '</strong>';
                    $('#error').html(msg)
                               .prepend('<span class="glyphicon glyphicon-alert"></span> Es ist ein schwerer Systemfehler aufgetreten.<br>')
                               .addClass('alert alert-danger');
                    $('#progress').width('0%');
                    $('.status span').replaceWith('<span class="glyphicon glyphicon-alert text-danger" data-toggle="tooltip" title="'+msg+'"></span>');
                    $('#progress-txt').removeClass();
                    stopProgress();
                });

            }, 200);

            return false;

        });
    });

    function stopProgress()
    {
        clearInterval(interval_id);
        $('#progress').width('0%');
        $('#progress').html('');
        $('#submit').attr('disabled', false);
    }

    function extround(zahl,n_stelle) {
        zahl = (Math.round(zahl * n_stelle) / n_stelle);
        return zahl;
//                10 = 1 Nachkommastelle
//                100 = 2 Nackommastellen
//                1000 = 3 Nachkommastellen
//                usw.
    }
</script>

</body>
</html>

