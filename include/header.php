

<header class="flex flex-row bg-gray-700 w-full bg-dark p-3 gap-4 rounded-3">
    <?php
        $current_file = basename($_SERVER['PHP_SELF'], ".php");
        $page_name = ucwords(str_replace('_', ' ', $current_file));
    ?>
    <h6 class="text-white text-lg fw-semibold leading-none">Page / <?= htmlspecialchars($page_name); ?></h6>
</header>