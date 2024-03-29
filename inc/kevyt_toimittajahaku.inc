<?php

// tarvitaan $ytunnus ja $kukarow
// palautetaan $toimittajarow

$ytunnus = addslashes(trim($ytunnus));
$limit   = 300; // montako riviä on maksimi
$monta   = 0;

if (is_numeric($toimittajaid) and $toimittajaid > 0) {
  $query  = "SELECT *
             FROM toimi
             WHERE yhtio = '{$kukarow["yhtio"]}'
             AND tunnus  = '{$toimittajaid}'";
  $result = pupe_query($query);
  $monta  = mysqli_num_rows($result);
}

if ($monta == 0 and $ytunnus != '') {
  //etsitään eka koko ytunnuksella
  $query  = "SELECT *
             FROM toimi
             WHERE yhtio  = '{$kukarow["yhtio"]}'
             AND ytunnus  = '{$ytunnus}'
             AND tyyppi  != 'P'
             ORDER BY ytunnus, ".poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, tunnus";
  $result = pupe_query($query);
  $monta  = mysqli_num_rows($result);
}

if ($monta == 0 and $ytunnus != '') {
  //etsitään eka koko toimittajanrolla
  $query  = "SELECT *
             FROM toimi
             WHERE yhtio        = '{$kukarow["yhtio"]}'
             AND toimittajanro  = '{$ytunnus}'
             AND tyyppi        != 'P'
             ORDER BY toimittajanro, ".poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, ytunnus, tunnus";
  $result = pupe_query($query);
  $monta  = mysqli_num_rows($result);
}

if ($monta == 0 and $ytunnus != '') {
  // kokeillaan ytunnuksen ja toimittajanumeron alulla
  $query  = "(SELECT *
              FROM toimi
              WHERE yhtio        = '{$kukarow["yhtio"]}'
              AND ytunnus        like '{$ytunnus}%'
              AND tyyppi        != 'P')
              UNION
              (SELECT *
              FROM toimi
              WHERE yhtio        = '{$kukarow["yhtio"]}'
              AND toimittajanro  like '{$ytunnus}%'
              AND tyyppi        != 'P')
              ORDER BY ytunnus, toimittajanro, ".poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, tunnus";
  $result = pupe_query($query);
  $monta  = mysqli_num_rows($result);
}

if ($monta == 0 and $ytunnus != '') {
  // kokeillaan nimen osalla
  $query  = "SELECT *
             FROM toimi
             WHERE yhtio  = '{$kukarow["yhtio"]}'
             AND (nimi like '%{$ytunnus}%' or nimitark like '%{$ytunnus}%')
             AND tyyppi  != 'P'
             ORDER BY ".poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, ytunnus, tunnus";
  $result = pupe_query($query);
  $monta  = mysqli_num_rows($result);
}

if ($monta==0) {
  if (mb_strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
    echo "<font class='error'>".t("Toimittajaa")." '$ytunnus' ".t("ei löydy")."!</font><br>";
  }
  $ytunnus     = "";
  $toimittajaid   = "";
}

if ($monta==1) {
  $toimittajarow   = mysqli_fetch_array($result);
  $ytunnus     = $toimittajarow['ytunnus'];
  $toimittajaid   = $toimittajarow['tunnus'];
}

