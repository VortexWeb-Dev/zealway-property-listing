<div id="locationPopup" class="absolute mt-2 p-2 bg-white shadow-lg z-50 hidden" style="width: 800px;">
    <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
        <p class="text-sm font-semibold text-gray-500 border-b pb-2">Result</p>
        <ul id="result-container" class="list-group bg-white z-10"></ul>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById('bayut_location');
        const popup = document.getElementById('locationPopup');
        const resultContainer = document.getElementById('result-container');
        const bayutCity = document.getElementById('bayut_city');
        const bayutCommunity = document.getElementById('bayut_community');
        const bayutSubCommunity = document.getElementById('bayut_subcommunity');
        const bayutBuilding = document.getElementById('bayut_building');

        const togglePopup = (show) => {
            popup.classList.toggle('hidden', !show);
        };

        const positionPopup = () => {
            const rect = searchInput.getBoundingClientRect();
            popup.style.top = `${rect.top + window.pageYOffset + 30}px`;
            popup.style.left = `${rect.left + window.pageXOffset}px`;
        };

        const resetFormFields = () => {
            bayutCity.value = '';
            bayutCommunity.value = '';
            bayutSubCommunity.value = '';
            bayutBuilding.value = '';
        };

        const autofillLocation = (location, city, community, subCommunity, building) => {
            bayutCity.value = city === '-' ? '' : city ?? '';
            bayutCommunity.value = community === '-' ? '' : community ?? '';
            bayutSubCommunity.value = subCommunity === '-' ? '' : subCommunity ?? '';
            bayutBuilding.value = building === '-' ? '' : building ?? '';
        };

        const searchItems = async (query) => {
            const webhookUrl = `${API_BASE_URL}crm.item.list`;
            const data = {
                entityTypeId: BAYUT_LOCATIONS_ENTITY_ID,
                select: ["id", "ufCrm13Location", "ufCrm13City", "ufCrm13Community", "ufCrm13SubCommunity", "ufCrm13Building"],
                filter: {
                    "%ufCrm13Location": query
                }
            };

            try {
                const response = await fetch(webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.error) {
                    throw new Error(result.error);
                }

                updateResultContainer(result.result.items);
            } catch (error) {
                console.error('Error:', error);
                resultContainer.innerHTML = '<p>Error fetching data.</p>';
            }
        };

        const updateResultContainer = (items) => {
            resultContainer.innerHTML = '';
            if (items.length > 0) {
                items.forEach(item => {
                    const itemElement = document.createElement('li');
                    itemElement.classList.add('p-2', 'cursor-pointer', 'border-b', 'hover:bg-gray-100', 'text-gray-700');
                    itemElement.innerText = item.ufCrm13Location;

                    itemElement.addEventListener('click', () => {
                        searchInput.value = item.ufCrm13Location;
                        togglePopup(false);
                        autofillLocation(item.ufCrm13Location, item.ufCrm13City, item.ufCrm13Community, item.ufCrm13SubCommunity, item.ufCrm13Building);
                    });

                    resultContainer.appendChild(itemElement);
                });
            } else {
                resultContainer.innerHTML = '<p class="text-center text-gray-500">No items found.</p>';
            }

        };

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                togglePopup(true);
                positionPopup();
                searchItems(query);
            } else {
                togglePopup(false);
                resultContainer.innerHTML = '';
                resetFormFields();
            }
        });
    });
</script>