<?php

require_once(__DIR__ . "/../crest/crest.php");
require_once(__DIR__ . "/../crest/crestcurrent.php");


function buildApiUrl($baseUrl, $entityTypeId, $fields, $start = 0)
{
    $selectParams = '';
    foreach ($fields as $index => $field) {
        $selectParams .= "select[$index]=$field&";
    }
    $selectParams = rtrim($selectParams, '&');
    return "$baseUrl/crm.item.list?entityTypeId=$entityTypeId&$selectParams&start=$start&filter[ufCrm11Status]=PUBLISHED";
}

function buildApiUrlAgents($baseUrl, $entityTypeId, $fields, $start = 0)
{
    $selectParams = '';
    foreach ($fields as $index => $field) {
        $selectParams .= "select[$index]=$field&";
    }
    $selectParams = rtrim($selectParams, '&');
    return "$baseUrl/crm.item.list?entityTypeId=$entityTypeId&$selectParams&start=$start";
}

function fetchAllProperties($baseUrl, $entityTypeId, $fields, $platform = null)
{
    $allProperties = [];
    $start = 0;

    try {
        while (true) {
            $apiUrl = buildApiUrl($baseUrl, $entityTypeId, $fields, $start);
            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true);

            if (isset($data['result']['items'])) {
                $properties = $data['result']['items'];
                $allProperties = array_merge($allProperties, $properties);
            }

            // If there's no "next" key, we've fetched all data
            if (empty($data['next'])) {
                break;
            }

            $start = $data['next'];
        }

        if ($platform) {
            switch ($platform) {
                case 'pf':
                    $allProperties = array_filter($allProperties, function ($property) {
                        return $property['ufCrm11PfEnable'] === 'Y';
                    });
                    break;
                case 'bayut':
                    $allProperties = array_filter($allProperties, function ($property) {
                        return $property['ufCrm11BayutEnable'] === 'Y';
                    });
                    break;
                case 'dubizzle':
                    $allProperties = array_filter($allProperties, function ($property) {
                        return $property['ufCrm11DubizzleEnable'] === 'Y';
                    });
                    break;
                case 'website':
                    $allProperties = array_filter($allProperties, function ($property) {
                        return $property['ufCrm11WebsiteEnable'] === 'Y';
                    });
                    break;
                default:
                    break;
            }
        }

        return $allProperties;
    } catch (Exception $e) {
        error_log('Error fetching properties: ' . $e->getMessage());
        return [];
    }
}

function fetchAllAgents($baseUrl, $entityTypeId, $fields)
{
    $allAgents = [];
    $start = 0;

    try {
        while (true) {
            $apiUrl = buildApiUrlAgents($baseUrl, $entityTypeId, $fields, $start);
            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true);

            if (isset($data['result']['items'])) {
                $agents = $data['result']['items'];
                $allAgents = array_merge($allAgents, $agents);
            }

            // If there's no "next" key, we've fetched all data
            if (empty($data['next'])) {
                break;
            }

            $start = $data['next'];
        }

        return $allAgents;
    } catch (Exception $e) {
        error_log('Error fetching agents: ' . $e->getMessage());
        return [];
    }
}

function getPropertyPurpose($property)
{
    return ($property['ufCrm11OfferingType'] == 'RR' || $property['ufCrm11OfferingType'] == 'CR') ? 'Rent' : 'Buy';
}

function getPropertyType($property)
{
    $offeringType = $property['ufCrm11OfferingType'];

    $property_types = array(
        "AP" => "Apartment",
        "BW" => "Bungalow",
        "CD" => "Compound",
        "DX" => "Duplex",
        "FF" => "Full floor",
        "HF" => "Half floor",
        "PH" => "Penthouse",
        "TH" => "Townhouse",
        "VH" => "Villa",
        "WB" => "Building",
        "HA" => "Hotel Apartment",
        "LC" => "Labor camp",
        "BU" => "Bulk units",
        "WH" => "Warehouse",
        "FA" => "Factory",
        "OF" => "Office",
        "RE" => "Retail",
        "LP" => "Plot",
        "SH" => "Shop",
        "SR" => "Show Room",
        "SA" => "Staff Accommodation"
    );

    $typeCode = $property['ufCrm11PropertyType'] ?? '';
    $baseType = $property_types[$typeCode] ?? '';

    if (in_array($offeringType, ['CR', 'CS'])) {
        if (in_array($typeCode, ['LP', 'WB', 'VH'])) {
            return 'Commercial ' . $baseType;
        }
    } else {
        if ($typeCode === 'WB') {
            return 'Whole ' . $baseType;
        } elseif ($typeCode === 'LP') {
            return 'Residential ' . $baseType;
        }
    }

    return $baseType;
}

