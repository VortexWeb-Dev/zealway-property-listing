<footer class="w-4/5 mx-auto mb-8 px-4 text-center">
    &copy; <?php echo date("Y"); ?> <a href="https://vortexweb.cloud/" target="_blank">VortexWeb</a>
</footer>
<a href="current.php" class="opacity-0 text-xs">invisible link</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./node_modules/lodash/lodash.min.js"></script>
<script src="./node_modules/dropzone/dist/dropzone-min.js"></script>
<script src="./node_modules/preline/dist/preline.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="./node_modules/lodash/lodash.min.js"></script>
<script src="./node_modules/apexcharts/dist/apexcharts.min.js"></script>
<script src="./node_modules/preline/dist/helper-apexcharts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fabric@latest/dist/index.min.js"></script>
<script src="assets/js/script.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const isAdmin = localStorage.getItem('isAdmin') == 'true';

        if (!isAdmin) {
            document.querySelectorAll(".admin-only").forEach(el => el.style.display = "none");
        }
    });

    // Toggle Bayut and Dubizzle
    document.getElementById('toggle_bayut_dubizzle') && document.getElementById('toggle_bayut_dubizzle').addEventListener('change', function() {
        const isChecked = this.checked;
        document.getElementById('bayut_enable').checked = isChecked;
        document.getElementById('dubizzle_enable').checked = isChecked;
    });

    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        };
        return date.toLocaleDateString('en-US', options);
    }

    // Update character count
    function updateCharCount(countElement, length, maxLength) {
        titleCount = document.getElementById(countElement);
        titleCount.textContent = length;

        if (length >= maxLength) {
            titleCount.parentElement.classList.add('text-danger');
        } else {
            titleCount.parentElement.classList.remove('text-danger');
        }
    }

    // Parse and update location fields
    function updateLocationFields(location, type) {
        const locationParts = location.split('-');

        const city = locationParts[0].trim();
        const community = locationParts[1].trim();
        const subcommunity = locationParts[2].trim() || null;
        const building = locationParts[3].trim() || null;

        document.getElementById(`${type}_city`).value = city;
        document.getElementById(`${type}_community`).value = community;
        document.getElementById(`${type}_subcommunity`).value = subcommunity;
        document.getElementById(`${type}_building`).value = building;
    }

    // Update reference
    async function handleUpdateReference(event) {
        event.preventDefault();

        const formData = new FormData(event.target);
        const propertyId = formData.get('propertyId');
        const newReference = formData.get('newReference');

        try {
            const response = await fetch(`${API_BASE_URL}crm.item.update?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}&fields[ufCrm11ReferenceNumber]=${newReference}`);
            const data = await response.json();
            location.reload();
        } catch (error) {
            console.error('Error updating reference:', error);
        }
    }

    // Format input date
    function formatInputDate(dateInput) {
        if (!dateInput) return null;

        const date = new Date(dateInput);

        if (isNaN(date.getTime())) return null;

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    // Get agent
    async function getAgent(agentId) {
        const response = await fetch(`${API_BASE_URL}crm.item.list?entityTypeId=${AGENTS_ENTITY_ID}&filter[ufCrm17AgentId]=${agentId}`);
        const data = await response.json();
        return data.result.items[0] || null;
    }

    // Handle action
    async function handleAction(action, propertyId, platform = null) {
        const baseUrl = API_BASE_URL;
        let apiUrl = '';
        let reloadRequired = true;

        switch (action) {
            case 'copyLink':
                const link = `https://lightgray-kudu-834713.hostingersite.com/property-listing-gi/index.php?page=view-property&id=${propertyId}`;
                navigator.clipboard.writeText(link);
                alert('Link copied to clipboard.');
                reloadRequired = false;
                break;

            case 'downloadPDF':
                window.location.href = `download-pdf.php?id=${propertyId}`;
                reloadRequired = false;
                break;
            case 'downloadPDFAgent':
                window.location.href = `download-pdf-agent.php?id=${propertyId}`;
                reloadRequired = false;
                break;
            case 'export-excel':
                window.location.href = `export-excel.php?id=${propertyId}`;
                reloadRequired = false;
                break;

            case 'duplicate':
                try {
                    const getUrl = `${baseUrl}/crm.item.get?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}&select[0]=id&select[1]=uf_*`;
                    const response = await fetch(getUrl, {
                        method: 'GET'
                    });
                    const data = await response.json();
                    const property = data.result.item;

                    let addUrl = `${baseUrl}/crm.item.add?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}`;
                    for (const field in property) {
                        if (
                            field.startsWith('ufCrm11') &&
                            !['ufCrm11ReferenceNumber', 'ufCrm11TitleEn', 'ufCrm11Status', 'ufCrm11PhotoLinks', 'ufCrm11Documents', 'ufCrm11Notes'].includes(field)
                        ) {
                            addUrl += `&fields[${field}]=${encodeURIComponent(property[field])}`;
                        }
                    }

                    if (property['ufCrm11PhotoLinks']) {
                        property['ufCrm11PhotoLinks'].forEach((photoLink, index) => {
                            addUrl += `&fields[ufCrm11PhotoLinks][${index}]=${encodeURIComponent(photoLink)}`;
                        });
                    }

                    if (property['ufCrm11Documents']) {
                        property['ufCrm11Documents'].forEach((document, index) => {
                            addUrl += `&fields[ufCrm11Documents][${index}]=${encodeURIComponent(document)}`;
                        });
                    }

                    if (property['ufCrm11Notes']) {
                        property['ufCrm11Notes'].forEach((note, index) => {
                            addUrl += `&fields[ufCrm11Notes][${index}]=${encodeURIComponent(note)}`;
                        });
                    }

                    addUrl += `&fields[ufCrm11TitleEn]=${encodeURIComponent(property.ufCrm11TitleEn + ' (Duplicate)')}`;
                    addUrl += `&fields[ufCrm11ReferenceNumber]=${encodeURIComponent(property.ufCrm11ReferenceNumber) + '-duplicate'}`;
                    addUrl += `&fields[ufCrm11Status]=DRAFT`;

                    await fetch(addUrl, {
                        method: 'GET'
                    });
                } catch (error) {
                    console.error('Error duplicating property:', error);
                }
                break;

            case 'publish':
                apiUrl = `${baseUrl}/crm.item.update?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}&fields[ufCrm11Status]=PUBLISHED`;
                if (platform) {
                    apiUrl += `&fields[ufCrm11${platform.charAt(0).toUpperCase() + platform.slice(1)}Enable]=Y`;
                } else {
                    apiUrl += `&fields[ufCrm11PfEnable]=Y&fields[ufCrm11BayutEnable]=Y&fields[ufCrm11DubizzleEnable]=Y&fields[ufCrm11WebsiteEnable]=Y&fields[ufCrm11Status]=PUBLISHED`;
                }
                break;

            case 'unpublish':
                apiUrl = `${baseUrl}/crm.item.update?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}`;
                if (platform) {
                    apiUrl += `&fields[ufCrm11${platform.charAt(0).toUpperCase() + platform.slice(1)}Enable]=N`;
                } else {
                    apiUrl += `&fields[ufCrm11PfEnable]=N&fields[ufCrm11BayutEnable]=N&fields[ufCrm11DubizzleEnable]=N&fields[ufCrm11WebsiteEnable]=N&fields[ufCrm11Status]=UNPUBLISHED`;
                }
                break;

            case 'archive':
                if (confirm('Are you sure you want to archive this property?')) {
                    apiUrl = `${baseUrl}/crm.item.update?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}&fields[ufCrm11Status]=ARCHIVED`;
                } else {
                    reloadRequired = false;
                }
                break;

            case 'delete':
                if (confirm('Are you sure you want to delete this property?')) {
                    try {
                        // First get property details to find image URLs
                        const getPropertyUrl = `${baseUrl}/crm.item.get?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}`;
                        const propertyResponse = await fetch(getPropertyUrl);
                        const propertyData = await propertyResponse.json();

                        if (propertyData.result && propertyData.result.item) {
                            const property = propertyData.result.item;
                            console.log('Property data for deletion:', property);

                            // Delete images from S3
                            if (property.ufCrm11PhotoLinks && Array.isArray(property.ufCrm11PhotoLinks)) {
                                console.log('Found photo links:', property.ufCrm11PhotoLinks);
                                for (const imageUrl of property.ufCrm11PhotoLinks) {
                                    try {
                                        console.log('Attempting to delete image:', imageUrl);
                                        const response = await fetch('./delete-s3-object.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                fileUrl: imageUrl
                                            })
                                        });
                                        const result = await response.json();
                                        console.log('Delete response:', result);
                                        if (!result.success) {
                                            console.error(`Failed to delete image: ${result.error}`);
                                        }
                                    } catch (error) {
                                        console.error(`Error deleting S3 object: ${imageUrl}`, error);
                                    }
                                }
                            }

                            // Delete floorplan from S3 if exists
                            if (property.ufCrm11FloorPlan) {
                                try {
                                    console.log('Attempting to delete floorplan:', property.ufCrm11FloorPlan);
                                    const response = await fetch('./delete-s3-object.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            fileUrl: property.ufCrm11FloorPlan
                                        })
                                    });
                                    const result = await response.json();
                                    console.log('Floorplan delete response:', result);
                                    if (!result.success) {
                                        console.error(`Failed to delete floorplan: ${result.error}`);
                                    }
                                } catch (error) {
                                    console.error(`Error deleting S3 floorplan: ${property.ufCrm11FloorPlan}`, error);
                                }
                            }

                            // Delete documents from S3
                            if (property.ufCrm11Documents && Array.isArray(property.ufCrm11Documents)) {
                                console.log('Found documents:', property.ufCrm11Documents);
                                for (const docUrl of property.ufCrm11Documents) {
                                    try {
                                        console.log('Attempting to delete document:', docUrl);
                                        const response = await fetch('./delete-s3-object.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                fileUrl: docUrl
                                            })
                                        });
                                        const result = await response.json();
                                        console.log('Delete response:', result);
                                        if (!result.success) {
                                            console.error(`Failed to delete document: ${result.error}`);
                                        }
                                    } catch (error) {
                                        console.error(`Error deleting S3 document: ${docUrl}`, error);
                                    }
                                }
                            }
                        }

                        // Now delete the property from CRM
                        apiUrl = `${baseUrl}/crm.item.delete?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}`;
                    } catch (error) {
                        console.error('Error in delete process:', error);
                        reloadRequired = false;
                    }
                } else {
                    reloadRequired = false;
                }
                break;

            default:
                console.error('Invalid action:', action);
                reloadRequired = false;
        }

        if (apiUrl) {
            try {
                await fetch(apiUrl, {
                    method: 'GET'
                });
            } catch (error) {
                console.error(`Error executing ${action}:`, error);
            }
        }

        if (reloadRequired) {
            location.reload();
        }
    }

    // Bulk action
    async function handleBulkAction(action, platform) {
        const checkboxes = document.querySelectorAll('input[name="property_ids[]"]:checked');
        const propertyIds = Array.from(checkboxes).map(checkbox => checkbox.value);

        if (propertyIds.length === 0) {
            alert('Please select at least one property.');
            return;
        }

        if (confirm(`Are you sure you want to ${action} the selected properties?`)) {
            try {
                const baseUrl = API_BASE_URL;
                const apiUrl = `${baseUrl}/crm.item.${action === 'delete' ? 'delete' : 'update'}?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}`;

                const platformFieldMapping = {
                    pf: 'ufCrm11PfEnable',
                    bayut: 'ufCrm11BayutEnable',
                    dubizzle: 'ufCrm11DubizzleEnable',
                    website: 'ufCrm11WebsiteEnable'
                };

                // If action is delete, first get all property details to find image URLs
                if (action === 'delete') {
                    for (const propertyId of propertyIds) {
                        try {
                            // Get property details to find image URLs
                            const getPropertyUrl = `${baseUrl}/crm.item.get?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${propertyId}`;
                            const propertyResponse = await fetch(getPropertyUrl);
                            const propertyData = await propertyResponse.json();
                            console.log('Property data:', propertyData);
                            if (propertyData.result && propertyData.result.item) {
                                const property = propertyData.result.item;

                                // Delete images from S3
                                if (property.ufCrm11PhotoLinks && Array.isArray(property.ufCrm11PhotoLinks)) {
                                    for (const imageUrl of property.ufCrm11PhotoLinks) {
                                        try {
                                            await fetch('./delete-s3-object.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify({
                                                    fileUrl: imageUrl
                                                })
                                            });
                                        } catch (error) {
                                            console.error(`Error deleting S3 object: ${imageUrl}`, error);
                                        }
                                    }
                                }

                                // Delete floorplan from S3 if exists
                                if (property.ufCrm11FloorPlan) {
                                    try {
                                        await fetch('./delete-s3-object.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                fileUrl: property.ufCrm11FloorPlan
                                            })
                                        });
                                    } catch (error) {
                                        console.error(`Error deleting S3 floorplan: ${property.ufCrm11FloorPlan}`, error);
                                    }
                                }

                                // Delete documents from S3
                                if (property.ufCrm11Documents && Array.isArray(property.ufCrm11Documents)) {
                                    for (const docUrl of property.ufCrm11Documents) {
                                        try {
                                            await fetch('./delete-s3-object.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify({
                                                    fileUrl: docUrl
                                                })
                                            });
                                        } catch (error) {
                                            console.error(`Error deleting S3 document: ${docUrl}`, error);
                                        }
                                    }
                                }
                            }
                        } catch (error) {
                            console.error(`Error getting property details for deletion: ${propertyId}`, error);
                        }
                    }
                }

                const requests = propertyIds.map(propertyId => {
                    let url = `${apiUrl}&id=${propertyId}`;

                    if (action === 'publish') {
                        url += '&fields[ufCrm11Status]=PUBLISHED';

                        if (platformFieldMapping[platform]) {
                            url += `&fields[${platformFieldMapping[platform]}]=Y`;
                        } else {
                            url += `&fields[ufCrm11PfEnable]=Y&fields[ufCrm11BayutEnable]=Y&fields[ufCrm11DubizzleEnable]=Y&fields[ufCrm11WebsiteEnable]=Y`;
                        }
                    } else if (action === 'unpublish') {
                        if (platformFieldMapping[platform]) {
                            url += `&fields[${platformFieldMapping[platform]}]=N`;
                        } else {
                            url += `&fields[ufCrm11PfEnable]=N&fields[ufCrm11BayutEnable]=N&fields[ufCrm11DubizzleEnable]=N&fields[ufCrm11WebsiteEnable]=N&fields[ufCrm11Status]=UNPUBLISHED`;
                        }
                    } else if (action === 'archive') {
                        url += '&fields[ufCrm11Status]=ARCHIVED';
                    }

                    return fetch(url, {
                            method: 'GET'
                        })
                        .then(response => response.json())
                        .then(data => {})
                        .catch(error => {
                            console.error(`Error updating property ${propertyId}:`, error);
                        });
                });

                // Wait for all requests to finish
                await Promise.all(requests);

                location.reload();
            } catch (error) {
                console.error('Error handling bulk action:', error);
            }
        }
    }

    // Function to add watermark to the image
    function addWatermark(imageElement, watermarkImagePath) {
        return new Promise((resolve, reject) => {
            const watermarkImage = new Image();
            watermarkImage.src = watermarkImagePath;

            watermarkImage.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const width = imageElement.width;
                const height = imageElement.height;

                canvas.width = width;
                canvas.height = height;

                ctx.drawImage(imageElement, 0, 0, width, height);

                const watermarkAspect = watermarkImage.width / watermarkImage.height;
                const imageAspect = width / height;

                let watermarkWidth, watermarkHeight;

                if (watermarkAspect > imageAspect) {
                    watermarkWidth = width * 0.6;
                    watermarkHeight = watermarkWidth / watermarkAspect;
                } else {
                    watermarkHeight = height * 0.6;
                    watermarkWidth = watermarkHeight * watermarkAspect;
                }

                const xPosition = (width - watermarkWidth) / 2;
                const yPosition = (height - watermarkHeight) / 2;

                ctx.drawImage(watermarkImage, xPosition, yPosition, watermarkWidth, watermarkHeight);
                const watermarkedImage = canvas.toDataURL('image/jpeg', 1);
                resolve(watermarkedImage);
            };

            watermarkImage.onerror = function() {
                reject('Failed to load watermark image.');
            };
        });
    }

    // Function to add watermark text to the image
    function addWatermarkText(imageElement, watermarkText) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const width = imageElement.width;
            const height = imageElement.height;

            canvas.width = width;
            canvas.height = height;

            ctx.drawImage(imageElement, 0, 0, width, height);

            // Set the watermark text properties
            ctx.font = '360px Arial'; // You can adjust the font size here
            ctx.fillStyle = 'rgba(255, 255, 255, 0.6)'; // White color with 50% transparency
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // Add the watermark text to the image (centered)
            ctx.fillText(watermarkText, width / 2, height / 2);

            // Convert the image to JPEG with reduced quality (optional)
            const watermarkedImage = canvas.toDataURL('image/jpeg', 0.7); // Adjust quality as needed
            resolve(watermarkedImage);
        });
    }

    // Function to upload a file
    function uploadFile(file, isDocument = false) {
        const formData = new FormData();
        formData.append('file', file);

        if (isDocument) {
            formData.append('isDocument', 'true');
        }

        return fetch('upload-file.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.url) {
                    return data.url;
                } else {
                    console.error('Error uploading file (PHP backend):', data.error);
                    return null;
                }
            })
            .catch((error) => {
                console.error("Error uploading file:", error);
                return null;
            });
    }

    // Process base64 images
    async function processBase64Images(base64Images, watermarkPath, watermark = true) {
        const photoPaths = [];
        const TARGET_ASPECT_RATIO = 4 / 3;

        function resizeToAspectRatio(image) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            let newWidth = image.width;
            let newHeight = image.height;
            const currentAspectRatio = image.width / image.height;

            if (currentAspectRatio > TARGET_ASPECT_RATIO) {
                newWidth = image.height * TARGET_ASPECT_RATIO;
                newHeight = image.height;
            } else if (currentAspectRatio < TARGET_ASPECT_RATIO) {
                newWidth = image.width;
                newHeight = image.width / TARGET_ASPECT_RATIO;
            }

            canvas.width = newWidth;
            canvas.height = newHeight;

            const xOffset = (newWidth - image.width) / 2;
            const yOffset = (newHeight - image.height) / 2;

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, newWidth, newHeight);

            ctx.drawImage(
                image,
                xOffset,
                yOffset,
                image.width,
                image.height
            );

            return canvas.toDataURL();
        }

        for (const base64Image of base64Images) {
            const regex = /^data:image\/(\w+);base64,/;
            const matches = base64Image.match(regex);

            if (matches) {
                const base64Data = base64Image.replace(regex, '');
                const imageData = atob(base64Data);

                const blob = new Blob([new Uint8Array(imageData.split('').map(c => c.charCodeAt(0)))], {
                    type: `image/${matches[1]}`,
                });
                const imageUrl = URL.createObjectURL(blob);

                const imageElement = new Image();
                imageElement.src = imageUrl;

                await new Promise((resolve, reject) => {
                    imageElement.onload = async () => {
                        try {
                            const resizedDataUrl = resizeToAspectRatio(imageElement);
                            const resizedImage = new Image();
                            resizedImage.src = resizedDataUrl;

                            await new Promise(resolve => {
                                resizedImage.onload = resolve;
                            });

                            const finalDataUrl = watermark ?
                                await addWatermark(resizedImage, watermarkPath) :
                                resizedDataUrl;

                            const finalBlob = dataURLToBlob(finalDataUrl);
                            const uploadedUrl = await uploadFile(finalBlob);

                            if (uploadedUrl) {
                                photoPaths.push(uploadedUrl);
                            } else {
                                console.error('Error uploading photo from base64 data');
                            }

                            resolve();
                        } catch (error) {
                            console.error('Error processing watermarking or uploading:', error);
                            reject(error);
                        } finally {
                            URL.revokeObjectURL(imageUrl);
                        }
                    };

                    imageElement.onerror = (error) => {
                        console.error('Failed to load image from URL:', error);
                        reject(error);
                    };
                });
            } else {
                console.error('Invalid base64 image data');
            }
        }

        return photoPaths;
    }

    function getAmenityName(amenityId) {
        const amenities = [{
                id: 'AC',
                label: 'Central air conditioning'
            },
            {
                id: 'AN',
                label: 'Cable-ready'
            },
            {
                id: 'AP',
                label: 'Near airport'
            },
            {
                id: 'BA',
                label: 'Balcony'
            },
            {
                id: 'BB',
                label: 'BBQ area'
            },
            {
                id: 'BC',
                label: 'Beach access'
            },
            {
                id: 'BH',
                label: 'Beach Access'
            },
            {
                id: 'BK',
                label: 'Kitchen Appliances'
            },
            {
                id: 'BL',
                label: 'View of Landmark'
            },
            {
                id: 'BT',
                label: 'Basement'
            },
            {
                id: 'BP',
                label: 'Basement parking'
            },
            {
                id: 'BW',
                label: 'Built in wardrobes'
            },
            {
                id: 'CA',
                label: 'Carpets'
            },
            {
                id: 'CL',
                label: 'Cleaning services'
            },
            {
                id: 'CR',
                label: 'Conference room'
            },
            {
                id: 'CS',
                label: 'Concierge Service'
            },
            {
                id: 'CV',
                label: 'Community view'
            },
            {
                id: 'DN',
                label: 'Pantry'
            },
            {
                id: 'DR',
                label: 'Drivers room'
            },
            {
                id: 'EO',
                label: 'East orientation'
            },
            {
                id: 'FF',
                label: 'Fully fitted kitchen'
            },
            {
                id: 'GA',
                label: 'Private garage'
            },
            {
                id: 'GF',
                label: 'Ground floor'
            },
            {
                id: 'GR',
                label: 'Garden view'
            },
            {
                id: 'GZ',
                label: 'Gazebo'
            },
            {
                id: 'HO',
                label: 'Near hospital'
            },
            {
                id: 'HT',
                label: 'Heating'
            },
            {
                id: 'IC',
                label: 'Within a Compound'
            },
            {
                id: 'IS',
                label: 'Indoor swimming pool'
            },
            {
                id: 'LF',
                label: 'On low floor'
            },
            {
                id: 'MB',
                label: 'Marble floors'
            },
            {
                id: 'MF',
                label: 'On mid floor'
            },
            {
                id: 'MR',
                label: 'Maids Room'
            },
            {
                id: 'MO',
                label: 'Near metro'
            },
            {
                id: 'MT',
                label: 'Maintenance'
            },
            {
                id: 'MS',
                label: 'Maid Service'
            },
            {
                id: 'NM',
                label: 'Near mosque'
            },
            {
                id: 'NO',
                label: 'North orientation'
            },
            {
                id: 'NS',
                label: 'Near school'
            },
            {
                id: 'PA',
                label: 'Pets allowed'
            },
            {
                id: 'PG',
                label: 'Garden'
            },
            {
                id: 'PK',
                label: 'Public parks'
            },
            {
                id: 'PL',
                label: 'Private Land'
            },
            {
                id: 'PP',
                label: 'Swimming pool'
            },
            {
                id: 'PR',
                label: 'Children Play Area'
            },
            {
                id: 'PY',
                label: 'Private Gym'
            },
            {
                id: 'RA',
                label: 'Reception area'
            },
            {
                id: 'RT',
                label: 'Near restaurants'
            },
            {
                id: 'SA',
                label: 'Sauna'
            },
            {
                id: 'SG',
                label: 'Storage room'
            },
            {
                id: 'SH',
                label: 'Core and Shell'
            },
            {
                id: 'SR',
                label: 'Steam room'
            },
            {
                id: 'SS',
                label: 'Spa'
            },
            {
                id: 'ST',
                label: 'Study'
            },
            {
                id: 'SY',
                label: 'Shared Gym'
            },
            {
                id: 'SP',
                label: 'Shared swimming pool'
            },
            {
                id: 'SV',
                label: 'Server room'
            },
            {
                id: 'TR',
                label: 'Terrace'
            },
            {
                id: 'UI',
                label: 'Upgraded interior'
            },
            {
                id: 'VF',
                label: 'Visitor Parking'
            },
            {
                id: 'VW',
                label: 'Sea/Water view'
            },
            {
                id: 'WC',
                label: 'Walk-in Closet'
            },
            {
                id: 'WO',
                label: 'West orientation'
            },
            {
                id: 'VP',
                label: 'Visitors parking'
            },
            {
                id: 'VT',
                label: 'Near veterinary'
            },
            {
                id: 'VW',
                label: 'View of Water'
            },
            {
                id: "SE",
                label: "Security"
            },
            {
                id: "CO",
                label: "Children's Pool"
            },
        ];

        return amenities.find(amenity => amenity.id === amenityId)?.label || amenityId;
    }

    function getAmenityId(amenityName) {
        const amenities = [{
                id: 'AC',
                label: 'Central air conditioning'
            },
            {
                id: 'AN',
                label: 'Cable-ready'
            },
            {
                id: 'AP',
                label: 'Near airport'
            },
            {
                id: 'BA',
                label: 'Balcony'
            },
            {
                id: 'BB',
                label: 'BBQ area'
            },
            {
                id: 'BC',
                label: 'Beach access'
            },
            {
                id: 'BH',
                label: 'Beach Access'
            },
            {
                id: 'BK',
                label: 'Kitchen Appliances'
            },
            {
                id: 'BL',
                label: 'View of Landmark'
            },
            {
                id: 'BT',
                label: 'Basement'
            },
            {
                id: 'BP',
                label: 'Basement parking'
            },
            {
                id: 'BW',
                label: 'Built in wardrobes'
            },
            {
                id: 'CA',
                label: 'Carpets'
            },
            {
                id: 'CL',
                label: 'Cleaning services'
            },
            {
                id: 'CR',
                label: 'Conference room'
            },
            {
                id: 'CS',
                label: 'Concierge Service'
            },
            {
                id: 'CV',
                label: 'Community view'
            },
            {
                id: 'DN',
                label: 'Pantry'
            },
            {
                id: 'DR',
                label: 'Drivers room'
            },
            {
                id: 'EO',
                label: 'East orientation'
            },
            {
                id: 'FF',
                label: 'Fully fitted kitchen'
            },
            {
                id: 'GA',
                label: 'Private garage'
            },
            {
                id: 'GF',
                label: 'Ground floor'
            },
            {
                id: 'GR',
                label: 'Garden view'
            },
            {
                id: 'GZ',
                label: 'Gazebo'
            },
            {
                id: 'HO',
                label: 'Near hospital'
            },
            {
                id: 'HT',
                label: 'Heating'
            },
            {
                id: 'IC',
                label: 'Within a Compound'
            },
            {
                id: 'IS',
                label: 'Indoor swimming pool'
            },
            {
                id: 'LF',
                label: 'On low floor'
            },
            {
                id: 'MB',
                label: 'Marble floors'
            },
            {
                id: 'MF',
                label: 'On mid floor'
            },
            {
                id: 'MR',
                label: 'Maids Room'
            },
            {
                id: 'MO',
                label: 'Near metro'
            },
            {
                id: 'MT',
                label: 'Maintenance'
            },
            {
                id: 'MS',
                label: 'Maid Service'
            },
            {
                id: 'NM',
                label: 'Near mosque'
            },
            {
                id: 'NO',
                label: 'North orientation'
            },
            {
                id: 'NS',
                label: 'Near school'
            },
            {
                id: 'PA',
                label: 'Pets allowed'
            },
            {
                id: 'PG',
                label: 'Garden'
            },
            {
                id: 'PK',
                label: 'Public parks'
            },
            {
                id: 'PL',
                label: 'Private Land'
            },
            {
                id: 'PP',
                label: 'Swimming pool'
            },
            {
                id: 'PR',
                label: 'Children Play Area'
            },
            {
                id: 'PY',
                label: 'Private Gym'
            },
            {
                id: 'RA',
                label: 'Reception area'
            },
            {
                id: 'RT',
                label: 'Near restaurants'
            },
            {
                id: 'SA',
                label: 'Sauna'
            },
            {
                id: 'SG',
                label: 'Storage room'
            },
            {
                id: 'SH',
                label: 'Core and Shell'
            },
            {
                id: 'SR',
                label: 'Steam room'
            },
            {
                id: 'SS',
                label: 'Spa'
            },
            {
                id: 'ST',
                label: 'Study'
            },
            {
                id: 'SY',
                label: 'Shared Gym'
            },
            {
                id: 'SP',
                label: 'Shared swimming pool'
            },
            {
                id: 'SV',
                label: 'Server room'
            },
            {
                id: 'TR',
                label: 'Terrace'
            },
            {
                id: 'UI',
                label: 'Upgraded interior'
            },
            {
                id: 'VF',
                label: 'Visitor Parking'
            },
            {
                id: 'VW',
                label: 'Sea/Water view'
            },
            {
                id: 'WC',
                label: 'Walk-in Closet'
            },
            {
                id: 'WO',
                label: 'West orientation'
            },
            {
                id: 'VP',
                label: 'Visitors parking'
            },
            {
                id: 'VT',
                label: 'Near veterinary'
            },
            {
                id: 'VW',
                label: 'View of Water'
            },
            {
                id: "SE",
                label: "Security"
            },
            {
                id: "CO",
                label: "Children's Pool"
            },
        ];

        return amenities.find(amenity => amenity.label === amenityName)?.id || amenityName;
    }

    // Function to convert data URL to Blob
    function dataURLToBlob(dataURL) {
        const byteString = atob(dataURL.split(',')[1]);
        const arrayBuffer = new ArrayBuffer(byteString.length);
        const uintArray = new Uint8Array(arrayBuffer);
        for (let i = 0; i < byteString.length; i++) {
            uintArray[i] = byteString.charCodeAt(i);
        }
        return new Blob([uintArray], {
            type: 'image/png'
        });
    }

    // Function to fetch a property
    async function fetchProperty(id) {
        const url = `${API_BASE_URL}crm.item.get?entityTypeId=${LISTINGS_ENTITY_TYPE_ID}&id=${id}`;
        console.log("url: ", url)
        const response = await fetch(url);
        const data = await response.json();
        console.log("data: ", data)
        console.log("data.result: ", data.result)
        console.log("data.result.item: ", data.result.item);

        if (data.result && data.result.item) {
            const property = data.result.item;

            // Management
            document.getElementById('reference').value = property.ufCrm11ReferenceNumber;
            if (property.ufCrm11Status === 'PUBLISHED') {
                document.getElementById('reference').disabled = true;
            }

            // Landlord 1
            document.getElementById('landlord_name').value = property.ufCrm11LandlordName;
            document.getElementById('landlord_email').value = property.ufCrm11LandlordEmail;
            document.getElementById('landlord_phone').value = property.ufCrm11LandlordContact;

            Array.from(document.getElementById('availability').options).forEach(option => {
                if (option.value == property.ufCrm11Availability) option.selected = true;
            });
            document.getElementById('available_from').value = formatInputDate(property.ufCrm11AvailableFrom);
            document.getElementById('contract_expiry').value = formatInputDate(property.ufCrm11ContractExpiryDate);

            // Specifications
            document.getElementById('title_deed').value = property.ufCrm11TitleDeed;
            document.getElementById('size').value = property.ufCrm11Size;
            document.getElementById('unit_no').value = property.ufCrm11UnitNo;
            document.getElementById('bathrooms').value = property.ufCrm11Bathroom;
            document.getElementById('parkings').value = property.ufCrm11Parking;
            document.getElementById('total_plot_size').value = property.ufCrm11TotalPlotSize;
            document.getElementById('lot_size').value = property.ufCrm11LotSize;
            document.getElementById('buildup_area').value = property.ufCrm11BuildupArea;
            document.getElementById('layout_type').value = property.ufCrm11LayoutType;
            document.getElementById('project_name').value = property.ufCrm11ProjectName;
            document.getElementById('build_year').value = property.ufCrm11BuildYear;
            Array.from(document.getElementById('property_type').options).forEach(option => {
                if (option.value === property.ufCrm11PropertyType) option.selected = true;
            });
            Array.from(document.getElementById('offering_type').options).forEach(option => {
                if (option.value === property.ufCrm11OfferingType) option.selected = true;
            });
            Array.from(document.getElementById('bedrooms').options).forEach(option => {
                if (option.value == property.ufCrm11Bedroom) option.selected = true;
            });
            Array.from(document.getElementById('furnished').options).forEach(option => {
                if (option.value == property.ufCrm11Furnished) option.selected = true;
            });
            Array.from(document.getElementById('project_status').options).forEach(option => {
                if (option.value == property.ufCrm11ProjectStatus) option.selected = true;
            });
            Array.from(document.getElementById('sale_type').options).forEach(option => {
                if (option.value == property.ufCrm11SaleType) option.selected = true;
            });
            Array.from(document.getElementById('ownership').options).forEach(option => {
                if (option.value == property.ufCrm11Ownership) option.selected = true;
            });

            // Property Permit
            document.getElementById('rera_permit_number').value = property.ufCrm11ReraPermitNumber
            document.getElementById('dtcm_permit_number').value = property.ufCrm11DtcmPermitNumber
            document.getElementById('rera_issue_date').value = formatInputDate(property.ufCrm11ReraPermitIssueDate);
            document.getElementById('rera_expiration_date').value = formatInputDate(property.ufCrm11ReraPermitExpirationDate);

            // Pricing
            document.getElementById('price').value = property.ufCrm11Price;
            document.getElementById('payment_method').value = property.ufCrm11PaymentMethod;
            document.getElementById('downpayment_price').value = property.ufCrm11DownPaymentPrice;
            document.getElementById('service_charge').value = property.ufCrm11ServiceCharge;
            property.ufCrm11HidePrice == "Y" ? document.getElementById('hide_price').checked = true : document.getElementById('hide_price').checked = false;
            Array.from(document.getElementById('rental_period').options).forEach(option => {
                if (option.value == property.ufCrm11RentalPeriod) option.selected = true;
            });
            Array.from(document.getElementById('cheques').options).forEach(option => {
                if (option.value == property.ufCrm11NoOfCheques) option.selected = true;
            });
            Array.from(document.getElementById('financial_status').options).forEach(option => {
                if (option.value == property.ufCrm11FinancialStatus) option.selected = true;
            });

            // Title and Description
            document.getElementById('title_en').value = property.ufCrm11TitleEn;
            document.getElementById('description_en').textContent = property.ufCrm11DescriptionEn;
            document.getElementById('title_ar').value = property.ufCrm11TitleAr;
            document.getElementById('description_ar').textContent = property.ufCrm11DescriptionAr;
            document.getElementById('brochure_description_1').textContent = property.ufCrm11BrochureDescription;
            document.getElementById('brochure_description_2').textContent = property.ufCrm_12_BROCHURE_DESCRIPTION_2;

            document.getElementById('titleEnCount').textContent = document.getElementById('title_en').value.length;
            document.getElementById('descriptionEnCount').textContent = document.getElementById('description_en').textContent.length;
            document.getElementById('titleArCount').textContent = document.getElementById('title_ar').value.length;
            document.getElementById('descriptionArCount').textContent = document.getElementById('description_ar').textContent.length;
            document.getElementById('brochureDescription1Count').textContent = document.getElementById('brochure_description_1').textContent.length;
            document.getElementById('brochureDescription2Count').textContent = document.getElementById('brochure_description_2').textContent.length;

            // Location
            document.getElementById('pf_location').value = property.ufCrm11Location;
            document.getElementById('pf_city').value = property.ufCrm11City;
            document.getElementById('pf_community').value = property.ufCrm11Community;
            document.getElementById('pf_subcommunity').value = property.ufCrm11SubCommunity;
            document.getElementById('pf_building').value = property.ufCrm11Tower;
            document.getElementById('bayut_location').value = property.ufCrm11BayutLocation;
            document.getElementById('bayut_city').value = property.ufCrm11BayutCity;
            document.getElementById('bayut_community').value = property.ufCrm11BayutCommunity;
            document.getElementById('bayut_subcommunity').value = property.ufCrm11BayutSubCommunity;
            document.getElementById('bayut_building').value = property.ufCrm11BayutTower;

            if (property.ufCrm11Geopoints) {
                const [latitude, longitude] = property.ufCrm11Geopoints.split(',').map(coord => coord.trim());
                document.getElementById('latitude').value = latitude;
                document.getElementById('longitude').value = longitude;
            }

            // Photos and Videos
            document.getElementById('video_tour_url').value = property.ufCrm11VideoTourUrl;
            document.getElementById('360_view_url').value = property.ufCrm_18_360_VIEW_URL;
            document.getElementById('qr_code_url').value = property.ufCrm11QrCodePropertyBooster;
            // Photos
            // Floor Plan

            // Portals
            property.ufCrm11PfEnable == "Y" ? document.getElementById('pf_enable').checked = true : document.getElementById('pf_enable').checked = false;
            property.ufCrm11BayutEnable == "Y" ? document.getElementById('bayut_enable').checked = true : document.getElementById('bayut_enable').checked = false;
            property.ufCrm11DubizzleEnable == "Y" ? document.getElementById('dubizzle_enable').checked = true : document.getElementById('dubizzle_enable').checked = false;
            property.ufCrm11WebsiteEnable == "Y" ? document.getElementById('website_enable').checked = true : document.getElementById('website_enable').checked = false;
            property.ufCrm11Watermark == "Y" ? document.getElementById('watermark').checked = true : document.getElementById('watermark').checked = false;
            if (document.getElementById('dubizzle_enable').checked && document.getElementById('bayut_enable').value) {
                toggle_bayut_dubizzle.checked = true;
            }

            switch (property.ufCrm11Status) {
                case 'PUBLISHED':
                    document.getElementById('publish').checked = true;
                    break;
                case 'UNPUBLISHED':
                    document.getElementById('unpublish').checked = true;
                    break;
                case 'LIVE':
                    document.getElementById('live').checked = true;
                    break;
                case 'DRAFT':
                    document.getElementById('draft').checked = true;
                    break;
                case 'ARCHIVED':
                    document.getElementById('archive').checked = true;
                    break;
                case 'POCKET':
                    document.getElementById('pocket').checked = true;
                    break;
            }

            // Make permit required or not required based on status
            const statusRadios = document.querySelectorAll('input[name="status"]');
            const reraPermit = document.getElementById('rera_permit_number');
            const reraLabel = document.querySelector('label[for="rera_permit_number"]');

            let requiredMarker = null;

            function updateRequiredFields() {
                const selected = document.querySelector('input[name="status"]:checked');
                if (selected && selected.value === 'PUBLISHED') {
                    reraPermit.setAttribute('required', 'required');

                    if (!requiredMarker) {
                        requiredMarker = document.createElement('span');
                        requiredMarker.textContent = ' *';
                        requiredMarker.classList.add('text-red-500');
                        reraLabel.appendChild(requiredMarker);
                    }
                } else {
                    reraPermit.removeAttribute('required');

                    if (requiredMarker) {
                        requiredMarker.remove();
                        requiredMarker = null;
                    }
                }
            }

            // Initial check on page load
            updateRequiredFields();

            // Attach change event to all status radio buttons
            statusRadios.forEach(radio => {
                radio.addEventListener('change', updateRequiredFields);
            });

            function ensureOptionExistsAndSelect(selectElementId, value, label) {
                const selectElement = document.getElementById(selectElementId);
                const existingOption = document.querySelector(`#${selectElementId} option[value="${value}"]`);

                if (!existingOption) {
                    const newOption = document.createElement('option');
                    newOption.value = value;
                    newOption.textContent = label || 'Unknown Option';
                    newOption.selected = true;
                    selectElement.appendChild(newOption);
                } else {
                    existingOption.selected = true;
                }
            }

            ensureOptionExistsAndSelect('listing_agent', property.ufCrm11AgentId, property.ufCrm11AgentName);
            ensureOptionExistsAndSelect('listing_owner', property.ufCrm11ListingOwner, property.ufCrm11ListingOwner);
            ensureOptionExistsAndSelect('developer', property.ufCrm11Developers, property.ufCrm11Developers);

            // Notes
            function addExistingNote(note) {
                const li = document.createElement("li");
                li.classList.add("text-gray-700", "p-2", "flex", "justify-between", "items-center", "mb-2", "bg-gray-100", "rounded-md");

                li.innerHTML = `
                    ${note} 
                    <button class="text-red-500 hover:text-red-700" onclick="removeNote(this)">×</button>
                `;

                document.getElementById("notesList").appendChild(li);
                updateNotesInput();
            }

            if (property.ufCrm11Notes.length > 0) {
                property.ufCrm11Notes.forEach(note => {
                    addExistingNote(note);
                });
            }

            // Amenities
            function addExistingAmenity(amenity) {
                if (!selectedAmenities.some(a => a.id === amenity)) {
                    selectedAmenities.push({
                        id: amenity,
                        label: getAmenityName(amenity)
                    });
                }

                const li = document.createElement("li");
                li.classList.add("text-gray-700", "p-2", "flex", "justify-between", "items-center", "mb-2", "bg-gray-100", "rounded-md");

                li.innerHTML = `
                    ${getAmenityName(amenity)} 
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="removeAmenity('${amenity}')">×</button>
                `;

                document.getElementById("selectedAmenities").appendChild(li);
                updateAmenitiesInput();
            }

            if (property.ufCrm11Amenities && property.ufCrm11Amenities.length > 0) {
                property.ufCrm11Amenities.forEach(amenity => {
                    addExistingAmenity(amenity);
                });
            }


            return property;

        } else {
            console.error('Invalid property data:', data);
            document.getElementById('property-details').textContent = 'Failed to load property details.';
        }
    }

    // Function to check if any property is selected
    function isPropertySelected() {
        var checkboxes = document.querySelectorAll('input[name="property_ids[]"]:checked');
        var propertyIds = Array.from(checkboxes).map(checkbox => checkbox.value);

        return propertyIds && propertyIds.length > 0;
    }

    // Function to select and add properties to agent transfer form
    function selectAndAddPropertiesToAgentTransfer() {
        var checkboxes = document.querySelectorAll('input[name="property_ids[]"]:checked');
        if (checkboxes.length == 0) {
            return alert('Please select at least one property or try refreshing the page.');
        }
        var propertyIds = Array.from(checkboxes).map(checkbox => checkbox.value);

        if (!isPropertySelected()) {
            return alert('Please select at least one property.');
        }

        if (document.getElementById('transferAgentPropertyIds')) {
            document.getElementById('transferAgentPropertyIds').value = propertyIds.join(',');
        } else {
            localStorage.removeItem('transferAgentPropertyIds');
            localStorage.setItem('transferAgentPropertyIds', propertyIds.join(','));
        }

        const agentModal = new bootstrap.Modal(document.getElementById('transferAgentModal'));
        agentModal.show();
    }

    // Function to select and add properties to owner transfer form
    function selectAndAddPropertiesToOwnerTransfer() {
        var checkboxes = document.querySelectorAll('input[name="property_ids[]"]:checked');
        var propertyIds = Array.from(checkboxes).map(checkbox => checkbox.value);

        if (!isPropertySelected()) {
            return alert('Please select at least one property.');
        }

        if (document.getElementById('transferOwnerPropertyIds')) {
            document.getElementById('transferOwnerPropertyIds').value = propertyIds.join(',');
        } else {
            localStorage.removeItem('transferOwnerPropertyIds');
            localStorage.setItem('transferOwnerPropertyIds', propertyIds.join(','));
        }


        const ownerModal = new bootstrap.Modal(document.getElementById('transferOwnerModal'));
        ownerModal.show();
    }

    // Function to calculate square meters
    function sqftToSqm(sqft) {
        const sqm = sqft * 0.092903;
        return parseFloat(sqm.toFixed(2));
    }
</script>

</body>

</html>