<div id="buildingPopup" class="position-absolute mt-2 p-2 bg-white shadow z-index-1000 d-none" style="width: 300px;">
    <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
        <p class="form-label pb-2 text-secondary border-bottom">Result</p>
        <div id="resultContainer_building" class="list-group"></div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('building');
        const popup = document.getElementById('buildingPopup');
        const resultContainer = document.getElementById('resultContainer_building');

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();

            if (query.length >= 2) {
                popup.classList.remove('d-none');
                popup.style.top = (searchInput.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - 170) + 'px';
                popup.style.left = (searchInput.getBoundingClientRect().left + (window.pageXOffset || document.documentElement.scrollLeft) - 300) + 'px';
                searchItems(query);
            } else {
                popup.classList.add('d-none');
                resultContainer.innerHTML = '';
            }
        });

        const searchItems = (query) => {
            const webhookUrl = `${API_BASE_URL}crm.item.list`;

            const data = {
                "entityTypeId": PF_LOCATIONS_ENTITY_ID, // Already correct
                "select": ["id", "ufCrm15Building"], // Updated field name
                "filter": {
                    "%ufCrm15Building": query // Updated field name in filter
                }
            };

            fetch(webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    resultContainer.innerHTML = '';

                    if (data.error) {
                        console.error('Error:', error);
                        resultContainer.innerHTML = '<p>Error fetching data.</p>';
                        return;
                    }

                    const items = data.result.items;
                    if (items.length > 0) {
                        // Create a Set to store unique buildings
                        const uniqueBuildings = new Set();
                        const uniqueItems = [];

                        // Filter out duplicates while preserving order
                        items.forEach(item => {
                            if (!uniqueBuildings.has(item.ufCrm15Building)) {
                                uniqueBuildings.add(item.ufCrm15Building);
                                uniqueItems.push(item);
                            }
                        });

                        // Display unique results
                        uniqueItems.forEach(item => {
                            const itemElement = document.createElement('li');
                            itemElement.classList.add('list-group-item');
                            itemElement.style.cursor = 'pointer';
                            itemElement.innerHTML = item.ufCrm15Building;

                            itemElement.addEventListener('click', function() {
                                searchInput.value = item.ufCrm15Building;
                                popup.classList.add('d-none');
                                resultContainer.innerHTML = '';
                            });
                            resultContainer.appendChild(itemElement);
                        });
                    } else {
                        resultContainer.innerHTML = '<p>No items found.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultContainer.innerHTML = '<p>Error fetching data.</p>';
                });
        };
    });
</script>