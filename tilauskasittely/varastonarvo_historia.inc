<?php

$lock_params = array(
  "locktime"    => 600,
  "lockfile"    => "$kukarow[yhtio]-keikka.lock",
  "filecontent" => "$otunnus;{$kukarow['kuka']};".date("Y-m-d H:i:s")
);

pupesoft_flock($lock_params);

$query  = "SELECT *
           FROM lasku
           WHERE tunnus = '$otunnus'
           AND yhtio    = '$kukarow[yhtio]'
           AND tila     = 'K'
           AND alatila  = ''";
$result = pupe_query($query);

if (mysqli_num_rows($result) == 1) {
  $laskurow = mysqli_fetch_array($result);
}
else {
  echo "<font style='error'>".t("Tilaus katosi!")."</font>";
  exit;
}

list(, , , $kohok, , , , $lisok, , $sarjanrook, , , , , , $varok) = tsekit($laskurow, 0, 0);

if ($kohok == 1 and $lisok == 1 and $sarjanrook == 1 and $varok == 1) {

  if ($tee == "varma") {
    require "varastoon.inc";

    // takaisin ihan alkuun
    $toiminto     = "dummy";
    $ytunnus      = "";
    $toimittajaid   = "";
  }
  else {
    if ($tee == "eitullia") {

      if (isset($poikkeavat_tulliprosentit) and is_array($poikkeavat_tulliprosentit)) {
        // poikkeava_tulliprosentti
        paivita_poikkeavat_tulliprosentit($poikkeavat_tulliprosentit, $rivi_oletus_tulliprosentti);
      }

      $tee = "";
    }

    // katotaan onko liitettyjä laskuja, mutta ei vaihto-omaisuuslaskuja
    $query  = "SELECT *
               from lasku
               where yhtio     = '$kukarow[yhtio]'
               and tila        = 'K'
               and alatila     = ''
               and vanhatunnus <> 0
               and laskunro    = '$laskurow[laskunro]'
               and vienti      not in ('C','F','I','J','K','L')";
    $result = pupe_query($query);

    $summa = 0;

    echo "<br>";

    if (mysqli_num_rows($result) != 0) {
      echo "<table>";
      echo "<tr>
          <th>".t("Nimi")."</th>
          <th>".t("Summa")."</th>
          <th>".t("Veroton")."</th>
          <th>".t("Valuutta")."</th>
          <th>".t("Veroton")." $yhtiorow[valkoodi]</th>
          <th>".t("Viite")."</th>
          <th>".t("Vienti")."</th></tr>";

      while ($llrow = mysqli_fetch_array($result)) {

        // tsekataan vielä
        if ($llrow["vienti_kurssi"] == 0) {
          echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");
          exit;
        }

        $apusumma = round($llrow["arvo"] * $llrow["vienti_kurssi"], 2);
        $summa += $llrow["arvo"] * $llrow["vienti_kurssi"];

        echo "<tr>
            <td>$llrow[nimi]</td>
            <td align='right'>$llrow[summa]</td>
            <td align='right'>$llrow[arvo]</td>
            <td>$llrow[valkoodi]</td>
            <td align='right'>$apusumma</td>
            <td>$llrow[viite]</td>
            <td>$llrow[vienti]</td></tr>";
      }

      // pyöristetään kahteen desimaaliin vasta lopuksi...
      $summa = round($summa, 2);

      echo "</table><br>";
    }

    if ($yhtiorow["jalkilaskenta_kuluperuste"] == "") {
      echo "<font class='message'>";
      echo t("Kululaskuja yhteensä").": ".$summa." $yhtiorow[valkoodi]";
      echo "</font><br>";

      // yhtään kululaskua ei ole liitetty
      if ($summa == 0) {
        echo "<font class='error'>".t("Yhtään kululaskua ei ole liitetty saapumiseen!")."<br>";
      }
    }

    // päivitetään kululaskujen summa kenttään saldo_maksettu keikan otsikolle
    $query  = "UPDATE lasku set saldo_maksettu='$summa', valkoodi='$yhtiorow[valkoodi]'
               where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
    $result = pupe_query($query);

    // haetaan keikan otsikko laskurowhun
    $query  = "SELECT *
               from lasku
               where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 and laskunro='$laskurow[laskunro]'";
    $result = pupe_query($query);
    $laskurow = mysqli_fetch_array($result);

    require "varastoon.inc";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kaikkiok'>";
    echo "<input type='hidden' name='tee' value='varma'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<input type='submit' value='".t("Laske virallinen varastonarvo!")."'>";
    echo "</form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value=''>";
    echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<input type='submit' value='".t("Peruuta")."'>";
    echo "</form>";
  }
}
else {
  echo "<br><br><font class='error'>".t("Saapumisen tiedot eivät täsmää! Korjaa asia")."!</font><br>";
}
