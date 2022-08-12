<?php

$servername = 'hostName';
$username = 'userName';
$password = 'password';
$dbname = 'database';

$connection = new mysqli($servername, $username, $password, $dbname);

function importCsvDataIntoDb($fileName) {
    $row = 0;
    $count = 0;
    $rows = array();

    if (($handle = fopen($fileName, "r")) === FALSE) {
        return FALSE;
    }

    while (($row_data = fgetcsv($handle, 200, ",")) !== FALSE) {

        if ($row == 0) {
            $headings = $row_data;
            $row++;
            continue;
        }

        $rows[] = array_combine($headings, $row_data);
        $count++;
    }
    return $rows;
}

$location = $_SERVER["DOCUMENT_ROOT"];
$fileName = 'products.csv';
$filePath = $location."/".$fileName;
$csvArray = importCsvDataIntoDb($filePath);

foreach (array_chunk($csvArray, 1000) as $item) {

    $queryStr = '';
    $queryWhere = '';

    foreach($item as $key => $product){

        if ($product['code']) {
            $queryStr .= "when code = '" . $product['code'] . "' then" . ' ' . "'" . $product['price'] .  "'" . ' ';
            $queryWhere .= "'" . $product['code'] . "',";
        }
    }

    $queryWhere = rtrim($queryWhere, ",");

    $sql = "UPDATE oc_product SET price = (case " . $queryStr . "end) " . "WHERE code in (" . $queryWhere . ")" ;
    $connection->query($sql);
}

$connection->close();
echo 'Data uploaded successfully';
?>