function getPropertyTypeFromId($typeId)
{
    $property_types = array(
        "AP" => "Apartment",
        "BW" => "Bungalow",
        "CD" => "Compound",
        "DX" => "Duplex",
        "FF" => "Full floor",
        "HF" => "Half floor",
        // "LP" => "Land / Plot",
        "PH" => "Penthouse",
        "TH" => "Townhouse",
        "VH" => "Villa",
        "WB" => "Whole Building",
        "HA" => "Hotel Apartment",
        "LC" => "Labor camp",
        "BU" => "Bulk units",
        "WH" => "Warehouse",
        "FA" => "Factory",
        "OF" => "Office space",
        "RE" => "Retail",
        "LP" => "Residential Plot",
        "SH" => "Shop",
        "SR" => "Show Room",
        "SA" => "Staff Accommodation"
    );

    return $property_types[$typeId] ?? $typeId;
}

function getPermitNumber($property)
{
    if (!empty($property['ufCrm11PermitNumber'])) {
        return $property['ufCrm11PermitNumber'];
    }
    return $property['ufCrm11ReraPermitNumber'] ?? '';
}

function getFullAmenityName($shortCode)
{
    $amenityMap = [
        'BA' => 'Balcony',
        'BP' => 'Basement parking',
        'BB' => 'BBQ area',
        'AN' => 'Cable-ready',
        'BW' => 'Built in wardrobes',
        'CA' => 'Carpets',
        'AC' => 'Central air conditioning',
        'CP' => 'Covered parking',
        'DR' => 'Drivers room',
        'FF' => 'Fully fitted kitchen',
        'GZ' => 'Gazebo',
        'PY' => 'Private Gym',
        'PJ' => 'Jacuzzi',
        'BK' => 'Kitchen Appliances',
        'MR' => 'Maids Room',
        'MB' => 'Marble floors',
        'HF' => 'On high floor',
        'LF' => 'On low floor',
        'MF' => 'On mid floor',
        'PA' => 'Pets allowed',
        'GA' => 'Private garage',
        'PG' => 'Garden',
        'PP' => 'Swimming pool',
        'SA' => 'Sauna',
        'SP' => 'Shared swimming pool',
        'WF' => 'Wood flooring',
        'SR' => 'Steam room',
        'ST' => 'Study',
        'UI' => 'Upgraded interior',
        'GR' => 'Garden view',
        'VW' => 'Sea/Water view',
        'SE' => 'Security',
        'MT' => 'Maintenance',
        'IC' => 'Within a Compound',
        'IS' => 'Indoor swimming pool',
        'SF' => 'Separate entrance for females',
        'BT' => 'Basement',
        'SG' => 'Storage room',
        'CV' => 'Community view',
        'GV' => 'Golf view',
        'CW' => 'City view',
        'NO' => 'North orientation',
        'SO' => 'South orientation',
        'EO' => 'East orientation',
        'WO' => 'West orientation',
        'NS' => 'Near school',
        'HO' => 'Near hospital',
        'TR' => 'Terrace',
        'NM' => 'Near mosque',
        'SM' => 'Near supermarket',
        'ML' => 'Near mall',
        'PT' => 'Near public transportation',
        'MO' => 'Near metro',
        'VT' => 'Near veterinary',
        'BC' => 'Beach access',
        'PK' => 'Public parks',
        'RT' => 'Near restaurants',
        'NG' => 'Near Golf',
        'AP' => 'Near airport',
        'CS' => 'Concierge Service',
        'SS' => 'Spa',
        'SY' => 'Shared Gym',
        'MS' => 'Maid Service',
        'WC' => 'Walk-in Closet',
        'HT' => 'Heating',
        'GF' => 'Ground floor',
        'SV' => 'Server room',
        'DN' => 'Pantry',
        'RA' => 'Reception area',
        'VP' => 'Visitors parking',
        'OP' => 'Office partitions',
        'SH' => 'Core and Shell',
        'CD' => 'Children daycare',
        'CL' => 'Cleaning services',
        'NH' => 'Near Hotel',
        'CR' => 'Conference room',
        'BL' => 'View of Landmark',
        'PR' => 'Children Play Area',
        'BH' => 'Beach Access',
        'SE' => 'Security',
        'CO' => "Children's Pool",
    ];

    return $amenityMap[$shortCode] ?? $shortCode;
}

