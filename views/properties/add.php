<div class="w-4/5 mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <form class="w-full space-y-4" id="addPropertyForm" onsubmit="handleAddProperty(event)" enctype="multipart/form-data">
            <!-- Management -->
            <?php include_once('views/components/add-property/management.php'); ?>
            <!-- Specifications -->
            <?php include_once('views/components/add-property/specifications.php'); ?>
            <!-- Property Permit -->
            <?php include_once('views/components/add-property/permit.php'); ?>
            <!-- Pricing -->
            <?php include_once('views/components/add-property/pricing.php'); ?>
            <!-- Title and Description -->
            <?php include_once('views/components/add-property/title.php'); ?>
            <!-- Amenities -->
            <?php include_once('views/components/add-property/amenities.php'); ?>
            <!-- Location -->
            <?php include_once('views/components/add-property/location.php'); ?>
            <!-- Photos and Videos -->
            <?php include_once('views/components/add-property/media.php'); ?>
            <!-- Floor Plan -->
            <?php include_once('views/components/add-property/floorplan.php'); ?>
            <!-- Documents -->
            <?php // include_once('views/components/add-property/documents.php'); 
            ?>
            <!-- Notes -->
            <?php include_once('views/components/add-property/notes.php'); ?>
            <!-- Portals -->
            <?php include_once('views/components/add-property/portals.php'); ?>
            <!-- Status -->
            <?php include_once('views/components/add-property/status.php'); ?>

            <div class="mt-6 flex justify-end space-x-4">
                <button type="button" onclick="javascript:history.back()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1">
                    Back
                </button>
                <button type="submit" id="submitButton" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById("offering_type").addEventListener("change", function() {
        const offeringType = this.value;
        console.log(offeringType);

        if (offeringType == 'RR' || offeringType == 'CR') {
            document.getElementById("rental_period").setAttribute("required", true);
            document.querySelector('label[for="rental_period"]').innerHTML = 'Rental Period (if rental) <span class="text-danger">*</span>';
        } else {
            document.getElementById("rental_period").removeAttribute("required");
            document.querySelector('label[for="rental_period"]').innerHTML = 'Rental Period (if rental)';
        }
    })

    async function addItem(entityTypeId, fields) {
        try {
            const response = await fetch(`${API_BASE_URL}crm.item.add?entityTypeId=${entityTypeId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fields,
                }),
            });

            if (response.ok) {
                window.location.href = 'index.php?page=properties';
            } else {
                console.error('Failed to add item');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function handleAddProperty(e) {
        e.preventDefault();

        document.getElementById('submitButton').disabled = true;
        document.getElementById('submitButton').innerHTML = 'Submitting...';

        const form = document.getElementById('addPropertyForm');
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = typeof value === 'string' ? value.trim() : value;
        });

        const agent = await getAgent(data.listing_agent);

        const selectedOwner = data.listing_owner;
        const [ownerId, ownerName] = selectedOwner.split('-', 2);

        const fields = {
            "ufCrm11TitleDeed": data.title_deed,
            "ufCrm11ReferenceNumber": data.reference,
            "ufCrm11OfferingType": data.offering_type,
            "ufCrm11PropertyType": data.property_type,
            "ufCrm11Price": data.price,
            "ufCrm11TitleEn": data.title_en,
            "ufCrm11DescriptionEn": data.description_en,
            "ufCrm11TitleAr": data.title_ar,
            "ufCrm11DescriptionAr": data.description_ar,
            "ufCrm11Size": data.size,
            "ufCrm11Bedroom": data.bedrooms,
            "ufCrm11Bathroom": data.bathrooms,
            "ufCrm11Parking": data.parkings,
            "ufCrm11Geopoints": `${data.latitude}, ${data.longitude}`,
            "ufCrm11PermitNumber": data.dtcm_permit_number,
            "ufCrm11RentalPeriod": data.rental_period,
            "ufCrm11Furnished": data.furnished,
            "ufCrm11TotalPlotSize": data.total_plot_size,
            "ufCrm11LotSize": data.lot_size,
            "ufCrm11BuildupArea": data.buildup_area,
            "ufCrm11LayoutType": data.layout_type,
            "ufCrm11ProjectName": data.project_name,
            "ufCrm11ProjectStatus": data.project_status,
            "ufCrm11Ownership": data.ownership,
            "ufCrm11Developers": data.developer,
            "ufCrm11BuildYear": data.build_year,
            "ufCrm11Availability": data.availability,
            "ufCrm11AvailableFrom": data.available_from,
            "ufCrm11PaymentMethod": data.payment_method,
            "ufCrm11DownPaymentPrice": data.downpayment_price,
            "ufCrm11NoOfCheques": data.cheques,
            "ufCrm11ServiceCharge": data.service_charge,
            "ufCrm11FinancialStatus": data.financial_status,
            "ufCrm11VideoTourUrl": data.video_tour_url,
            "ufCrm_18_360_VIEW_URL": data["360_view_url"],
            "ufCrm11QrCodePropertyBooster": data.qr_code_url,
            "ufCrm11Location": data.pf_location,
            "ufCrm11City": data.pf_city,
            "ufCrm11Community": data.pf_community,
            "ufCrm11SubCommunity": data.pf_subcommunity,
            "ufCrm11Tower": data.pf_building,
            "ufCrm11BayutLocation": data.bayut_location,
            "ufCrm11BayutCity": data.bayut_city,
            "ufCrm11BayutCommunity": data.bayut_community,
            "ufCrm11BayutSubCommunity": data.bayut_subcommunity,
            "ufCrm11BayutTower": data.bayut_building,
            "ufCrm11Status": data.status,
            "ufCrm11ReraPermitNumber": data.rera_permit_number,
            "ufCrm11ReraPermitIssueDate": data.rera_issue_date,
            "ufCrm11ReraPermitExpirationDate": data.rera_expiration_date,
            "ufCrm11DtcmPermitNumber": data.dtcm_permit_number,
            "ufCrm11ListingOwner": ownerName,
            "ufCrm11ListingOwnerId": ownerId,

            "ufCrm11LandlordName": data.landlord_name,
            "ufCrm11LandlordEmail": data.landlord_email,
            "ufCrm11LandlordContact": data.landlord_phone,

            "ufCrm11ContractExpiryDate": data.contract_expiry,
            "ufCrm11UnitNo": data.unit_no,
            "ufCrm11SaleType": data.sale_type,
            "ufCrm11BrochureDescription": data.brochure_description_1,
            "ufCrm_12_BROCHURE_DESCRIPTION_2": data.brochure_description_2,
            "ufCrm11HidePrice": data.hide_price == "on" ? "Y" : "N",
            "ufCrm11PfEnable": data.pf_enable == "on" ? "Y" : "N",
            "ufCrm11BayutEnable": data.bayut_enable == "on" ? "Y" : "N",
            "ufCrm11DubizzleEnable": data.dubizzle_enable == "on" ? "Y" : "N",
            "ufCrm11WebsiteEnable": data.website_enable == "on" ? "Y" : "N",
            "ufCrm11Watermark": data.watermark == "on" ? "Y" : "N",
        };

        if (agent) {
            fields["ufCrm11AgentId"] = agent.ufCrm17AgentId;
            fields["ufCrm11AgentName"] = agent.ufCrm17AgentName;
            fields["ufCrm11AgentEmail"] = agent.ufCrm17AgentEmail;
            fields["ufCrm11AgentPhone"] = agent.ufCrm17AgentMobile;
            fields["ufCrm11AgentPhoto"] = agent.ufCrm17AgentPhoto;
            fields["ufCrm11AgentLicense"] = agent.ufCrm17AgentLicense;
        }

        // Notes
        const notesString = data.notes;
        if (notesString) {
            const notesArray = JSON.parse(notesString);
            if (notesArray) {
                fields["ufCrm11Notes"] = notesArray;
            }
        }

        // Amenities
        const amenitiesString = data.amenities;
        if (amenitiesString) {
            const amenitiesArray = JSON.parse(amenitiesString);
            if (amenitiesArray) {
                fields["ufCrm11Amenities"] = amenitiesArray;
            }
        }

        // Property Photos
        const photos = document.getElementById('selectedImages').value;
        if (photos) {
            const fixedPhotos = photos.replace(/\\'/g, '"');
            const photoArray = JSON.parse(fixedPhotos);
            const watermarkPath = 'assets/images/watermark.webp?cache=' + Date.now();
            const uploadedImages = await processBase64Images(photoArray, watermarkPath, data.watermark === "on");

            if (uploadedImages.length > 0) {
                fields["ufCrm11PhotoLinks"] = uploadedImages;
            }
        }

        // Floorplan
        const floorplan = document.getElementById('selectedFloorplan').value;
        if (floorplan) {
            const fixedFloorplan = floorplan.replace(/\\'/g, '"');
            const floorplanArray = JSON.parse(fixedFloorplan);
            const watermarkPath = 'assets/images/watermark.webp?cache=' + Date.now();
            const uploadedFloorplan = await processBase64Images(floorplanArray, watermarkPath, data.watermark === "on");

            if (uploadedFloorplan.length > 0) {
                fields["ufCrm11FloorPlan"] = uploadedFloorplan[0];
            }
        }

        // Documents
        // const documents = document.getElementById('documents')?.files;
        // if (documents) {
        //     if (documents.length > 0) {
        //         let documentUrls = [];

        //         for (const document of documents) {
        //             if (document.size > 10485760) {
        //                 alert('File size must be less than 10MB');
        //                 return;
        //             }
        //             const uploadedDocument = await uploadFile(document);
        //             documentUrls.push(uploadedDocument);
        //         }

        //         fields["ufCrm11Documents"] = documentUrls;
        //     }

        // }

        // Add to CRM
        addItem(LISTINGS_ENTITY_TYPE_ID, fields, '?page=properties');
    }
</script>