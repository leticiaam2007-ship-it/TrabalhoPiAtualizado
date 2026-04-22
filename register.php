<?php
include("config.php");

$conexao = obterConexao();

if (!$conexao) {
    die("ERRO CONEXAO: " . mysqli_connect_error());
}





// pegar dados do formulário
$nome  = $_POST['nome']  ?? null;
$email = $_POST['email'] ?? null;
$senha = $_POST['senha'] ?? null;

if (!$nome || !$email || !$senha) {
    die("ERRO: dados não chegaram");
}

$sql = "INSERT INTO usuarios (nome, email, senha)
        VALUES ('$nome', '$email', '$senha')";

$stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (nome, email, senha) VALUES (?,?,?)");
mysqli_stmt_bind_param($stmt, "sss", $val1, $val2, $val3);


$val1 = $nome;
$val2 =$email ;
$val3 = $senha;

/* Executa a instrução */



try {
   $result = mysqli_stmt_execute($stmt);

   if ( $result == true){
 header("Location: login.php?cad=ok");
   }else{
 header("Location: login.php?erro=4");
   }
   
    
} catch (\Throwable $th) {
    header("Location: login.php?erro=7");
}
?>