function formatDate($date)
{
    return $date ? date('Y-m-d H:i:s', strtotime($date)) : date('Y-m-d H:i:s');
}

function formatField($field, $value, $type = 'string')
{
    if (empty($value) && $value != 0) {
        return '';
    }

    switch ($type) {
        case 'date':
            return '<' . $field . '>' . formatDate($value) . '</' . $field . '>';
        default:
            return '<' . $field . '>' . htmlspecialchars($value) . '</' . $field . '>';
    }
}

function formatPriceOnApplication($property)
{
    $priceOnApplication = ($property['ufCrm11HidePrice'] === 'Y') ? 'Yes' : 'No';
    return formatField('price_on_application', $priceOnApplication);
}

function formatRentalPrice($property)
{
    if (empty($property['ufCrm11Price'])) {
        return '<price></price>';
    }

    $price = (int) $property['ufCrm11Price'];
    $rentalPeriod = $property['ufCrm11RentalPeriod'] ?? '';

    $minPrices = [
        'Y' => 10000, // Yearly rent
        'M' => 1000,  // Monthly rent
        'W' => 1000,  // Weekly rent
        'D' => 100,   // Daily rent
    ];

    if (isset($minPrices[$rentalPeriod]) && $price < $minPrices[$rentalPeriod]) {
        return '<price></price>'; // Leave empty tag if below minimum
    }

    // If it's a sales price, return directly
    if (!$rentalPeriod) {
        return "<price>{$price}</price>";
    }

    // Construct rental price XML dynamically (avoid empty tags)
    $rentalPrices = [];
    if ($rentalPeriod == 'Y') $rentalPrices[] = "<yearly>{$price}</yearly>";
    if ($rentalPeriod == 'M') $rentalPrices[] = "<monthly>{$price}</monthly>";
    if ($rentalPeriod == 'W') $rentalPrices[] = "<weekly>{$price}</weekly>";
    if ($rentalPeriod == 'D') $rentalPrices[] = "<daily>{$price}</daily>";

    return "<price>\n" . implode("\n", $rentalPrices) . "\n</price>";
}

function formatBedroom($property)
{
    return formatField('bedroom', ($property['ufCrm11Bedroom'] > 7) ? '7+' : $property['ufCrm11Bedroom']);
}

function formatBathroom($property)
{
    return formatField('bathroom', ($property['ufCrm11Bathroom'] > 7) ? '7+' : $property['ufCrm11Bathroom']);
}

function formatFurnished($property)
{
    $furnished = $property['ufCrm11Furnished'] ?? '';
    if ($furnished) {
        switch ($furnished) {
            case 'furnished':
                return formatField('furnished', 'Yes');
            case 'unfurnished':
                return formatField('furnished', 'No');
            case 'Partly Furnished':
                return formatField('furnished', 'Partly');
            default:
                return '';
        }
    }
    return ''; // If no furnished value exists, return an empty string
}

function formatAgent($property)
{
    $xml = '<agent>';
    $xml .= formatField('id', $property['ufCrm11AgentId']);
    $xml .= formatField('name', $property['ufCrm11AgentName']);
    $xml .= formatField('email', $property['ufCrm11AgentEmail']);
    $xml .= formatField('phone', $property['ufCrm11AgentPhone']);
    $xml .= formatField('photo', $property['ufCrm11AgentPhoto'] ?? 'https://youtupia.com/thinkrealty/images/agent-placeholder.png');
    $xml .= '</agent>';

    return $xml;
}

