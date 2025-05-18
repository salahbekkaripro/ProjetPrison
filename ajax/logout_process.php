<?php
session_start();
session_unset();
session_destroy();
header("Location: ../ajax/logout.php"); // Et là on va juste afficher l’overlay
exit;
