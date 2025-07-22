<?php
session_start();
session_destroy();
header("Location: index.php?message=Déconnexion réussie !");
exit();
?>
