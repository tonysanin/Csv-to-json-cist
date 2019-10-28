<?php
require_once "download_csv.php";

GetJsonFromCSV($_POST['group']);
header ('Location: results.json');
exit();