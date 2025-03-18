<?php

$query = "SELECT tilino
          FROM tiliotesaanto
          WHERE yhtio     = '$yritirow[yhtio]'
          and erittely    = 'o'
          and pankkitili  = '$pankkitilino'
          and koodi       = '$koodi'
          and koodiselite = '$koodiselite'";
$tiliotesaantoresult = pupe_query($query);

if (mysqli_num_rows($tiliotesaantoresult) > 0) {
  $kuittikoodi =' ';
  echo "<font class='message'>".t("Erittely pyydettiin käsittelemään")."</font><br>\n";
}
