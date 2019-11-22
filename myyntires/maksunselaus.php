<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<br><font class='head'>".t("Maksujen selaus")."</font><hr>";

$query = "SELECT tapvm, summa, selite, tunnus
          FROM maksu
          WHERE yhtio ='$kukarow[yhtio]'
          and tyyppi   = 'MU'
          and maksettu <> '1'
          ORDER BY tapvm";

$result = pupe_query($query);

echo "<table><tr>";

for ($i = 0; $i < mysqli_num_fields($result)-1; $i++) {
  echo "<th>" . t(mysqli_field_name($result, $i))."</th>";
}
echo "<th></th></tr>";

while ($maksurow=mysqli_fetch_array($result)) {

  for ($i=0; $i<mysqli_num_fields($result)-1; $i++) {
    echo "<td>$maksurow[$i]</td>";
  }

  echo "</tr></form>";
}

echo "</tr></table>";
echo "</body></html>";
