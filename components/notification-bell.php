<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/components/notifications-display.php';
?>

<!-- Notification Bell Component -->
<div class="relative inline-block">
    <!-- Notification Bell Icon -->
    <button id="notification-bell" class="relative p-1 text-gray-700 hover:text-green-600 transition">
        <i class="fas fa-bell text-xl"></i>
        <?php if (isset($notification_count) && $notification_count > 0): ?>
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"><?php echo $notification_count; ?></span>
        <?php endif; ?>
    </button>

    <!-- Notification Dropdown (hidden by default) -->
    <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50">
        <div class="p-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Notifikationer</h3>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <?php if (isset($notifications) && count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="p-3 border-b border-gray-100 hover:bg-gray-50">
                        <div class="flex">
                            <!-- Plant Image -->
                            <div class="mr-3">
                                <div class="h-10 w-10 rounded-full bg-gray-100 overflow-hidden">
                                    <img src="<?php echo '/' . $notification['image_path']; ?>" alt="Plant" class="h-full w-full object-cover" onerror="this.src='../../assets/plants/default.png'">
                                </div>
                            </div>

                            <!-- Notification Content -->
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($notification['plant_name']); ?></p>
                                    <span class="text-xs text-gray-500"><?php echo date('d/m H:i', strtotime($notification['scheduled_for'])); ?></span>
                                </div>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($notification['message']); ?></p>

                                <!-- Mark as Read Button -->
                                <form method="POST" class="mt-2 text-right">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="text-xs text-green-600 hover:text-green-800">
                                        Marker som læst
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-4 text-center text-gray-500">
                    <p>Ingen nye notifikationer</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="p-2 border-t border-gray-200 text-center">
            <a href="/smartplant/dashboard/mine-planter/notifications.php" class="text-sm text-green-600 hover:text-green-800">Se alle notifikationer</a>
        </div>
    </div>
</div>

<!-- Add JavaScript to toggle the notification dropdown -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bell = document.getElementById('notification-bell');
        const dropdown = document.getElementById('notification-dropdown');

        if (bell && dropdown) {
            // Toggle dropdown when bell is clicked
            bell.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
    });
</script>