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
    <?php include 'include/aside.php'; ?>

    <main class="flex flex-1 flex-col h-full align-center gap-2 p-2">
        <!-- header -->
        <?php include 'include/header.php' ?>
        
        
    <div class="flex flex-1 w-full items-center justify-center
    bg-cover bg-center"
    style="background-image: url('uploads/bg.jpg');">

    <?php   

        $id = $_SESSION['id'];

        $user = $conn->prepare("
            SELECT * FROM users
            WHERE id = ?
        ");
        $user->bind_param('i', $id);
        $user->execute();
        $result = $user->get_result();
        $row = $result->fetch_assoc();
    ?>
        
        <form action="config/update-account.php" method="post" 
            class=" w-[22rem] shadow bg-white p-4 rounded-4 needs-validation
            flex flex-col items-center p-3 gap-3" validate>

            <h3 class="w-[100%] p-4 rounded-lg mt-[-40px] 
            text-white text-center text-2xl font-semibold 
            bg-black">Profile</h3>

            <div class="input-wrapper w-full">
                <input type="text" id="" name="tag" value="<?= $row['tag'] ?>"
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

            <input type="hidden" name="id" value="<?= $_SESSION['id'] ?>">

            <button type="submit" class="w-full p-2 rounded-lg
            text-white text-md font-semibold
            border-2 border-blue-700 bg-blue-600
            hover:scale-105
            transition duration-200">Update</button>
        </form>

    </div>
        
    </main>
</body>
</html>