if ($monta > 1 and $monta <= $limit) {
  //tämä vain otsik.php:tä varten
  echo $lause;

  if ($kutsuja != "yllapito.php") {
    echo "<table>";
    echo "<tr>
        <th>".t("Ytunnus")."</th>
        <th>".t("Toimittajanro")."</th>
        <th>".t("Nimi")."</th>
        <th colspan='3'>".t("Osoite")."</th>
        <th>".t("Tilinumero")."</th>
        <td class='back'></td>
      </tr>";
  }
  while ($toimittaja = mysqli_fetch_array($result)) {

    if ($kutsuja != "yllapito.php") {

      if ($toimittaja['ytunnus'] != '') $numero = trim($toimittaja['ytunnus']);
      else die("VIRHE: Toimittajalla ei ole ytunnusta! Korjaa asia!");

      echo "<tr class='aktiivi'>
          <td>".tarkistahetu($toimittaja["ytunnus"])."</td>
          <td>$toimittaja[toimittajanro]</td>
          <td>$toimittaja[nimi] $toimittaja[nimitark]</td>
          <td>$toimittaja[osoite]</td>
          <td>$toimittaja[postino]</td>
          <td>$toimittaja[postitp]</td>";

      if (mb_strtoupper($toimittaja['maa']) != mb_strtoupper($yhtiorow['maa'])) {
        echo "<td>$toimittaja[ultilno]</td>";
      }
      else {
        echo "<td>$toimittaja[tilinumero]</td>";
      }

      echo "<td class='back'>";
      if (mb_stristr($PHP_SELF, 'ostolasku_asn_tarkastelu')) {
        if ($valitse == 'asn') {
          $action = "&asn_numerot={$asn_numerot}&valitse={$valitse}";
        }
        else {
          $action = "&tilausnumero={$tilausnumero}&valitse={$valitse}";
        }
        echo "<form id='toimittaja_$toimittaja[tunnus]' name='toimittaja_$toimittaja[tunnus]' action=\"?tee=vaihdatoimittaja{$action}\" method='post'>";
      }
      else {
        echo "<form id='toimittaja_$toimittaja[tunnus]' name='toimittaja_$toimittaja[tunnus]' action=\"$PHP_SELF\" method='post'>";
      }

      echo "<input type='hidden' name='toimittajaid' value='$toimittaja[tunnus]'>";

      if (isset($lopetus)) echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      if (isset($asiakasid)) echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
      if (isset($tee)) echo "<input type='hidden' name='tee' value='$tee'>";
      if (isset($tila)) echo "<input type='hidden' name='tila' value='$tila'>";
      if (isset($maara)) echo "<input type='hidden' name='maara' value='$maara'>";
      if (isset($toim)) echo "<input type='hidden' name='toim' value='$toim'>";
      if (isset($nayta_rivit)) echo "<input type='hidden' name='nayta_rivit' value='$nayta_rivit'>";
      if (isset($tunnus)) echo "<input type='hidden' name='tunnus' value='$tunnus'>";
      if (isset($keikalla)) echo "<input type='hidden' name='keikalla' value='$keikalla'>";
      if (isset($muutparametrit)) echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
      if (isset($tilausnumero)) echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
      if (isset($numero)) echo "<input type='hidden' name='ytunnus' value='$numero'>";
      if (isset($keikka)) echo "<input type='hidden' name='keikka' value='$keikka'>";
      if (isset($myopp)) echo "<input type='hidden' name='myopp' value='$myopp'>";
      if (isset($myokk)) echo "<input type='hidden' name='myokk' value='$myokk'>";
      if (isset($myovv)) echo "<input type='hidden' name='myovv' value='$myovv'>";
      if (isset($toimipaikka)) echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
      if (isset($kayta_ostotilausta)) echo "<input type='hidden' name='kayta_ostotilausta' value='$kayta_ostotilausta'>";
      if (isset($mul_tuoteryhma)) echo "<input type='hidden' name='mul_tuoteryhma' value='".base64_encode(serialize($mul_tuoteryhma))."'>";
      if (isset($mul_kustannuspaikka)) echo "<input type='hidden' name='mul_kustannuspaikka' value='".base64_encode(serialize($mul_kustannuspaikka))."'>";
      if (isset($vain_excel)) echo "<input type='hidden' name='vain_excel' value='$vain_excel'>";

      echo "<input type='submit' value='".t("Valitse")."' onclick=\"this.form.id='kevyt_toimittajahaku';\">";
      echo "</form>";
      echo "</td></tr>";
    }
    else {
      $ulos2 .= "<option value='$toimittaja[tunnus]'>$toimittaja[ytunnus] $toimittaja[toimittajanro] $toimittaja[nimi] $toimittaja[nimitark] </option>";
    }

  }
  echo "</table><br>";
  $ytunnus = "";
}

if ($monta > $limit) {
  echo "<font class='error'>".t("VIRHE: Toimittajahaulla löytyi")." $monta ".t("sopivaa toimittajaa. Tarkenna hakuasi")."!</font><br><br>";
  $ytunnus="";
}
