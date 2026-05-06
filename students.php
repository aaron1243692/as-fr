<?php
    include 'include/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'include/title.php';?>

    <!-- custom css -->
    <link rel="stylesheet" href="asset/css/input.css">

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

        <!-- search -->
        <form action="students.php" method="post" class="flex flex-row items-center justify-center bg-black p-2 rounded-3 gap-2">
            <input type="text" name="search" id="" value="<?= isset($_POST['search']) ? $_POST['search'] : '' ?>"
            placeholder="Search student..."
            class="flex flex-1 rounded-2 px-2 p-1 text-lg fw-normal
            outline-none">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div class="h-full border-2 border-gray-600 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-y-auto h-100"> <!-- control table body height -->
                <table class="table-auto min-w-full border-collapse">
                    <thead class="bg-gray-800 text-white sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-left w-1/6">ID</th>
                            <th class="px-4 py-2 text-center">Student</th>
                            <th class="px-4 py-2 text-center">Email</th>
                            <th class="px-4 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-50 text-gray-700">
<?php
    $search = isset($_POST['search']) ? $_POST['search'] : "";
    $find = "%$search%";

    $students = $conn->prepare("
        SELECT * FROM users
        WHERE role = 'student'
            AND name LIKE ?
        ORDER BY name ASC
    ");
    $students->bind_param('s', $find);
    $students->execute();
    $result = $students->get_result();
?>

<?php if($result->num_rows): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-gray-100">
            <td class="px-4 py-2 text-center border"><?= $row['tag'] ?></td>
            <td class="px-4 py-2 text-center border"><?= $row['name'] ?></td>
            <td class="px-4 py-2 text-center border"><?= $row['email'] ?></td>
            <td class="px-4 py-1 text-center border">
                <button class="px-3 py-[0.40rem] rounded
                border border-blue-700 bg-blue-600
                text-white text-center text-md font-semibold 
                hover:bg-blue-800 hover:scale-110
                transition duration-200"
                data-bs-toggle="modal"
                data-bs-target="#edit<?= $row['id'] ?>">Edit</button>
<div class="modal fade" id="edit<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="width: 24rem;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form action="config/update-student.php" method="post"
            class="w-full flex flex-col items-center p-4 gap-3">

                <h3 class="w-full text-center mt-[-60px]
                font-semibold p-4 rounded-lg text-xl text-white
                bg-black">Student</h3>

                <div class="input-wrapper w-full">
                    <input type="text" id="tag" name="tag" value="<?= $row['tag'] ?>"
                    class="input-field" required placeholder=" ">
                    <label for="">Student ID:</label>
                </div>  

                <div class="input-wrapper w-full">
                    <input type="text" id="" name="name" value="<?= $row['name'] ?>"
                    class="input-field" required placeholder=" ">
                    <label for="">Full Name:</label>
                </div>  

                <div class="input-wrapper w-full">
                    <input type="email" id="" name="email" value="<?= $row['email'] ?>"
                    class="input-field" required placeholder=" ">
                    <label for="">Email:</label>
                </div>    

                <div class="input-wrapper w-full">
                    <input type="text" id="" name="contact" value="<?= $row['contact'] ?>"
                    class="input-field" required placeholder=" ">
                    <label for="">Contact:</label>
                </div>

                <div class="input-wrapper w-full">
                    <input type="text" id="" name="password" class="input-field" placeholder=" ">
                    <label for="">New Password(optional):</label>
                </div>

                <input type="hidden" name="id" value="<?= $row['id'] ?>">

                <div class="w-full flex items-center justify-center">
                    <button type="submit" class="w-full py-2 rounded-lg
                    border border-blue-700 bg-blue-600
                    text-white font-semibold
                    hover:scale-105 hover:bg-blue-800
                    transition duration-200">Update</button>
                </div>

            </form>
        </div>
    </div>
</div>

                <a href="config/delete-student.php?id=<?= $row['id'] ?>" class="px-3 py-[0.50rem] rounded
                border border-red-700 bg-red-600
                text-white text-center text-md font-semibold 
                hover:bg-red-800 hover:scale-110
                transition duration-200">Delete</a>
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