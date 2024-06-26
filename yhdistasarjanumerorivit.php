<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Kohdista eri riveiltä myydyt sarjanumerot")."</font><hr>";

// Haetaan sarjanumerot jotka on liitetty myyntiriviin mutta ei ostoriviin
$query = "SELECT *
          FROM sarjanumeroseuranta
          WHERE yhtio          = '$kukarow[yhtio]'
          and myyntirivitunnus > 0
          and ostorivitunnus   = 0";
$sarres1 = pupe_query($query);

echo "<table>";

$lask = 0;

while ($sarrow1 = mysqli_fetch_array($sarres1)) {

  $query = "SELECT *
            FROM sarjanumeroseuranta
            WHERE yhtio          = '$kukarow[yhtio]'
            and tuoteno          = '$sarrow1[tuoteno]'
            and sarjanumero      = '$sarrow1[sarjanumero]'
            and myyntirivitunnus = 0
            and ostorivitunnus   > 0
            LIMIT 1";
  $sarres2 = pupe_query($query);

  if (mysqli_num_rows($sarres2) == 1) {

    $sarrow2 = mysqli_fetch_array($sarres2);

    echo "<tr><td>$sarrow1[tuoteno]</td><td>$sarrow1[sarjanumero]</td><td>$sarrow1[ostorivitunnus]</td></tr>";
    echo "<tr><td>$sarrow2[tuoteno]</td><td>$sarrow2[sarjanumero]</td><td>$sarrow2[myyntirivitunnus]</td></tr>";

    if ($tee == "KOHDISTA") {
      $query = "UPDATE sarjanumeroseuranta
                SET ostorivitunnus = '$sarrow2[ostorivitunnus]'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$sarrow1[tunnus]'";
      $sres = pupe_query($query);

      $query = "DELETE
                FROM sarjanumeroseuranta
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$sarrow2[tunnus]'";
      $sres = pupe_query($query);
    }

    $lask++;
  }
}

echo "</table>";

if ($lask > 0 and $tee == "") {
  echo "  <form method='post'>
      <input type='hidden' name='tee' value='KOHDISTA'>
      <input type='submit' value='".t("Tee kohdistus")."'>
      </form>";
}

require "inc/footer.inc";
