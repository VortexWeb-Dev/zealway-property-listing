<div class="container mx-auto py-8">

    <div id="property-details" class="flex-1 bg-white shadow-md rounded-lg p-6">
        <div class="flex justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800" id="property-title">Loading...</h2>
                <h3 class="text-lg text-gray-600 mb-4"><i class="fas fa-map-marker"></i> <span id="property-location"></span> </h3>
            </div>
            <div>
                <h3 class="text-lg text-gray-600 mb-4" id="property-price"></h3>
            </div>
        </div>

        <img src="" id="main-image" alt="Main Image" class="w-full h-64 object-cover rounded-lg mb-4">
        <p class="text-gray-600 mb-6 text-wrap" id="property-description">Please wait while we fetch property details...</p>

        <!-- Grid Display -->
        <div class="flex justify-between gap-6">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-700">General Information</h3>
                <ul class="mt-2 space-y-2 text-md text-gray-600">
                    <li><span class="text-gray-800">City:</span> <span id="property-city"></span></li>
                    <li><span class="text-gray-800">Community:</span> <span id="property-community"></span></li>
                    <li><span class="text-gray-800">Price:</span> <span id="property-price"></span></li>
                    <li><span class="text-gray-800">Size:</span> <span id="property-size"></span></li>
                    <li><span class="text-gray-800">Bedrooms:</span> <span id="property-bedrooms"></span></li>
                    <li><span class="text-gray-800">Bathrooms:</span> <span id="property-bathrooms"></span></li>
                    <li><span class="text-gray-800">Property Type:</span> <span id="property-type"></span></li>
                </ul>
            </div>

            <div class="flex-1">
                <h3 class="font-semibold text-gray-700">Additional Information</h3>
                <ul class="mt-2 space-y-2 text-md text-gray-600">
                    <li><span class="text-gray-800">Status:</span> <span id="property-status"></span></li>
                    <li><span class="text-gray-800">Listing Owner:</span> <span id="property-owner"></span></li>
                    <li><span class="text-gray-800">Agent Name:</span> <span id="agent-name"></span></li>
                    <li><span class="text-gray-800">Agent Phone:</span> <span id="agent-phone"></span></li>
                    <li><span class="text-gray-800">Agent Email:</span> <span id="agent-email"></span></li>
                </ul>
            </div>
        </div>

        <div class="flex-1 bg-white shadow-md rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Images</h3>
            <div id="property-images" class="grid grid-cols-4 gap-4 overflow-y-auto">

            </div>
        </div>

        <div class="flex justify-between gap-6 mt-6">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-700">Comments</h3>
                <ul class="mt-2 space-y-2 text-md text-gray-600" id="property-comments"></ul>
                </ul>
            </div>
        </div>

    </div>


</div>

<script>
    function formatPrice(amount, locale = 'en-US', currency = 'AED') {
        if (isNaN(amount)) {
            return 'Invalid amount';
        }

        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    async function fetchProperty(id) {
        const url = `${API_BASE_URL}crm.item.get?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${id}`;
        const response = await fetch(url);
        const data = await response.json();
        if (data.result && data.result.item) {
            const property = data.result.item;

            document.getElementById('property-title').textContent = property.ufCrm11TitleEn || 'No title available';
            document.getElementById('property-description').innerHTML = '<pre>' + property.ufCrm11DescriptionEn + '</pre>' || 'No description available';
            document.getElementById('property-city').textContent = property.ufCrm11City || 'N/A';
            document.getElementById('property-community').textContent = property.ufCrm11Community || 'N/A';
            let priceText = property.ufCrm11Price ? formatPrice(property.ufCrm11Price) : 'N/A';
            if (property.ufCrm11RentalPeriod && property.ufCrm11RentalPrice) {
                const rentalPeriodMapping = {
                    Y: 'Year',
                    M: 'Month',
                    W: 'Week',
                    D: 'Day',
                };

                const rentalPeriod = rentalPeriodMapping[property.ufCrm11RentalPrice] || property.ufCrm11RentalPeriod;
                priceText += `/ ${rentalPeriod}`;
            }
            document.getElementById('property-price').textContent = priceText;
            document.getElementById('property-size').textContent = property.ufCrm11Size ? `${property.ufCrm11Size} sqft` : 'N/A';
            document.getElementById('property-bedrooms').textContent = property.ufCrm11Bedroom || 'N/A';
            document.getElementById('property-bathrooms').textContent = property.ufCrm11Bathroom || 'N/A';
            document.getElementById('property-type').textContent = property.ufCrm11PropertyType || 'N/A';
            document.getElementById('property-location').textContent = property.ufCrm11Location || 'N/A';
            document.getElementById('property-status').textContent = property.ufCrm11Status || 'N/A';
            document.getElementById('property-owner').textContent = property.ufCrm11ListingOwner || 'N/A';
            document.getElementById('agent-name').textContent = property.ufCrm11AgentName || 'N/A';
            document.getElementById('agent-phone').textContent = property.ufCrm11AgentPhone || 'N/A';
            document.getElementById('agent-email').textContent = property.ufCrm11AgentEmail || 'N/A';
            document.getElementById('main-image').src = property.ufCrm11PhotoLinks[0] || 'https://placehold.jp/150x150.png';

            const images = property.ufCrm11PhotoLinks || [];
            const imageContainer = document.getElementById('property-images');
            images.forEach(image => {
                const imageElement = document.createElement('img');
                imageElement.src = image;
                imageElement.alt = 'Property Image';
                imageElement.classList.add('w-full', 'h-64', 'object-cover');
                imageContainer.appendChild(imageElement);
            });

            function extractNoteAndTime(noteString) {
                const match = noteString.match(/^(.*)\s-\s\[(.*)\]$/);
                if (match) {
                    return {
                        text: match[1].trim(),
                        timestamp: match[2].trim()
                    };
                } else {
                    console.error("Invalid note format:", noteString);
                    return {
                        text: "",
                        timestamp: ""
                    };
                }
            }

            const comments = property.ufCrm11Notes || [];
            const commentsContainer = document.getElementById("property-comments");

            if (comments.length === 0) {
                const commentElement = document.createElement("li");
                commentElement.classList.add("p-3", "border", "border-gray-300", "rounded-lg", "shadow-sm", "mb-2", "bg-gray-50");
                commentElement.innerHTML = `
        <div class="text-gray-700 font-medium">No comments available.</div>
    `;
            }

            comments.forEach(comment => {
                const {
                    text,
                    timestamp
                } = extractNoteAndTime(comment);

                const commentElement = document.createElement("li");
                commentElement.classList.add("p-3", "border", "border-gray-300", "rounded-lg", "shadow-sm", "mb-2", "bg-gray-50");

                commentElement.innerHTML = `
        <div class="text-gray-700 font-medium">${text}</div>
        <div class="text-xs text-gray-500 text-right">${timestamp}</div>
    `;

                commentsContainer.appendChild(commentElement);
            });
        } else {
            console.error('Invalid property data:', data);
            document.getElementById('property-details').textContent = 'Failed to load property details.';
        }
    }

    fetchProperty(<?php echo $_GET['id']; ?>);
</script>