<!-- Add Agent Modal -->
<div id="addModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white w-1/3 rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Agent</h3>

        <form id="addAgentForm" onsubmit="handleAddAgent(event)">
            <div class="mb-4">
                <label for="pfId" class="block text-sm font-semibold text-gray-800">PF ID</label>
                <input type="text" id="pfId" name="pfId" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="PF ID">
            </div>

            <div class="mb-4">
                <label for="agentName" class="block text-sm font-semibold text-gray-800">Name</label>
                <input type="text" id="agentName" name="agentName" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="John Doe">
            </div>

            <div class="mb-4">
                <label for="agentEmail" class="block text-sm font-semibold text-gray-800">Email</label>
                <input type="email" id="agentEmail" name="agentEmail" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="john@example.com">
            </div>

            <div class="mb-4">
                <label for="agentMobile" class="block text-sm font-semibold text-gray-800">Phone</label>
                <input type="tel" id="agentMobile" name="agentMobile" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="+1 (555) 123-4567">
            </div>

            <div class="mb-4">
                <label for="agentLicense" class="block text-sm font-semibold text-gray-800">License</label>
                <input type="text" id="agentLicense" name="agentLicense" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="123456">
            </div>

            <div class="mb-4">
                <label for="agentPhoto" class="block text-sm font-semibold text-gray-800">Photo URL</label>
                <input type="text" id="agentPhoto" name="agentPhoto" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500" placeholder="https://example.com/photo.jpg">
            </div>

            <div class="flex justify-end space-x-2">
                <button
                    type="button"
                    onclick="toggleModal(false)"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Add Agent
                </button>
            </div>
        </form>
    </div>
</div>

<script>
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
                toggleModal(false);
                location.reload();
            } else {
                console.error('Failed to add item');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function handleAddAgent(e) {
        e.preventDefault();

        const form = document.getElementById('addAgentForm');
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        const fields = {
            "ufCrm17AgentId": data.pfId.trim(),
            "ufCrm17AgentName": data.agentName.trim(),
            "ufCrm17AgentEmail": data.agentEmail.trim(),
            "ufCrm17AgentMobile": data.agentMobile.trim(),
            "ufCrm17AgentLicense": data.agentLicense.trim(),
            "ufCrm17AgentPhoto": data.agentPhoto.trim(),
        };

        addItem(AGENTS_ENTITY_ID, fields);
    }
</script>