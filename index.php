<?php
require __DIR__ . '/crest/settings.php';
require __DIR__ . '/controllers/SpaController.php';
require __DIR__ . '/utils/index.php';
require_once(__DIR__ . '/crest/crestcurrent.php');
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filtersStr = sessionStorage.getItem('filters');
        if (filtersStr) {
            try {
                const filters = JSON.parse(filtersStr);

                const keys = Object.keys(filters);
                const onlyAgentId = keys.length === 1 && keys[0] === 'ufCrm11AgentId';

                if (!onlyAgentId) {
                    document.querySelector('#clearFiltersBtn').classList.remove('d-none');
                }

            } catch (e) {
                // If JSON parsing fails, show the clear button just in case
                document.querySelector('#clearFiltersBtn').classList.remove('d-none');
            }
        }
    });

    let DEVELOPERS_ENTITY_ID = <?php echo json_encode(DEVELOPERS_ENTITY_TYPE_ID); ?>;
    let AGENTS_ENTITY_ID = <?php echo json_encode(AGENTS_ENTITY_TYPE_ID); ?>;
    let PF_LOCATIONS_ENTITY_ID = <?php echo json_encode(PF_LOCATIONS_ENTITY_TYPE_ID); ?>;
    let BAYUT_LOCATIONS_ENTITY_ID = <?php echo json_encode(BAYUT_LOCATIONS_ENTITY_TYPE_ID); ?>;
    let LISTINGS_ENTITY_TYPE_ID = <?php echo json_encode(LISTINGS_ENTITY_TYPE_ID); ?>;

    let API_BASE_URL = 'https://zealwayproperties.bitrix24.com/rest/13/wso670w02zhpalic/';
</script>

<?php

include __DIR__ . '/views/header.php';

$result = CRestCurrent::call('user.current');
$currentUser = $result['result'];
$currentUserId = $currentUser['ID'];
$isAdmin = isAdmin($currentUserId);

include 'views/components/toast.php';
include 'views/components/topbar.php';

$pages = [
    'properties' => 'views/properties/index.php',
    'add-property' => 'views/properties/add.php',
    'edit-property' => 'views/properties/edit.php',
    'view-property' => 'views/properties/view.php',

    'pocket' => 'views/pocket/index.php',
    'agents' => 'views/agents/index.php',
    'developers' => 'views/developers/index.php',
    'pf-locations' => 'views/pf-locations/index.php',
    'bayut-locations' => 'views/bayut-locations/index.php',
    'settings' => 'views/settings/index.php',
    'reports' => 'views/reports/index.php',
];

$page = isset($_GET['page']) && array_key_exists($_GET['page'], $pages) ? $_GET['page'] : 'properties';


require $pages[$page];

if (!array_key_exists($page, $pages)) {
    header("Location: index.php?page=properties';");
    exit;
}
?>

<script>
    (function() {
        // Constants
        const isAdminKey = 'isAdmin';
        const userIdKey = 'userId';
        const expiryKey = 'isAdminExpiry';
        const expiryTime = 30 * 60 * 1000; // 30 minutes in milliseconds
        const now = Date.now();

        // Current values from server (these would be populated by PHP in your actual implementation)
        const currentIsAdmin = <?php echo json_encode($isAdmin); ?>; // This will be filled by PHP
        const currentUserId = <?php echo json_encode($currentUserId); ?>; // This will be filled by PHP

        // Get stored values
        const storedExpiry = localStorage.getItem(expiryKey);
        const storedUserId = localStorage.getItem(userIdKey);

        // Check if admin status has expired or if the user ID has changed
        if (!storedExpiry || now > parseInt(storedExpiry, 30) || storedUserId !== currentUserId && currentUserId) {
            // Update all values
            localStorage.setItem(isAdminKey, currentIsAdmin);
            localStorage.setItem(userIdKey, currentUserId);
            localStorage.setItem(expiryKey, now + expiryTime);

            console.log('User session data updated:', {
                userId: currentUserId,
                isAdmin: currentIsAdmin,
                expiresAt: new Date(now + expiryTime).toLocaleTimeString()
            });
        }
    })();
</script>


<?php
include __DIR__ . '/views/footer.php';
