<?php
//Jos tullaan jtselauksesta niin huijataan
if ($mista== 'jtselaus') {
  $kukarow['kesken']=$laskurow['tunnus'];

  // Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
  $query = "SELECT *
            from tilausrivi
            where yhtio = '$kukarow[yhtio]'
            and otunnus = '$kukarow[kesken]'
            and netto   = ''";
  $jresultlask = pupe_query($query);
}
elseif ($mista== 'jtrivit_tilaukselle.inc') {
  // Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
  $query = "SELECT *
            from tilausrivi
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'
            and netto   = ''";
  $jresultlask = pupe_query($query);
}
elseif ($mista== 'jtrivit_tilaukselle.php' and $kukarow['extranet'] != '') {
  // Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
  $query = "SELECT *
            from tilausrivi
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'
            and netto   = ''";
  $jresultlask = pupe_query($query);

  $laskurow["ytunnus"] = $kukarow["oletus_asiakas"];
}

while ($jjrow = mysqli_fetch_array($jresultlask)) {

  $query = "SELECT *
            from tuote
            where yhtio='$kukarow[yhtio]' and tuoteno='$jjrow[tuoteno]'";
  $tres  = pupe_query($query);
  $trow  = mysqli_fetch_array($tres);

  $query = "SELECT alennus
            FROM asiakasalennus
            WHERE yhtio='$kukarow[yhtio]' and ryhma = '$trow[aleryhma]' and ytunnus = '$laskurow[ytunnus]'";
  $aleresult = pupe_query($query);

  // TODO: Tämä koko osio on mystistä paskaa!
  if (mysqli_num_rows($aleresult) != 0) {
    $alerow = mysqli_fetch_array($aleresult);

    //jos rivi on katemyyty tai käyttäjä haluaa, lasketaan ale uudestaan
    if (($alerow["alennus"][0] == "-" and $trow["kehahin"] > 0) or $laskeuusix != "") { // MUOKKAUS: deprecated -> []

      $debug = 0;

      if ($debug == 1) {
        echo "vienti=$vienti alv=$alv netto=$netto akplhinta=$akplhinta aale=$aale rowtilkpl=$jjrow[tilkpl] rowalv=$jjrow[alv] rowhinta=$jjrow[hinta] ";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          echo "rowale{$alepostfix}=", $jjrow["ale{$alepostfix}"], " ";
        }
        echo "apuroytunnus=$apuro[ytunnus] ytunnus=$ytunnus apuroytunnus=$apuro[ytunnus] mista=$mista tunnus=$tunnus<br>";
        echo "jrowtunnus=$jjrow[tunnus]<br>";
      }

      list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $jjrow['tilkpl'], '', '', '');
      list($hinta, $alv) = alv($laskurow, $trow, $hinta, $jjrow["alv"], $alehinta_alv);

      $ale_query_update_lisa = '';

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $ale_query_update_lisa .= " ale{$alepostfix} = '".$ale["ale{$alepostfix}"]."', ";
      }

      $query = "UPDATE tilausrivi set hinta='$hinta', {$ale_query_update_lisa} netto='$netto', alv='$alv' where yhtio='$kukarow[yhtio]' and tunnus='$jjrow[tunnus]'";
      $tres  = pupe_query($query);

      if ($debug == 1) {
        echo "$query<br>";
      }

      $netto  = '';
      $hinta  = 0;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        ${'ale'.$alepostfix} = 0;
      }

      $alv  = 0;
    }
  }
}
