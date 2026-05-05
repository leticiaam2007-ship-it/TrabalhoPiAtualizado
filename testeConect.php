<?php
session_start();

    if(isset($_POST['submit']) && !empty($_POST['email']) && !empty($_POST['senha']));
    {  
    include_once('config.php');
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if(mysql_num_rows($result) < 1)
    {
        unset($_SESSION['email'] = $email;)
        unset($_SESSION['senha'] = $senha;)
        unset(header{'Location: login.php'};)
    }
    else
    {
        $_SESSION['email'] = $email;
        $_SESSION['senha'] = $senha;
        header('Location: inder.php')
    }
}
else
{
    header('Location: login.php')
}
?>