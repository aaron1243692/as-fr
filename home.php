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

        
        <div class="h-full flex flex-col border-2 border-gray-600 rounded-lg overflow-auto shadow-md p-3 gap-3"></div>


    </main>
</body>
</html>