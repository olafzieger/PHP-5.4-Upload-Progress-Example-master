<?php
session_start();

$target_path = "/Users/olaf/uploads/";

if(isset($_FILES['files'])) {
    $name_array = $_FILES['files']['name'];
    $tmp_name_array = $_FILES['files']['tmp_name'];
    $type_array = $_FILES['files']['type'];
    $size_array = $_FILES['files']['size'];
    $error_array = $_FILES['files']['error'];
    for($i = 0; $i < count($tmp_name_array); $i++){
        if(move_uploaded_file($tmp_name_array[$i], $target_path . $name_array[$i])){
            echo $name_array[$i]." upload is complete<br>";
        } else {
            echo "move_uploaded_file function failed for ".$name_array[$i]."<br>";
        }
    }
}
?>