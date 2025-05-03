<?php
require 'utils/index.php';
require __DIR__ . "/crest/settings.php";
header('Content-Type: application/json; charset=UTF-8');

$baseUrl = WEB_HOOK_URL;
$entityTypeId = LISTINGS_ENTITY_TYPE_ID;
$fields = [
    'id',
    'ufCrm11ReferenceNumber',
    'ufCrm11PermitNumber',
    'ufCrm11ReraPermitNumber',
    'ufCrm11DtcmPermitNumber',
    'ufCrm11OfferingType',
    'ufCrm11PropertyType',
    'ufCrm11HidePrice',
    'ufCrm11RentalPeriod',
    'ufCrm11Price',
    'ufCrm11ServiceCharge',
    'ufCrm11NoOfCheques',
    'ufCrm11City',
    'ufCrm11Community',
    'ufCrm11SubCommunity',
    'ufCrm11Tower',
    'ufCrm11TitleEn',
    'ufCrm11TitleAr',
    'ufCrm11DescriptionEn',
    'ufCrm11DescriptionAr',
    'ufCrm11TotalPlotSize',
    'ufCrm11Size',
    'ufCrm11Bedroom',
    'ufCrm11Bathroom',
    'ufCrm11AgentId',
    'ufCrm11AgentName',
    'ufCrm11AgentEmail',
    'ufCrm11AgentPhone',
    'ufCrm11AgentPhoto',
    'ufCrm11BuildYear',
    'ufCrm11Parking',
    'ufCrm11Furnished',
    'ufCrm_18_360_VIEW_URL',
    'ufCrm11PhotoLinks',
    'ufCrm11FloorPlan',
    'ufCrm11Geopoints',
    'ufCrm11AvailableFrom',
    'ufCrm11VideoTourUrl',
    'ufCrm11Developers',
    'ufCrm11ProjectName',
    'ufCrm11ProjectStatus',
    'ufCrm11ListingOwner',
    'ufCrm11Status',
    'ufCrm11PfEnable',
    'ufCrm11BayutEnable',
    'ufCrm11DubizzleEnable',
    'ufCrm11WebsiteEnable',
    'updatedTime',
    'ufCrm11TitleDeed',
    'ufCrm11Amenities'
];

$properties = fetchAllProperties($baseUrl, $entityTypeId, $fields,);

if (count($properties) > 0) {
    $json = generateWebsiteJson($properties);
    echo $json;
} else {
    echo json_encode([]);
}
