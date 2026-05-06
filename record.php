<?php
    include 'include/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'include/title.php';?>

    <!-- bootstrap breakpoint -->
    <!-- <link rel="stylesheet" href="asset/bootstrap/breakpoint.css"> -->

    <!-- tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- basic bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <!-- bootstrap popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

</head>
<body class="vh-100 bg-gray-200 flex flex-row justify-content-center">
    <!-- aside -->
    <?php include'include/aside.php'; ?>


    <main class="flex flex-1 flex-col h-full align-center gap-2 p-2">
        <!-- header -->
        <?php include 'include/header.php' ?>

        <div class="w-full p-2 gap-3 flex flex-row justify-end">

            <!-- Export Today -->
            <form action="download-logs.php" method="post">
                <input type="hidden" name="type" value="today">
                <button type="submit" class="px-3 py-1 rounded-lg text-white text-lg font-medium
                        border-blue-600 bg-blue-500 hover:bg-blue-700 hover:scale-105 transition duration-200">
                    Export Today
                </button>
            </form>

            <!-- Export All -->
            <form action="download-logs.php" method="post">
                <input type="hidden" name="type" value="all">
                <button type="submit" class="px-3 py-1 rounded-lg text-white text-lg font-medium
                        border-yellow-600 bg-yellow-500 hover:bg-yellow-700 hover:scale-105 transition duration-200">
                    Export All
                </button>
            </form>

        </div>

        <form action="record.php" method="post" class="flex flex-row items-center justify-center bg-black p-2 rounded-3 gap-2">
            <input 
                type="text" 
                name="search" 
                value="<?= isset($_POST['search']) ? $_POST['search'] : ''; ?>"
                placeholder="Find student..."
                class="flex flex-1 rounded-md p-[0.35rem] text-lg font-normal bg-white text-black border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 hover:scale-105 transition duration-300">
                Search
            </button>
        </form>


        <div class="h-full border-2 border-gray-600 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-y-auto h-100">
                <table class="table-auto min-w-full border-collapse">
                    <thead class="bg-gray-800 text-white sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-left w-1/6">ID</th>
                            <th class="px-4 py-2 text-center">Student</th>
                            <th class="px-4 py-2 text-center">Email</th>
                            <th class="px-4 py-2 text-center">Status</th>
                            <th class="px-4 py-2 text-center">Datetime</th>
                        </tr>
                    </thead>
                        <tbody class="bg-gray-50 text-gray-700">

<?php 
    $search = isset($_POST['search']) ? $_POST['search'] : "";
    $find = "%$search%";

    $students = $conn->prepare("
        SELECT
            users.*,
            CONCAT(UPPER(LEFT(logs.status, 1)), LOWER(SUBSTRING(logs.status, 2))) AS status,
            logs.time AS time
        FROM logs
        JOIN users ON users.id = logs.user_id
        WHERE users.name LIKE ?
        ORDER BY logs.time DESC
    ");

    $students->bind_param("s", $find);  // ← REQUIRED
    $students->execute();

    $result = $students->get_result();
?>
<?php if($result->num_rows): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-gray-100">
            <td class="px-4 py-2 text-center border"><?= $row['tag'] ?></td>
            <td class="px-4 py-2 text-center border"><?= $row['name'] ?></td>
            <td class="px-4 py-2 text-center border"><?= $row['email'] ?></td>
            <td class="px-4 py-2 text-center border"><?= $row['status'] ?></td>
            <td class="px-4 py-2 text-center border">
                <?= date("M d, Y h:i A", strtotime($row['time'])) ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<?php endif; ?>
                        </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>