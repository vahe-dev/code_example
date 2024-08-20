<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('max_execution_time', '600');
ini_set('memory_limit','2048M');

$inputFileName = 'file.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$sheet = $spreadsheet->getSheet(0);

$startRow = 14;
$endRow = $sheet->getHighestRow();

$products = [];
$endRow = $sheet->getHighestRow();
$data = [];
$currentStyle = '';

// Function to iterate through columns from 'G' to 'AD'
function iterateColumns($start, $end) {
    $columns = [];
    for ($column = $start; $column !== $end;) {
        $columns[] = $column;
        $column++;
    }
    $columns[] = $end; // Include the last column
    return $columns;
}

$columns = iterateColumns('I', 'AC');

// Function to extract color
function extractColor($colorString) {
    // Split by space to separate color and code
    $parts = explode(' ', $colorString);
    if (count($parts) > 1) {
        return $parts[0];
    } else {
        return $colorString;
    }
}

for ($row = $startRow; $row <= $endRow; $row++) {
    $style = $sheet->getCell('D' . $row)->getValue();
    $color = $sheet->getCell('H' . $row)->getValue();
    $price = $sheet->getCell('AD' . $row)->getValue();

    // If the style cell is empty, use the last non-empty style
    if (!empty($style)) {
        $currentStyle = $style;
    }

    // If color is not empty, proceed to collect SKU, quantity, and price
    if (!empty($color)) {
        $extractedColor = extractColor($color);

        foreach ($columns as $col) {
            $size = $sheet->getCell($col . '13')->getValue();
            $quantity = $sheet->getCell($col . $row)->getValue();

            if (!empty($quantity)) {
                $sku = $currentStyle . '_' . $extractedColor . '_' . $size;
                $data[] = [
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }
        }
    }
}

// Create a new Spreadsheet object and add data
$newSpreadsheet = new Spreadsheet();
$newSheet = $newSpreadsheet->getActiveSheet();

// Set headers
$newSheet->setCellValue('A1', 'SKU');
$newSheet->setCellValue('B1', 'Quantity');
$newSheet->setCellValue('C1', 'Price');

$rowIndex = 2;
foreach ($data as $row) {
    $newSheet->setCellValue('A' . $rowIndex, $row['sku']);
    $newSheet->setCellValue('B' . $rowIndex, $row['quantity']);
    $newSheet->setCellValue('C' . $rowIndex, $row['price']);
    $rowIndex++;
}

$outputFileName = 'new_file.xlsx';
file_put_contents($outputFileName, '');
chmod($outputFileName, 0666);
$writer = new Xlsx($newSpreadsheet);
$writer->save($outputFileName);

echo "File has been generated successfully.";