function formatPhotos($photos, $watermark = true)
{
    if (empty($photos)) {
        return '';
    }

    $xml = '<photo>';
    foreach ($photos as $photo) {
        $xml .= '<url last_update="' . date('Y-m-d H:i:s') . '" watermark="' . ($watermark ? 'Yes' : 'No') . '">' . htmlspecialchars($photo) . '</url>';
    }
    $xml .= '</photo>';

    return $xml;
}

function formatWebsitePhotos($photos)
{
    if (empty($photos)) {
        return '';
    }

    $xml = '<more_photo>';

    foreach ($photos as $index => $photo) {
        $xml .= "<key_$index>";
        $xml .= '<src>' . htmlspecialchars($photo) . '</src>';
        $xml .= '<file_name>' . htmlspecialchars(basename($photo)) . '</file_name>';
        $xml .= "</key_$index>";  // Properly closing the tag
    }

    $xml .= '</more_photo>';

    return $xml;
}

function formatGeopoints($property)
{
    $geopoints = $property['ufCrm11Geopoints'] ?? '';

    return formatField('geopoints', $geopoints);
}

function formatCompletionStatus($property)
{
    $status = $property['ufCrm11ProjectStatus'] ?? '';
    switch ($status) {
        case 'Completed':
        case 'ready_secondary':
            return formatField('completion_status', 'completed');
        case 'offplan':
        case 'offplan_secondary':
            return formatField('completion_status', 'off_plan');
        case 'ready_primary':
            return formatField('completion_status', 'completed_primary');
        case 'off_plan_primary':
            return formatField('completion_status', 'off_plan_primary');
        default:
            return '';
    }
}

function formatAmenities($property)
{
    $private_amenities_ids = [
        'AC',
        'BA',
        'BK',
        'BL',
        'BW',
        'CP',
        'CS',
        'LB',
        'MR',
        'MS',
        'PA',
        'PG',
        'PJ',
        'PP',
        'PY',
        'VC',
        'SE',
        'SP',
        'SS',
        'ST',
        'SY',
        'VW',
        'WC',
        'CO',
        'PR',
        'BR'
    ];

    $commercial_amenities_ids = [
        'CR',
        'AN',
        'DN',
        'LB',
        'SP',
        'SY',
        'CP',
        'VC',
        'PN',
        'MZ'
    ];

    $amenities = $property['ufCrm11Amenities'] ?? [];

    $private_xml = '<private_amenities>';
    $commercial_xml = '<commercial_amenities>';

    foreach ($amenities as $amenity) {
        if (strlen($amenity) > 2) continue;

        if (in_array($amenity, $private_amenities_ids)) {
            $private_xml .= $amenity . ', ';
        }

        if (in_array($amenity, $commercial_amenities_ids)) {
            $commercial_xml .= $amenity . ', ';
        }
    }

    $private_xml = rtrim($private_xml, ', ') . '</private_amenities>';
    $commercial_xml = rtrim($commercial_xml, ', ') . '</commercial_amenities>';

    return $private_xml . $commercial_xml;
}

