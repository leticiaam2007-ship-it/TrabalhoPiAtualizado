<?php

    session_start();

    // Remove todas as variáveis de sessão
    session_unset();

    // Destrói a sessão
    session_destroy();

    //unset($logado);

    header("Location: login.php");

?>