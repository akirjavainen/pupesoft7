<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<table>";
echo "<tr><th>".t("Taulu")."</th><th>".t("Tauluntunnus")."</th><th>".t("Tapa")."</th><th>".t("Aika")."</th><th>".t("Laatija")."</th><th>".t("Viesti")."</th></tr>";

$query = "  SELECT * from synclog where yhtio='$kukarow[yhtio]' order by tunnus desc";
$result = pupe_query($query);
while ($row=mysqli_fetch_array($result)) {
  echo "<tr>
      <td>$row[taulu]</td>
      <td>$row[tauluntunnus]</td>
      <td>$row[tapa]</td>
      <td>$row[luontiaika]</td>
      <td>$row[laatija]</td>
      <td>$row[viesti]</td>
    </tr>";
}

echo "</table>";
