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

        <?php if($_SESSION['role']=='admin'): ?>
            <!-- admin content -->
            <div class="h-full flex flex-col overflow-auto shadow-md gap-2">

                <!-- stats grid -->
                <div class="w-full grid grid-cols-2 md:grid-cols-4 gap-3">

                    <!-- student count -->
                    <div class="flex flex-col items-center justify-center border-2 border-green-600 bg-green-50 text-green-700 px-6 py-4 rounded-xl shadow-md">
                        <?php
                            $count = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE role='student'");
                            $count->execute();
                            $result = $count->get_result();
                            $row= $result->fetch_assoc();
                        ?>
                        <h2 class="text-xl font-bold"><?= $row['count'] ?></h2>
                        <p class="text-sm">Students</p>
                    </div>

                    <!-- total logs today -->
                    <div class="flex flex-col items-center justify-center border-2 border-blue-600 bg-blue-50 text-blue-700 px-6 py-4 rounded-xl shadow-md">
                        <?php
                            $logs = $conn->prepare("SELECT COUNT(*) AS logs FROM logs WHERE time >= CURDATE()");
                            $logs->execute();
                            $logs->bind_result($count);
                            $logs->fetch();
                            $logs->close();
                        ?>
                        <h2 class="text-xl font-bold"><?= $count ?></h2>
                        <p class="text-sm">Total Logs Today</p>
                    </div>

                    <!-- log in today -->
                    <div class="flex flex-col items-center justify-center border-2 border-blue-600 bg-blue-50 text-blue-700 px-6 py-4 rounded-xl shadow-md">
                        <?php
                            $logs = $conn->prepare("SELECT COUNT(*) AS logs FROM logs WHERE time >= CURDATE() AND LOWER(status)='in'");
                            $logs->execute();
                            $logs->bind_result($count);
                            $logs->fetch();
                            $logs->close();
                        ?>
                        <h2 class="text-xl font-bold"><?= $count ?></h2>
                        <p class="text-sm">Log In Today</p>
                    </div>

                    <!-- log out today -->
                    <div class="flex flex-col items-center justify-center border-2 border-blue-600 bg-blue-50 text-blue-700 px-6 py-4 rounded-xl shadow-md">
                        <?php
                            $logs = $conn->prepare("SELECT COUNT(*) AS logs FROM logs WHERE time >= CURDATE() AND LOWER(status)='out'");
                            $logs->execute();
                            $logs->bind_result($count);
                            $logs->fetch();
                            $logs->close();
                        ?>
                        <h2 class="text-xl font-bold"><?= $count ?></h2>
                        <p class="text-sm">Log Out Today</p>
                    </div>
                </div>

                <!-- attendance input -->
                <div class="flex flex-row items-center justify-center items-center bg-black p-2 rounded-3 gap-2">
                    <label class="text-white text-lg fw-normal">Student ID: </label>
                    <form action="config/insert-log.php" method="post" class="flex flex-1 flex-row justify-center gap-2">
                        <input type="text" name="tag" id="student_id" required autofocus
                            class="flex flex-1 rounded-2 p-1 text-lg fw-semibold outline-none">

                        <button type="submit" name="action" value="in"
                                class="px-3 py-2 rounded-lg text-white font-semibold bg-blue-600 hover:scale-105 hover:bg-blue-800 transition duration-200">
                            In
                        </button>

                        <button type="submit" name="action" value="out"
                                class="px-3 py-2 rounded-lg text-white font-semibold bg-blue-600 hover:scale-105 hover:bg-blue-800 transition duration-200">
                            Out
                        </button>
                    </form>
                </div>

                <!-- recent logs table -->
                <div class="h-full flex flex-1 border-2 border-gray-600 rounded-lg shadow-md overflow-hidden">
                    <div class="w-full overflow-y-auto">
                        <h3 class="w-full text-center font-semibold p-1 bg-gray-200">Recent Logs</h3>
                        <table class="table-auto w-full border-collapse">
                            <thead class="bg-gray-800 text-white sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2 text-center">ID</th>
                                    <th class="px-4 py-2 text-center">Student</th>
                                    <th class="px-4 py-2 text-center">Status</th>
                                    <th class="px-4 py-2 text-center">Time</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-50 text-gray-700">
                                <?php
                                    $record = $conn->prepare("
                                        SELECT 
                                            users.tag AS tag,
                                            users.name AS name,
                                            CONCAT(UPPER(LEFT(logs.status,1)), LOWER(SUBSTRING(logs.status,2))) AS status,
                                            logs.time AS time
                                        FROM logs
                                        JOIN users ON logs.user_id = users.id
                                        ORDER BY logs.time DESC
                                        LIMIT 50
                                    ");
                                    $record->execute();
                                    $result = $record->get_result();
                                ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-100">
                                        <td class="px-4 py-2 text-center border"><?= $row['tag'] ?></td>
                                        <td class="px-4 py-2 text-center border"><?= $row['name'] ?></td>
                                        <td class="px-4 py-2 text-center border"><?= $row['status'] ?></td>
                                        <td class="px-4 py-2 text-center border"><?= (new DateTime($row['time']))->format('M d h:i A') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- student content -->
            <div class="h-full flex flex-col overflow-auto shadow-md gap-2">

<div class="w-full gap-3
grid grid-cols-4">

    <div class="flex flex-1 flex-col items-center justify-center 
        border-2 border-red-600 bg-red-50 text-red-700 
        px-6 py-4 rounded-xl shadow-md">

        <?php
        $count = $conn->prepare("
            SELECT COUNT(*) AS count FROM logs
            WHERE user_id=?
        ");
        $count->bind_param('i', $_SESSION['id']);
        $count->execute();
        $result = $count->get_result();
        $row = $result->fetch_assoc();
        ?>
        
        <h2 class="text-xl font-bold"><?= $row['count'] ?></h2>
        <p class="text-sm ">Total logs record</p>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center 
        border-2 border-blue-600 bg-blue-50 text-blue-700 
        px-6 py-4 rounded-xl shadow-md">

        <?php
        $count = $conn->prepare("
            SELECT COUNT(*) AS count FROM logs
            WHERE user_id=?
              AND DATE(time) = CURDATE()
        ");
        $count->bind_param('i', $_SESSION['id']);
        $count->execute();
        $result = $count->get_result();
        $row = $result->fetch_assoc();
        ?>

        <h2 class="text-xl font-bold"><?= $row['count'] ?></h2>
        <p class="text-sm ">Today logs</p>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center 
        border-2 border-red-600 bg-red-50 text-red-700 
        px-6 py-4 rounded-xl shadow-md">
        <?php
        $count = $conn->prepare("
            SELECT COUNT(*) AS count FROM logs
            WHERE user_id=?
                AND status='in'
                AND time=CURDATE()
        ");
        $count->bind_param('i', $_SESSION['id']);
        $count->execute();
        $result = $count->get_result();
        $row = $result->fetch_assoc();
        ?>
        
        <h2 class="text-xl font-bold"><?= $row['count'] ?></h2>
        <p class="text-sm ">Log-In's today</p>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center 
        border-2 border-red-600 bg-red-50 text-red-700 
        px-6 py-4 rounded-xl shadow-md">
        <?php
        $count = $conn->prepare("
            SELECT COUNT(*) AS count FROM logs
            WHERE user_id=?
                AND status='out'
                AND time=CURDATE()
        ");
        $count->bind_param('i', $_SESSION['id']);
        $count->execute();
        $result = $count->get_result();
        $row = $result->fetch_assoc();
        ?>
        
        <h2 class="text-xl font-bold"><?= $row['count'] ?></h2>
        <p class="text-sm ">Log-Out's today</p>
    </div>

</div>



<div class="w-full flex flex-1 overflow-hidden">
    <div class="w-full h-full border border-black rounded-lg shadow-md flex flex-col">
        <h3 class="p-1 text-center font-semibold text-red-550">Logs</h3>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-black text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-1 fw-normal text-center">Status</th>
                        <th class="px-3 py-1 fw-normal text-center">Datetime</th>
                    </tr>
                </thead>
                <tbody class="overflow-y-auto bg-gray-50 text-gray-700">

<?php
    $logs = $conn->prepare("
        SELECT 
            CONCAT(UPPER(LEFT(logs.status,1)), LOWER(SUBSTRING(logs.status,2))) AS status,
            DATE_FORMAT(time, '%m %d, %Y %h:%i:%s %p') AS time
        FROM logs
        WHERE user_id=?
        ORDER BY time DESC
    ");
    $logs->bind_param('i', $_SESSION['id']);
    $logs->execute();
    $result = $logs->get_result();
    
    while ($row = $result->fetch_assoc()):
?>
                    <tr class="hover:bg-gray-100">
                        <td class="px-2 py-1 text-center border w-1/2"><?= $row['status'] ?></td>
                        <td class="px-2 py-1 text-center border w-1/2"><?= $row['time'] ?></td>
                    </tr>
<?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

            </div>
        <?php endif; ?>


    </main>
</body>
</html>