function generatePfXml($properties)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<list last_update="' . date('Y-m-d H:i:s') . '" listing_count="' . count($properties) . '">';

    foreach ($properties as $property) {
        $xml .= '<property last_update="' . formatDate($property['updatedTime'] ?? '') . '" id="' . htmlspecialchars($property['id'] ?? '') . '">';

        $xml .= formatField('reference_number', $property['ufCrm11ReferenceNumber']);
        $xml .= formatField('permit_number', getPermitNumber($property));

        $xml .= formatField('dtcm_permit', $property['ufCrm11DtcmPermitNumber']);
        $xml .= formatField('offering_type', $property['ufCrm11OfferingType']);
        $xml .= formatField('property_type', $property['ufCrm11PropertyType']);
        $xml .= formatPriceOnApplication($property);
        $xml .= formatRentalPrice($property);

        $xml .= formatField('service_charge', $property['ufCrm11ServiceCharge']);
        $xml .= formatField('cheques', $property['ufCrm11NoOfCheques']);
        $xml .= formatField('city', $property['ufCrm11City']);
        $xml .= formatField('community', $property['ufCrm11Community']);
        $xml .= formatField('sub_community', $property['ufCrm11SubCommunity']);
        $xml .= formatField('property_name', $property['ufCrm11Tower']);

        $xml .= formatField('title_en', $property['ufCrm11TitleEn']);
        $xml .= formatField('title_ar', $property['ufCrm11TitleAr']);
        $xml .= formatField('description_en', $property['ufCrm11DescriptionEn']);
        $xml .= formatField('description_ar', $property['ufCrm11DescriptionAr']);

        $xml .= formatField('plot_size', $property['ufCrm11TotalPlotSize']);
        $xml .= formatField('size', $property['ufCrm11Size']);
        // $xml .= formatField('bedroom', $property['ufCrm11Bedroom']);
        $xml .= formatBedroom($property);
        $xml .= formatBathroom($property);

        $xml .= formatAgent($property);
        $xml .= formatField('build_year', $property['ufCrm11BuildYear']);
        $xml .= formatField('parking', $property['ufCrm11Parking']);
        $xml .= formatFurnished($property);
        $xml .= formatField('view360', $property['ufCrm_18_360_VIEW_URL']);

        $watermark = ($property['ufCrm11Watermark'] === 'Y' || $property['ufCrm11Watermark'] === null) ? 'Y' : 'N';
        $xml .= formatPhotos($property['ufCrm11PhotoLinks'], $watermark === 'Y');

        $xml .= formatField('floor_plan', $property['ufCrm11FloorPlan']);
        $xml .= formatGeopoints($property);
        $xml .= formatField('availability_date', $property['ufCrm11AvailableFrom'], 'date');
        $xml .= formatField('video_tour_url', $property['ufCrm11VideoTourUrl']);
        $xml .= formatField('developer', $property['ufCrm11Developers']);
        $xml .= formatField('project_name', $property['ufCrm11ProjectName']);
        $xml .= formatCompletionStatus($property);
        $xml .= formatAmenities($property);

        $xml .= '</property>';
    }

    $xml .= '</list>';
    return $xml;
}

