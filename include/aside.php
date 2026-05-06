

<aside class="w-1/4 sm:w-1/3 md:w-1/4 lg:w-1/6 xl:w-1/8 h-full bg-white-900">
<div class="w-full h-full flex flex-col align-center border-black border-1 p-3">
    <ul class="flex flex-col w-full gap-3">

        <?php if($_SESSION['role']=='admin'): ?>
            <!-- admin content -->
            <li class="rounded-3 w-full">
                <a href="dashboard.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Dashboard</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="record.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Logs Record</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="students.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Students</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="profile.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Profile</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="password.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Password</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="face-registration.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Face Data</a>
            </li>
        <?php else: ?>
            <!-- student content -->
            <li class="rounded-3 w-full">
                <a href="dashboard.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Dashboard</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="profile.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Profile</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="password.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Password</a>
            </li>
            <li class="rounded-3 w-full">
                <a href="face-registration.php" class="block text-center text-lg fw-semibold  py-1 w-full
                shadow-center shadow-sm rounded-3 border border-gray-100
                hover:bg-black hover:text-white
                transition duration-300"
                >Face Data</a>
            </li>
        <?php endif; ?>
            
    </ul>
    <p class="text-black text-md border-t-2 border-gray-700 mt-[2rem]">Pages:</p>
    <a href="index.php" class="text-blue-800 underline text-md">log-out</a>
</div>
</aside>
