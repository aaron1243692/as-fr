<?php
include 'config/face-auth.php';

face_auth_logout();

header('location: sign-in.php');
exit;