function generateWebsiteXml($properties)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<list last_update="' . date('Y-m-d H:i:s') . '">';

    foreach ($properties as $property) {
        $xml .= '<property last_update="' . formatDate($property['updatedTime'] ?? '') . '" id="' . htmlspecialchars($property['id'] ?? '') . '">';

        $xml .= formatField('id', $property['id']);
        $xml .= formatField('name', $property['ufCrm11TitleEn']);
        $xml .= formatField('detail_text', $property['ufCrm11DescriptionEn']);
        $xml .= formatField('price', $property['ufCrm11Price'] . ' AED');

        $xml .= '<properties>';

        $xml .= formatWebsitePhotos($property['ufCrm11PhotoLinks']);
        $xml .= '<status>Live</status>';
        $purposeMapping = [
            'RS' => 'Sale',
            'RR' => 'Rent',
            'CS' => 'Sale',
            'CR' => 'Rent'
        ];

        $offeringType = $property['ufCrm11OfferingType'] ?? ''; // Get the value safely
        $purpose = $purposeMapping[$offeringType] ?? ''; // Map value or default to empty string

        $xml .= "<property_purpose><key_0>" . htmlspecialchars($purpose) . "</key_0></property_purpose>";

        // Adding the agent details
        $xml .= "<link_to_employee>";
        $xml .= "<id>" . htmlspecialchars($property['ufCrm11AgentId'] ?? '') . "</id>";
        $xml .= "<login>" . htmlspecialchars($property['ufCrm11AgentEmail'] ?? '') . "</login>";
        $xml .= "<full_name>" . htmlspecialchars($property['ufCrm11AgentName'] ?? '') . "</full_name>";
        $xml .= "<email>" . htmlspecialchars($property['ufCrm11AgentEmail'] ?? '') . "</email>";
        $xml .= "<phone>" . htmlspecialchars($property['ufCrm11AgentPhone'] ?? '') . "</phone>";
        $xml .= "<photo>" . htmlspecialchars($property['ufCrm11AgentPhoto'] ?? '') . "</photo>";
        $xml .= "</link_to_employee>";

        $xml .= formatField('permit_number', getPermitNumber($property));
        $xml .= formatField('bedrooms_number', $property['ufCrm11Bedroom']);
        $xml .= formatField('bathrooms_number', $property['ufCrm11Bathroom']);
        $xml .= formatField('bua_area_size', $property['ufCrm11Size']);

        $xml .= "<export_to>";

        $exportPlatforms = [];
        if ($property['ufCrm11BayutEnable'] ?? '' == 'Y') {
            $exportPlatforms[] = "Bayut";
        }
        if ($property['ufCrm11DubizzleEnable'] ?? '' == 'Y') {
            $exportPlatforms[] = "Dubizzle";
        }
        if ($property['ufCrm11WebsiteEnable'] ?? '' == 'Y') {
            $exportPlatforms[] = "Web";
        }

        foreach ($exportPlatforms as $index => $platform) {
            $xml .= "<key_$index>" . htmlspecialchars($platform) . "</key_$index>";
        }

        $xml .= "</export_to>";

        $xml .= formatField('title_deed_and_passport', $property['ufCrm11TitleDeed']);
        $xml .= formatField('availability_date_for_rental', $property['ufCrm11AvailableFrom']);
        $xml .= formatField('plot_size', $property['ufCrm11TotalPlotSize']);
        $xml .= formatField('yearly_service_charge', $property['ufCrm11ServiceCharge']);
        $xml .= formatField('cheques', $property['ufCrm11NoOfCheques']);
        $xml .= formatField('parking_slots', $property['ufCrm11Parking']);
        $xml .= formatField('link_city', $property['ufCrm11City']);
        $xml .= formatField('link_district', $property['ufCrm11Community']);
        $xml .= formatField('link_subarea', $property['ufCrm11SubCommunity']);
        $xml .= formatField('geopoints', $property['ufCrm11Geopoints']);
        $purposeMapping = [
            'RS' => 'Residential Sale',
            'RR' => 'Residential Rent',
            'CS' => 'Commercial Sale',
            'CR' => 'Commercial Rent'
        ];

        $offeringType = $property['ufCrm11OfferingType'] ?? ''; // Get the value safely
        $purpose = $purposeMapping[$offeringType] ?? ''; // Map value or default to empty string

        $xml .= "<offering_type>" . htmlspecialchars($purpose) . "</offering_type>";
        $xml .= formatField('property_ref_no', $property['ufCrm11ReferenceNumber']);

        $xml .= formatField('property_type', getPropertyType($property));

        // $xml .= formatPriceOnApplication($property);
        // $xml .= formatRentalPrice($property);
        // $xml .= formatField('service_charge', $property['ufCrm11ServiceCharge']);
        // $xml .= formatField('cheques', $property['ufCrm11NoOfCheques']);
        // $xml .= formatField('property_name', $property['ufCrm11Tower']);
        // $xml .= formatField('title_ar', $property['ufCrm11TitleAr']);
        // $xml .= formatField('description_ar', $property['ufCrm11DescriptionAr']);
        // $xml .= formatField('plot_size', $property['ufCrm11TotalPlotSize']);
        // $xml .= formatField('size', $property['ufCrm11Size']);
        // $xml .= formatField('bedroom', $property['ufCrm11Bedroom']);
        // $xml .= formatBedroom($property);
        // $xml .= formatBathroom($property);
        // $xml .= formatAgent($property);
        // $xml .= formatField('build_year', $property['ufCrm11BuildYear']);
        // $xml .= formatField('parking', $property['ufCrm11Parking']);
        // $xml .= formatFurnished($property);
        // $xml .= formatField('view360', $property['ufCrm_18_360_VIEW_URL']);
        // $xml .= formatField('floor_plan', $property['ufCrm11FloorPlan']);
        // $xml .= formatGeopoints($property);
        // $xml .= formatField('availability_date', $property['ufCrm11AvailableFrom'], 'date');
        // $xml .= formatField('video_tour_url', $property['ufCrm11VideoTourUrl']);
        // $xml .= formatField('developer', $property['ufCrm11Developers']);
        // $xml .= formatField('project_name', $property['ufCrm11ProjectName']);
        // $xml .= formatCompletionStatus($property);

        $xml .= '</properties>';
        $xml .= '</property>';
    }

    $xml .= '</list>';
    return $xml;
}

