<?php

require __DIR__ . "/crest/crest.php";
require __DIR__ . "/crest/crestcurrent.php";
require __DIR__ . "/crest/settings.php";
require __DIR__ . "/utils/index.php";
require __DIR__ . "/vendor/autoload.php";

define('C_REST_WEB_HOOK_URL', 'https://zealwayproperties.bitrix24.com/rest/13/wso670w02zhpalic/');

// Fetch URL params
$type = $_GET['type'];
$id   = $_GET['id'];

// Retrieve the property from Bitrix24
$response = CRest::call('crm.item.get', [
  "entityTypeId" => LISTINGS_ENTITY_TYPE_ID,
  "id"           => $id
]);

$property = $response['result']['item'];

if (!$property) {
  die("Property not found.");
}

// Utility function to sanitize file names
function sanitizeFileName($filename)
{
  $filename = trim($filename);
  $filename = str_replace(' ', '_', $filename);
  $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
  $filename = preg_replace('/_+/', '_', $filename);
  return $filename;
}

// Prepare data
$file_name     = !empty($property['ufCrm11ReferenceNumber'])
  ? sanitizeFileName($property['ufCrm11ReferenceNumber']) . ".pdf"
  : "Property_$id.pdf";

$title         = $property['ufCrm11TitleEn']       ?? "No Title";
$price         = !empty($property['ufCrm11Price']) ? "AED " . $property['ufCrm11Price'] : "Not Available";
$refId         = $property['ufCrm11ReferenceNumber'] ?? "";
$location      = $property['ufCrm11Location']      ?? "Unknown";
$description   = $property['ufCrm11DescriptionEn'] ?? "No Description";
$images        = $property['ufCrm11PhotoLinks']    ?? [];
$mainImage     = isset($images[0]) ? $images[0] : '';
$size          = $property['ufCrm11Size']          ?? "0";
$bedrooms      = $property['ufCrm11Bedroom']       ?? "0";
$bathrooms     = $property['ufCrm11Bathroom']      ?? "0";
$propertyType  = $property['ufCrm11PropertyType']  ?? "Unknown";
$offeringType = $property['ufCrm11OfferingType'] ?? "Unknown";
$availability  = $property['ufCrm11Availability']  ?? "Unknown";
$geopoints     = $property['ufCrm11Geopoints']     ?? null;

// Company info
$companyLogoPath = __DIR__ . "/assets/images/company-logo.png";
$companyName    = "ZealWay Properties";
$companyAddress = "Office No. 403 Hamsah Building, Ansar Gallery, Al Karama, Dubai P.O Box - 27183";
$companyWebsite = "https://zealwayproperties.com/";

// Agent/Owner info
if ($type === 'agent') {
  $agentName  = $property['ufCrm11AgentName']  ?? "ZealWay Properties";
  $agentEmail = $property['ufCrm11AgentEmail'] ?? "info@zealwayproperties.com";
  $agentPhone = $property['ufCrm11AgentPhone'] ?? "+971 4 321 7447";
} elseif ($type === 'owner') {
  $agentName  = $property['ufCrm11ListingOwner'] ?? "ZealWay Properties";
  // Attempt to fetch owner details from Bitrix
  $userResponse = CRest::call("user.get", [
    "filter" => ["NAME" => $property['ufCrm11ListingOwner']]
  ]);
  $owner       = $userResponse['result'][0] ?? [];
  $agentEmail  = $owner["EMAIL"]          ?? "info@zealwayproperties.com";
  $agentPhone  = $owner["PERSONAL_MOBILE"] ?? "+971 4 321 7447";
} else {
  // Default to current user
  $currentUserResponse = CRestCurrent::call('user.current');
  $user       = $currentUserResponse['result'];
  $agentName  = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
  $agentEmail = $user['EMAIL'];
  $agentPhone = $user['PERSONAL_MOBILE'] ?? "+971 4 321 7447";
}

// Amenities
$amenities = $property['ufCrm11Amenities'] ?? [];

// Function to convert image to Base64
function imageToBase64($path)
{
  $type = pathinfo($path, PATHINFO_EXTENSION);
  $data = file_get_contents($path);
  if ($data) {
    $base64 = base64_encode($data);
    return 'data:image/' . $type . ';base64,' . $base64;
  }
  return null;
}

