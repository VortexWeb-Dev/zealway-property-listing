<?php

require __DIR__ . "/crest/crest.php";
require __DIR__ . "/crest/crestcurrent.php";
require __DIR__ . "/crest/settings.php";
require __DIR__ . "/utils/index.php";
require __DIR__ . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$id = $_GET['id'];

$response = CRest::call('crm.item.list', [
    "entityTypeId" => LISTINGS_ENTITY_TYPE_ID,
    "filter" => ["id" => $id],
    "select" => [
        "ufCrm11ReferenceNumber",
        "ufCrm11OfferingType",
        "ufCrm11PropertyType",
        "ufCrm11SaleType",
        "ufCrm11UnitNo",
        "ufCrm11Size",
        "ufCrm11Bedroom",
        "ufCrm11Bathroom",
        "ufCrm11Parking",
        "ufCrm11LotSize",
        "ufCrm11TotalPlotSize",
        "ufCrm11BuildupArea",
        "ufCrm11LayoutType",
        "ufCrm11TitleEn",
        "ufCrm11DescriptionEn",
        "ufCrm11TitleAr",
        "ufCrm11DescriptionAr",
        "ufCrm11Geopoints",
        "ufCrm11ListingOwner",
        "ufCrm11LandlordName",
        "ufCrm11LandlordEmail",
        "ufCrm11LandlordContact",
        "ufCrm11ReraPermitNumber",
        "ufCrm11ReraPermitIssueDate",
        "ufCrm11ReraPermitExpirationDate",
        "ufCrm11DtcmPermitNumber",
        "ufCrm11Location",
        "ufCrm11BayutLocation",
        "ufCrm11ProjectName",
        "ufCrm11ProjectStatus",
        "ufCrm11Ownership",
        "ufCrm11Developers",
        "ufCrm11BuildYear",
        "ufCrm11Availability",
        "ufCrm11AvailableFrom",
        "ufCrm11RentalPeriod",
        "ufCrm11Furnished",
        "ufCrm11DownPaymentPrice",
        "ufCrm11NoOfCheques",
        "ufCrm11ServiceCharge",
        "ufCrm11PaymentMethod",
        "ufCrm11FinancialStatus",
        "ufCrm11AgentName",
        "ufCrm11ContractExpiryDate",
        "ufCrm11FloorPlan",
        "ufCrm11QrCodePropertyBooster",
        "ufCrm11VideoTourUrl",
        "ufCrm_18_360_VIEW_URL",
        "ufCrm11BrochureDescription",
        "ufCrm_12_BROCHURE_DESCRIPTION_2",
        "ufCrm11PhotoLinks",
        "ufCrm11Notes",
        "ufCrm11Amenities",
        "ufCrm11Price",
        "ufCrm11Status",
        "ufCrm11HidePrice",
        "ufCrm11PfEnable",
        "ufCrm11BayutEnable",
        "ufCrm11DubizzleEnable",
        "ufCrm11WebsiteEnable",
        "ufCrm11TitleDeed",
        // "ufCrm11City",
        // "ufCrm11Community",
        // "ufCrm11SubCommunity",
        // "ufCrm11Tower",
        // "ufCrm11BayutCity",
        // "ufCrm11BayutCommunity",
        // "ufCrm11BayutSubCommunity",
        // "ufCrm11BayutTower",
        // "ufCrm11AgentId",
        // "ufCrm11AgentEmail",
        // "ufCrm11AgentPhone",
        // "ufCrm11AgentLicense",
        // "ufCrm11AgentPhoto",
        // "ufCrm11Watermark",
    ]
]);

$property = $response['result']['items'][0];

if (!$property) {
    die("Property not found.");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

function getExcelColumn($index)
{
    return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
}

$columnIndex = 1;
foreach ($property as $key => $value) {
    if (empty($value)) {
        continue;
    }

    $colLetter = getExcelColumn($columnIndex);
    $sheet->setCellValue($colLetter . '1', $key);
    $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
    $sheet->setCellValue($colLetter . '2', is_array($value) ? implode(', ', $value) : $value); // Values
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    $columnIndex++;
}

function sanitizeFileName($filename)
{
    $filename = trim($filename);
    $filename = str_replace(' ', '_', $filename);
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
    $filename = preg_replace('/_+/', '_', $filename);

    return $filename;
}

$filename = "property_" . sanitizeFileName($property['ufCrm11ReferenceNumber']) . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
