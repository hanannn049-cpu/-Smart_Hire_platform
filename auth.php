<?php
session_start();
function checkLogin() {

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

}
function checkRole($role) {
    if (!isset($_SESSION['usertype'])) {
        header("Location: login.php");
        exit();
    }
    if ($_SESSION['usertype'] != $role) {
        header("Location: login.php");
        exit();
    }
}
?>