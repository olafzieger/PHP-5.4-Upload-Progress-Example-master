<?php
session_start();
/*var_dump($_SESSION);
var_dump($_FILES);*/
echo '<pre>';
print_r($_SESSION);

/*print_r($_FILES);*/

$target_path = "/Users/olaf/uploads/";

/*$target_path = $target_path . basename( $_FILES['files']['name']);*/

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

if(move_uploaded_file($_FILES['file1']['tmp_name'], $target_path)) {
    echo "The file ".  basename( $_FILES['file1']['name']).
        " has been uploaded";
} else{
    echo "There was an error uploading the file, please try again!";
}

?>
</pre>