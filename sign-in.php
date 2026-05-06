<?php    
    (session_status() == PHP_SESSION_NONE) ? session_start() : '';
    if (!empty($_SESSION['message'])) {
        echo "
            <script>
                alert('" . $_SESSION['message'] . "');
            </script>
        ";
        unset($_SESSION['message']);
    }
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
<body class="h-screen flex flex-row justify-center 
bg-cover bg-center"
style="background-image: url('uploads/bg.jpg');">

    <main class="flex items-center justify-center" style="width: 100%; height: 100%;">
        
        <form action="config/sign-in.php" method="post" 
            class=" w-[22rem] shadow bg-white p-4 rounded-4 needs-validation
            flex flex-col items-center p-3 gap-3" validate>

            <h3 class="w-[100%] p-4 rounded-lg mt-[-40px] 
            text-white text-center text-2xl font-semibold 
            bg-black">Sign-in</h3>

            <div class="input-wrapper w-full">
                <input type="text" id="" name="email" class="input-field" required placeholder=" ">
                <label for="">Email:</label>
            </div>

            <div class="input-wrapper w-full">
                <input type="password" id="" name="password" class="input-field" required placeholder=" ">
                <label for="">Password:</label>
            </div>

            <p class="w-full text-center flex-row">
                Don't have an account?
                <a href="sign-up.php" class="text-blue-600">Sign Up.</a>
            </p>

            <button type="submit" class="w-full p-2 rounded-lg
            text-white text-md font-semibold
            border-2 border-blue-700 bg-blue-600
            hover:scale-105
            transition duration-200">Sign-in</button>
        </form>
        
    </main>
</body>
</html>