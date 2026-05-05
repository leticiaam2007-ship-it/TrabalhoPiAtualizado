<h2><?php echo $marmita['nome']; ?></h2>
<div id="marmitaOpcoes">
   </div>

<script>
    // Os dados que vieram do PHP (Model) são passados para o seu JS
    const marmitaConfig = <?php echo json_encode($marmita['config']); ?>;
    
    // Suas funções de renderTamanhos() e renderOpcoes() continuam aqui
    // Mas agora elas usam o 'marmitaConfig' que veio do banco!
</script>