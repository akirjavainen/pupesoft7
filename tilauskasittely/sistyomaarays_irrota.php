<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Irrota lisävaruste laitteesta").":<hr></font>";

if ($tee == "VALMIS") {

  //Haetaan jatkojalostettavan tuotteen ostorivitunnus
  $query = "UPDATE sarjanumeroseuranta
            SET siirtorivitunnus = 0
            WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and siirtorivitunnus='$siirtorivitunnus'";
  $sarjares = pupe_query($query);


  $query = "UPDATE tilausrivi
            SET perheid2 = 0
            WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and tunnus='$rivitunnus'";
  $sarjares = pupe_query($query);


  // Päivitetään saldo_varattu-kenttää
  $query = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso, tilkpl
            FROM tilausrivi
            WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and tunnus='$rivitunnus'";
  $sarjares = pupe_query($query);
  $sarjarow = mysqli_fetch_array($sarjares);

  $query = "UPDATE tuotepaikat
            SET saldo_varattu = saldo_varattu - $sarjarow[tilkpl]
            WHERE yhtio   = '$kukarow[yhtio]'
            and tuoteno   = '$tuoteno'
            and hyllyalue = '$sarjarow[hyllyalue]'
            and hyllynro  = '$sarjarow[hyllynro]'
            and hyllyvali = '$sarjarow[hyllyvali]'
            and hyllytaso = '$sarjarow[hyllytaso]'
            LIMIT 1";
  $sarjares = pupe_query($query);

  echo t("Lisävaruste irrotettu laitteesta")."!<br><br>";

  $tee = "";
}


if ($tee == "") {

  // Näytetään muuten vaan sopivia tilauksia
  echo "<br><form method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <font class='message'>".t("Etsi laite").":<hr></font>
      ".t("Syötä sarjanumero").":
      <input type='text' name='etsi' value='$etsi'>
      <input type='submit' class='hae_btn' value='".t("Etsi")."'>
      </form>";


  if ($etsi != '') {

    //Tutkitaan lisävarusteita
    $query = "SELECT tilausrivi_osto.perheid2, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.sarjanumero
              FROM sarjanumeroseuranta
              JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
              WHERE sarjanumeroseuranta.yhtio     = '$kukarow[yhtio]'
              and sarjanumeroseuranta.sarjanumero = '$etsi'";
    $sarjares = pupe_query($query);

    echo "<br><br><table>";

    while ($sarjarow = mysqli_fetch_array($sarjares)) {
      // Haetaan muut lisävarusteet
      $query = "SELECT tuoteno, perheid2, tilkpl, tyyppi, tunnus
                FROM tilausrivi use index (yhtio_perheid2)
                WHERE yhtio   = '$kukarow[yhtio]'
                and perheid2  = '$sarjarow[perheid2]'
                and tyyppi   != 'D'
                and tunnus   != '$sarjarow[perheid2]'
                and perheid2!= 0";
      $sarjares1 = pupe_query($query);

      if (mysqli_num_rows($sarjares1) > 0) {
        echo "<tr><th>$sarjarow[tuoteno]</th><th>$sarjarow[sarjanumero]</th></tr>";

        while ($sarjarow1 = mysqli_fetch_array($sarjares1)) {
          echo "<tr><td>$sarjarow1[tuoteno]</td><td>$sarjarow1[tilkpl]</td>";

          if ($sarjarow1["tyyppi"] == "G") {
            echo "<td class='back'>
                <form method='post'>
                <input type='hidden' name='tee' value='VALMIS'>
                <input type='hidden' name='etsi' value='$etsi'>
                <input type='hidden' name='tuoteno' value='$sarjarow1[tuoteno]'>
                <input type='hidden' name='siirtorivitunnus' value='$sarjarow[perheid2]'>
                <input type='hidden' name='rivitunnus' value='$sarjarow1[tunnus]'>
                <input type='submit' value='".t("Irrota lisävaruste")."'>
                </form>
                </td>";
          }
          else {
            echo "<td class='back'>".t("Tehdaslisävarustetta ei voida irrottaa")."</td>";
          }

          echo "</tr>";
        }
      }
    }
    echo "</table>";
  }
}

/*
// Näytetään kaikki
$query  = "SELECT sarjanumeroseuranta.*,
           if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys) nimitys,
           tuote.myyntihinta                   tuotemyyntihinta,
           lasku_osto.tunnus                  osto_tunnus,
           lasku_osto.nimi                    osto_nimi,
           lasku_myynti.tunnus                  myynti_tunnus,
           lasku_myynti.nimi                  myynti_nimi,
           lasku_myynti.tila                  myynti_tila,
           (tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)    ostohinta,
           tilausrivi_osto.tunnus                osto_rivitunnus,
           tilausrivi_osto.perheid2              osto_perheid2,
           (tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)  myyntihinta,
           varastopaikat.nimitys                varastonimi,
           sarjanumeroseuranta.lisatieto            lisatieto,
           sarjanumeroseuranta.hyllyalue,
           sarjanumeroseuranta.hyllynro,
           sarjanumeroseuranta.hyllyvali,
           sarjanumeroseuranta.hyllytaso
           FROM sarjanumeroseuranta
           JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
           LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
           LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
           LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
           LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
           LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
            AND varastopaikat.tunnus                 = sarjanumeroseuranta.varasto)
           WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
           and sarjanumeroseuranta.myyntirivitunnus != -1
           and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
           and tilausrivi_osto.laskutettuaika       != '0000-00-00'
           HAVING osto_rivitunnus = osto_perheid2
           ORDER BY sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.myyntirivitunnus";
$sarjares = pupe_query($query);

while ($sarjarow = mysqli_fetch_array($sarjares)) {

  // Haetaan muut lisävarusteet
  $query = "SELECT tuoteno, perheid2, tilkpl, tyyppi, tunnus
            FROM tilausrivi use index (yhtio_perheid2)
            WHERE yhtio   = '$kukarow[yhtio]'
            and perheid2  = '$sarjarow[osto_perheid2]'
            and tyyppi   != 'D'
            and tunnus   != '$sarjarow[osto_perheid2]'
            and perheid2!= 0";
  $sarjares1 = pupe_query($query);

  if (mysqli_num_rows($sarjares1) > 0) {
    echo "<br><br><table>";
    echo "<tr><th>$sarjarow[tuoteno]</th><th>$sarjarow[sarjanumero]</th><th>$sarjarow[hyllyalue] $sarjarow[hyllynro] $sarjarow[hyllyvali] $sarjarow[hyllytaso]</th></tr>";

    while ($sarjarow1 = mysqli_fetch_array($sarjares1)) {
      echo "<tr><td>$sarjarow1[tuoteno]</td><td>$sarjarow1[tilkpl]</td>";

      $query = "UPDATE tuotepaikat
                set saldo_varattu   = saldo_varattu+1
                WHERE tuoteno = '$sarjarow1[tuoteno]'
                and yhtio     = '$kukarow[yhtio]'
                and hyllyalue = '$sarjarow[hyllyalue]'
                and hyllynro  = '$sarjarow[hyllynro]'
                and hyllyvali = '$sarjarow[hyllyvali]'
                and hyllytaso = '$sarjarow[hyllytaso]'";
      $rresult = pupe_query($query);

      echo "</tr>";
    }
    echo "</table>";
  }
}
*/

require "../inc/footer.inc";