// Convert company logo to Base64
$base64Logo = imageToBase64($companyLogoPath);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>
  <style>
    /* 
      ShadCN-inspired design 
      Using subtle shadows, rounded corners, clean sans-serif typography
    */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 24px;
      color: #1A1A1A;
      font-size: 14px;
      line-height: 1.5;
      background-color: #FFFFFF;
    }

    h1,
    h2,
    h3,
    h4 {
      margin: 0;
      padding: 0;
      font-weight: 600;
      color: #111827;
    }

    /* Utility classes */
    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .text-blue {
      color: #1D4ED8;
    }

    .bg-light-gray {
      background-color: #F3F4F6;
    }

    /* Light grey */
    .rounded-xl {
      border-radius: 12px;
    }

    .rounded-2xl {
      border-radius: 16px;
    }

    .shadow {
      box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 12px;
    }

    .mb-2 {
      margin-bottom: 8px;
    }

    .mb-3 {
      margin-bottom: 12px;
    }

    .mb-4 {
      margin-bottom: 16px;
    }

    .mb-5 {
      margin-bottom: 20px;
    }

    .mb-6 {
      margin-bottom: 24px;
    }

    .mr-2 {
      margin-right: 8px;
    }

    .mr-3 {
      margin-right: 12px;
    }

    .mr-4 {
      margin-right: 16px;
    }

    .flex {
      display: flex;
    }

    .flex-col {
      flex-direction: column;
    }

    .justify-between {
      justify-content: space-between;
    }

    .items-center {
      align-items: center;
    }

    .gap-2 {
      gap: 8px;
    }

    .gap-3 {
      gap: 12px;
    }

    .gap-4 {
      gap: 16px;
    }

    .p-4 {
      padding: 16px;
    }

    .p-3 {
      padding: 12px;
    }

    .font-bold {
      font-weight: 700;
    }

    /* Top Section: Hero Images */
    .hero-container {
      display: flex;
      flex-wrap: nowrap;
      /* keep on one row if possible */
      margin-bottom: 24px;
      gap: 16px;
    }

    .hero-main {
      width: 70%;
      position: relative;
    }

    .hero-side {
      width: 30%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      /* vertically center smaller images */
      gap: 16px;
    }

    .hero-image {
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    /* Title & Price Section */
    .title-price-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .title-center {
      flex: 1;
      text-align: center;
    }

    .price-right {
      min-width: 160px;
      text-align: right;
    }

    .logo-left {
      width: 100px;
      height: auto;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo-left img {
      max-width: 100%;
      height: auto;
      border-radius: 4px;
    }

    .sale-badge {
      display: inline-block;
      background-color: #1D4ED8;
      color: #FFFFFF;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      margin-left: 8px;
    }

    .ref-id {
      font-size: 12px;
      color: #6B7280;
    }

    /* Property Details & Additional Images */
    .split-layout {
      display: flex;
      gap: 24px;
      margin-bottom: 24px;
    }

    .split-left,
    .split-right {
      flex: 1;
    }

    .property-desc {
      margin-bottom: 16px;
    }

    .image-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      /* 2x2 layout */
      gap: 12px;
    }

    .grid-image {
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    /* Facilities & Amenities */
    .amenities-section {
      padding: 24px;
      margin-bottom: 24px;
    }

    .amenities-list {
      display: grid;
      grid-template-columns: 1fr 1fr;
      /* or 3 columns if you prefer */
      gap: 8px;
      margin-top: 16px;
    }

    .amenity-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      padding: 6px 8px;
      border-radius: 8px;
      background-color: #FFFFFF;
      box-shadow: rgba(0, 0, 0, 0.05) 0px 1px 3px;
    }

    /* Location Section */
    .location-section {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
    }

    .location-map {
      flex: 1;
    }

    .location-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 8px;
    }

    .map-iframe {
      width: 100%;
      height: 220px;
      border: 0;
      border-radius: 12px;
      box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 12px;
    }

    /* Contact (Agent) Section */
    .agent-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px;
      border-radius: 8px;
      box-shadow: rgba(0, 0, 0, 0.05) 0px 1px 3px;
    }

    .agent-photo {
      width: 60px;
      height: 60px;
      border-radius: 9999px;
      /* fully circular */
      object-fit: cover;
      background-color: #E5E7EB;
      /* placeholder grey */
    }

    .agent-details {
      flex: 1;
    }

    .enquiry-btn {
      display: inline-block;
      background-color: #1D4ED8;
      color: #FFF;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
    }

    /* Basic headings spacing */
    .section-heading {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 8px;
      border-bottom: 1px solid #E5E7EB;
      padding-bottom: 4px;
    }

    /* Page breaks (for multi-page PDFs) */
    .page-break {
      page-break-before: always;
    }

    /* Download PDF Button styling */
    .download-pdf-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      background: #1D4ED8;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
      font-weight: 600;
    }

    .download-pdf-btn:hover {
      background: #1E3A8A;
      transform: translateY(-2px);
    }

    @media print {
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>

<body>
  <button class="download-pdf-btn no-print" onclick="downloadPDF()">Download PDF</button>


  <!-- 1️⃣ Top Section: Hero Images -->
  <div class="hero-container">
    <!-- Left: Large primary image (70%) -->
    <div class="hero-main">
      <?php if ($mainImage): ?>
        <img
          src="<?= htmlspecialchars($mainImage) ?>"
          alt="Main Property Image"
          class="hero-image shadow rounded-2xl">
      <?php else: ?>
        <div style="padding: 40px; background-color: #f0f0f0;"
          class="shadow rounded-2xl text-center">
          No main image available
        </div>
      <?php endif; ?>
    </div>

    <!-- Right: Two stacked smaller images (30%) -->
    <div class="hero-side">
      <?php if (isset($images[1])): ?>
        <img
          src="<?= htmlspecialchars($images[1]) ?>"
          alt="Side Image 1"
          class="hero-image shadow rounded-2xl">
      <?php endif; ?>

      <?php if (isset($images[2])): ?>
        <img
          src="<?= htmlspecialchars($images[2]) ?>"
          alt="Side Image 2"
          class="hero-image shadow rounded-2xl">
      <?php endif; ?>
    </div>
  </div>

  <!-- 2️⃣ Title & Price Section -->
  <div class="title-price-row">
    <!-- Company Logo -->
    <div class="logo-left">
      <?php if ($base64Logo): ?>
        <img
          src="<?= $base64Logo ?>"
          alt="Company Logo">
      <?php else: ?>
        Logo not available
      <?php endif; ?>
    </div>

    <!-- Title (center) -->
    <div class="title-center">
      <h1><?= htmlspecialchars($title) ?>
        <span class="sale-badge">
          <?= ($offeringType === "CS" || $offeringType === "RS") ? "FOR SALE" : "FOR RENT" ?>
        </span>
      </h1>
    </div>

    <!-- Price (right) -->
    <div class="price-right">
      <h2 class="text-blue font-bold mb-1"><?= htmlspecialchars($price) ?></h2>
      <?php if (!empty($refId)): ?>
        <div class="ref-id">Ref: <?= htmlspecialchars($refId) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 3️⃣ Property Details & Additional Interior Images -->
  <div class="split-layout">
    <!-- Left Column: Property Description -->
    <div class="split-left">
      <h3 class="section-heading">Property Details</h3>
      <div class="property-desc">
        <p><strong>Location:</strong> <?= htmlspecialchars($location) ?></p>
        <p><strong>Size:</strong> <?= htmlspecialchars($size) ?> sqft</p>
        <p><strong>Bedrooms:</strong> <?= htmlspecialchars($bedrooms) ?></p>
        <p><strong>Bathrooms:</strong> <?= htmlspecialchars($bathrooms) ?></p>
        <p><strong>Type:</strong> <?= htmlspecialchars($propertyType) ?></p>
        <?php if (!empty($availability)): ?>
          <p><strong>Availability:</strong> <?= htmlspecialchars($availability) ?></p>
        <?php endif; ?>
      </div>
      <p><?= nl2br(htmlspecialchars($description)) ?></p>
    </div>

    <!-- Right Column: 4 smaller images in a 2x2 grid -->
    <div class="split-right">
      <div class="image-grid">
        <?php
        // Start from image index 3 to show additional interior images
        for ($i = 3; $i < 7; $i++):
          if (isset($images[$i])):
        ?>
            <img
              src="<?= htmlspecialchars($images[$i]) ?>"
              alt="Interior Image <?= $i ?>"
              class="grid-image shadow rounded-xl">
          <?php else: ?>
            <!-- If not enough images, show placeholders or skip -->
        <?php endif;
        endfor; ?>
      </div>
    </div>
  </div>

  <!-- 4️⃣ Facilities & Amenities (Full Width, Light Grey Background) -->
  <div class="amenities-section bg-light-gray rounded-xl shadow">
    <h3 class="section-heading text-center">Facilities and Amenities</h3>
    <?php if (!empty($amenities)): ?>
      <div class="amenities-list">
        <?php foreach ($amenities as $amenity): ?>
          <div class="amenity-item">
            <!-- Icon placeholder or use your own icons -->
            <span>🏷️</span>
            <span><?= htmlspecialchars(getFullAmenityName($amenity)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-center mb-0">No amenities listed.</p>
    <?php endif; ?>
  </div>

  <!-- 5️⃣ Location Section -->
  <?php if ($geopoints):
    $coords = explode(',', $geopoints);
    $lat = trim($coords[0]);
    $lng = trim($coords[1]);
  ?>
    <div class="location-section">
      <!-- Left: Map -->
      <div class="location-map">
        <iframe
          class="map-iframe"
          src="https://maps.google.com/maps?q=<?= $lat ?>,<?= $lng ?>&hl=en&z=14&output=embed"
          allowfullscreen></iframe>
      </div>

      <!-- Right: Location Details -->
      <div class="location-info">
        <h3 class="section-heading">Location</h3>
        <p><strong><?= htmlspecialchars($location) ?></strong></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- 6️⃣ Contact Information (Agent Details) -->
  <h3 class="section-heading">Contact Information</h3>
  <div class="agent-card">
    <!-- Agent Photo (placeholder or real) -->
    <img
      src="https://cdn.pixabay.com/photo/2016/08/08/09/17/avatar-1577909_960_720.png"
      alt="Agent Photo"
      class="agent-photo">
    <div class="agent-details">
      <strong><?= htmlspecialchars($agentName) ?></strong><br>
      <?= htmlspecialchars($agentPhone) ?><br>
      <small><?= htmlspecialchars($agentEmail) ?></small>
    </div>
    <a href="mailto:<?= htmlspecialchars($agentEmail) ?>" class="enquiry-btn">Make an Enquiry</a>
  </div>
  <script>
    function downloadPDF() {
      document.querySelector('.download-pdf-btn').style.display = 'none';
      window.print();

      setTimeout(() => {
        document.querySelector('.download-pdf-btn').style.display = 'block';
      }, 10);
    }
  </script>

</body>

</html>