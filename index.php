<?php
session_start();
?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" href="bootstrap.min.css">
        <style>
            progress {
                margin:10px 0 10px 0;
            }
            
            .content {
            margin-top:60px;
            }
        </style>
        <title>Relaunch des R&C Datentransferservers</title>
    </head>
    <body>

        <div class="topbar">
            <div class="fill">
                <div class="container">
                    <a class="brand" href="#">Datentransfer</a>

                    <ul class="nav">
                        <li><a href="http://daten.local/admin/">Transferübersicht</a></li>
                        <li><a href="https://www.runze-casper.de">runze-casper.de</a></li>
                    </ul>

                </div>
            </div>
        </div>
        <article class="container">
            <div class="content">
                <header class="page-header">
                    <h1>Relaunch des R&C Datentransferservers</h1>
                </header>

                <div class="row">
                    <div class="span16">

                        <h2>Upload</h2>
                        <p>Select one or two files to upload (Max total size 2MB)</p>
                        <form action="/upload.php" method="POST" enctype="multipart/form-data" id="upload">
                            <input type="hidden" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="upload" />


                            <div class="clearfix">
                                <label for="files">Bitte wählen Sie Dateien die übermittelt werden sollen.</label>
                                <div class="input">
                                    <input type="file" name="files[]" id="files" multiple style="width: 410px !important;" />
                                </div>
                            </div>
                            <div class="actions">
                                <input type="submit" class="btn primary" value="Upload"/>
                            </div>
                        </form>

                        <h2>Progress</h2>
                        <progress max="1" value="0" id="progress"></progress>
                        <p id="progress-txt"></p>
                        <ul id="fileslist"></ul>
                    </div>
                </div>
            </div>

        </article>




        <!-- File containing Jquery and the Jquery form plugin-->
        <script src="jquery.js"></script>
        <script>
		
            //Holds the id from set interval
            var interval_id = 0;
        
            $(document).ready(function(){

                $('#files').change(function(){
                    var selectedFileList = '';
                    var fileListSize = 0;
                    var selectedfiles = $('#files')[0].files;
                    for(var f = 0; f < selectedfiles.length; f++) {
                        selectedFileList += '<li>' + selectedfiles[f].name + ' ' + extround((selectedfiles[f].size/1024/1024), 100) + ' MB</li>';
                        fileListSize += selectedfiles[f].size;
                    }
                    $('#fileslist').html(selectedFileList);
                    $('#progress-txt').html('Die von Ihnen agewählten Dateien: ' + extround((fileListSize/1024/1024), 100) + ' MB');
                });
	

                //Add the submit handler to the form
                $('#upload').submit(function(e){
                	
                    //check there is at least one file
                    if($('#files').val() == '')
                    {
                        e.preventDefault();
                        return;
                    }
		
                    //Poll the server for progress
                    interval_id = setInterval(function() {
                        $.getJSON('/progress.php', function(data){
                            
                            //if there is some progress then update the status
                            if(data)
                            {
                                $('#progress').val(data.bytes_processed / data.content_length);
                                $('#progress-txt').html('Uploading '+ Math.round((data.bytes_processed / data.content_length) * 100) + '% ');
                                $('#progress-txt').append(extround((data.content_length/1024/1024), 100) + ' MB');
                                var filelist = "";
                                for(var i = 0; i < data.files.length; i++) {
                                    var done = ' <img src="preloader.gif" style="margin-bottom: -3px" />';
                                    if(data.files[i]['done']) {done = '<span style="font-size: 140%; color: green;"> ✓</span>'}
                                    filelist += '<li>' + data.files[i]['name'] +  done + '</li>';
                                }
                                $('#fileslist').html(filelist);
                            }
                        })
                    }, 200);
		
                    $('#upload').ajaxSubmit({
                        // Optionen für den jQuery-Ajax-Commit:
                        success: function(){
                            $('#progress').val('1');
                            $('#progress-txt').html('Alle Daten erfolgreich hochgeladen.');
                            $('#fileslist > li > img').replaceWith('<span style="font-size: 140%; color: green;"> ✓</span>');
                            stopProgress();
                        },
                        error: function(){
                            $('#progress').val('1');
                            $('#progress-txt').html('Es ist ein Fehler aufgetreten.');
                            $('#fileslist > li > img').replaceWith('<span style="font-size: 140%; color: red;"> ✘</span>');
                            stopProgress();
                        }
                    });
                    
                    e.preventDefault();
                });	
            });

            function stopProgress()
            {
                clearInterval(interval_id);
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

