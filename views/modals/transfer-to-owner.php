<!-- Modal (Transfer to Owner) -->
<div class="modal fade" id="transferOwnerModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">Transfer Property to Owner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="transferOwnerForm" onsubmit="handleTransferOwnerSubmit(event)">
                    <input type="hidden" id="transferOwnerPropertyIds" name="transferOwnerPropertyIds">

                    <div class="form-group">
                        <label for="listing_owner" class="block text-sm font-medium mb-2">Listing Owner <span class="text-danger">*</span></label>
                        <select id="listing_owner" name="listing_owner" class="py-3 px-4 pe-9 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                            <option value="">Please select</option>
                            <?php
                            define('C_REST_WEB_HOOK_URL', 'https://zealwayproperties.bitrix24.com/rest/13/wso670w02zhpalic/');
                            $listing_owners = [];
                            $owner_result = CRest::call('user.get', ['order' => ['NAME' => 'ASC']]);

                            $total_owners = $owner_result['total'];
                            $listing_owners = $owner_result['result'];

                            for ($i = 1; $i < ceil($total_owners / 50); $i++) {
                                $owner_response = CRest::call('user.get', ['order' => ['NAME' => 'ASC'], 'start' => $i * 50])['result'];
                                $listing_owners = array_merge($listing_owners, $owner_response);
                            }

                            if (empty($listing_owners)) {
                                echo '<option disabled>No owners found</option>';
                            } else {
                                foreach ($listing_owners as $owner) {
                                    $id = $owner['ID'];
                                    $name = trim($owner['NAME'] . ' ' . $owner['LAST_NAME']);
                                    $value = "$id-$name";

                                    echo '<option value="' . $value . '">' . $name . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary" id="transferOwnerBtn">
                            Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    async function updateItem(entityTypeId, fields, id) {
        try {
            const response = await fetch(`${API_BASE_URL}crm.item.update?entityTypeId=${entityTypeId}&id=${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fields
                })
            });

            if (!response.ok) throw new Error('Failed to update item');
            console.log('Item updated successfully');
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function handleTransferOwnerSubmit(e) {
        document.getElementById("transferOwnerBtn").disabled = true;
        document.getElementById("transferOwnerBtn").innerHTML = 'Transferring...';

        e.preventDefault();

        const formData = new FormData(e.target);
        const selectedOwner = formData.get('listing_owner');
        const [id, name] = selectedOwner.split('-', 2);

        const fields = {
            "ufCrm11ListingOwnerId": id,
            "ufCrm11ListingOwner": name,
        };

        const propertyIds = formData.get('transferOwnerPropertyIds').split(',') || JSON.parse(localStorage.getItem('transferOwnerPropertyIds')) || [];

        for (const id of propertyIds) {
            await updateItem(LISTINGS_ENTITY_TYPE_ID, fields, Number(id));
        }

        localStorage.removeItem('transferOwnerPropertyIds');

        window.location.replace('?page=properties');
    }
</script>