function generateAgentsXml($agents)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<agents>';

    foreach ($agents as $agent) {
        $xml .= '<agent>';

        $xml .= formatField('name', $agent['ufCrm11AgentName']);
        $xml .= formatField('phone', $agent['ufCrm11AgentMobile']);
        $xml .= formatField('email', $agent['ufCrm11AgentEmail']);
        $xml .= formatField('photo', $agent['ufCrm11AgentPhoto']);
        $xml .= formatField('license', $agent['ufCrm11AgentLicense']);

        $xml .= '</agent>';
    }

    $xml .= '</agents>';
    return $xml;
}

function generateBayutXml($properties)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Properties last_update="' . date('Y-m-d H:i:s') . '" listing_count="' . count($properties) . '">';

    foreach ($properties as $property) {
        $xml .= '<Property id="' . htmlspecialchars($property['id'] ?? '') . '">';

        // Ensure proper CDATA wrapping and no misplaced closing tags
        $xml .= '<Property_Ref_No><![CDATA[' . ($property['ufCrm11ReferenceNumber'] ?? '') . ']]></Property_Ref_No>';
        $xml .= '<Permit_Number><![CDATA[' . getPermitNumber($property) . ']]></Permit_Number>';
        $xml .= '<Property_Status>live</Property_Status>';
        $xml .= '<Property_purpose><![CDATA[' . getPropertyPurpose($property) . ']]></Property_purpose>';
        $xml .= '<Property_Type><![CDATA[' . getPropertyType($property) . ']]></Property_Type>';
        $xml .= '<Property_Size><![CDATA[' . ($property['ufCrm11Size'] ?? '') . ']]></Property_Size>';
        $xml .= '<Property_Size_Unit>SQFT</Property_Size_Unit>';

        // Ensure proper condition for optional fields
        if (!empty($property['ufCrm11TotalPlotSize'])) {
            $xml .= '<plotArea><![CDATA[' . $property['ufCrm11TotalPlotSize'] . ']]></plotArea>';
        }

        if ($property['ufCrm11OfferingType'] === 'RR' || $property['ufCrm11OfferingType'] === 'RS') {
            $xml .= '<Bedrooms><![CDATA[' . (($property['ufCrm11Bedroom'] === 0) ? -1 : ($property['ufCrm11Bedroom'] > 10 ? "10+" : $property['ufCrm11Bedroom'])) . ']]></Bedrooms>';
        }
        $xml .= '<Bathrooms><![CDATA[' . ($property['ufCrm11Bathroom'] ?? '') . ']]></Bathrooms>';

        $is_offplan = ($property['ufCrm11ProjectStatus'] === 'off_plan_primary' || $property['ufCrm11ProjectStatus'] === 'offplan_secondary') ? 'Yes' : 'No';
        $xml .= '<Off_plan><![CDATA[' . $is_offplan . ']]></Off_plan>';

        $xml .= '<Portals>';
        if ($property['ufCrm11BayutEnable'] === 'Y') {
            $xml .= '<Portal>Bayut</Portal>';
        }
        if ($property['ufCrm11DubizzleEnable'] === 'Y') {
            $xml .= '<Portal>Dubizzle</Portal>';
        }
        $xml .= '</Portals>';

        $xml .= '<Property_Title><![CDATA[' . ($property['ufCrm11TitleEn'] ?? '') . ']]></Property_Title>';
        $xml .= '<Property_Description><![CDATA[' . ($property['ufCrm11DescriptionEn'] ?? '') . ']]></Property_Description>';

        if (!empty($property['ufCrm11TitleAr'])) {
            $xml .= '<Property_Title_AR><![CDATA[' . ($property['ufCrm11TitleAr'] ?? '') . ']]></Property_Title_AR>';
        }
        if (!empty($property['ufCrm11DescriptionAr'])) {
            $xml .= '<Property_Description_AR><![CDATA[' . ($property['ufCrm11DescriptionAr'] ?? '') . ']]></Property_Description_AR>';
        }

        $xml .= '<Price><![CDATA[' . ($property['ufCrm11Price'] ?? '') . ']]></Price>';

        if ($property['ufCrm11RentalPeriod'] == 'Y') {
            $xml .= '<Rent_Frequency>Yearly</Rent_Frequency>';
        } elseif ($property['ufCrm11RentalPeriod'] == 'M') {
            $xml .= '<Rent_Frequency>Monthly</Rent_Frequency>';
        } elseif ($property['ufCrm11RentalPeriod'] == 'W') {
            $xml .= '<Rent_Frequency>Weekly</Rent_Frequency>';
        } elseif ($property['ufCrm11RentalPeriod'] == 'D') {
            $xml .= '<Rent_Frequency>Daily</Rent_Frequency>';
        }

        if ($property['ufCrm11Furnished'] === 'furnished') {
            $xml .= '<Furnished>Yes</Furnished>';
        } elseif ($property['ufCrm11Furnished'] === 'unfurnished') {
            $xml .= '<Furnished>No</Furnished>';
        } elseif ($property['ufCrm11Furnished'] === 'semi-furnished') {
            $xml .= '<Furnished>Partly</Furnished>';
        }

        if (!empty($property['ufCrm11SaleType'])) {
            $xml .= '<offplanDetails_saleType><![CDATA[' . ($property['ufCrm11SaleType'] ?? '') . ']]></offplanDetails_saleType>';
        }

        $xml .= '<City><![CDATA[' . ($property['ufCrm11BayutCity'] ?: $property['ufCrm11City'] ?? '') . ']]></City>';
        $xml .= '<Locality><![CDATA[' . ($property['ufCrm11BayutCommunity'] ?: $property['ufCrm11Community'] ?? '') . ']]></Locality>';
        $xml .= '<Sub_Locality><![CDATA[' . ($property['ufCrm11BayutSubCommunity'] ?: $property['ufCrm11SubCommunity'] ?? '') . ']]></Sub_Locality>';
        $xml .= '<Tower_Name><![CDATA[' . ($property['ufCrm11BayutTower'] ?: $property['ufCrm11Tower'] ?? '') . ']]></Tower_Name>';

        $xml .= '<Listing_Agent><![CDATA[' . ($property['ufCrm11AgentName'] ?? '') . ']]></Listing_Agent>';
        $xml .= '<Listing_Agent_Phone><![CDATA[' . ($property['ufCrm11AgentPhone'] ?? '') . ']]></Listing_Agent_Phone>';
        $xml .= '<Listing_Agent_Email><![CDATA[' . ($property['ufCrm11AgentEmail'] ?? '') . ']]></Listing_Agent_Email>';

        $xml .= '<Images>';
        foreach ($property['ufCrm11PhotoLinks'] ?? [] as $image) {
            $xml .= '<Image last_update="' . date('Y-m-d H:i:s') . '"><![CDATA[' . $image . ']]></Image>';
        }
        $xml .= '</Images>';

        if (!empty($property['ufCrm11Amenities']) && is_array($property['ufCrm11Amenities'])) {
            $xml .= '<Features>';
            foreach ($property['ufCrm11Amenities'] as $amenity) {
                $fullName = getFullAmenityName(trim($amenity));
                $xml .= '<Feature><![CDATA[' . $fullName . ']]></Feature>';
            }
            $xml .= '</Features>';
        }

        $xml .= '</Property>';
    }

    $xml .= '</Properties>';
    return $xml;
}

function uploadFile($file, $isDocument = false)
{
    global $cloudinary;

    try {
        if (!file_exists($file)) {
            throw new Exception("File not found: " . $file);
        }

        $uploadResponse = $cloudinary->uploadApi()->upload($file, [
            'folder' => 'property-listing-uploads',
            'resource_type' => $isDocument ? 'raw' : 'image',
        ]);

        return $uploadResponse['secure_url'];
    } catch (Exception $e) {
        error_log("Error uploading image: " . $e->getMessage());
        echo "Error uploading image: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        return false;
    }
}

function fetchCurrentUser()
{
    $response = CRestCurrent::call('user.current');
    return $response['result'];
}

function getUser($filter)
{
    $response = CRest::call("user.get", [
        'filter' => $filter
    ]);
    return $response['result'][0];
}

function isAdmin($userId)
{
    $admins = [
        13, // Vortexweb  
        1, // Afthab
        49, // Rehan Shaikh
    ];

    return in_array($userId, $admins);
}


function generateWebsiteJson($properties)
{
    $json = json_encode([
        'properties' => $properties,
        'total' => count($properties)
    ]);

    return $json;
}
