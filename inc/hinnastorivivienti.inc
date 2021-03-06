<?php

function hinnastorivi($tuote, $laskurow) {
  global $kukarow, $yhtiorow, $kl_alenimi;

  static $rivi = 1;

  //haetaan aleryhmän tunnus
  $query = "SELECT b.tunnus, b.ryhma
            FROM tuote a
            LEFT JOIN perusalennus b ON a.yhtio = b.yhtio and a.aleryhma = b.ryhma
            WHERE a.tuoteno = '$tuote[tuoteno]'
            and a.yhtio     = '$kukarow[yhtio]'";
  $aleresult = pupe_query($query);
  $alerow = mysqli_fetch_array($aleresult);

  if ($kl_alenimi == "") {
    $aletun = $alerow['ryhma'];
  }
  else {
    $aletun = $alerow['tunnus'];
  }

  $tunpit = mb_strlen($aletun);

  //korvaavat tuotteet
  $koti     = '';
  $edellinen   = '';
  $seuraava   = '';
  $korvaavat  = '';

  if ($tuote["id"] > 0) {
    $query = "select * from korvaavat where id='$tuote[id]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
    $korvaresult = pupe_query($query);

    $lask = 0;
    while ($korvarow = mysqli_fetch_array($korvaresult)) {
      $korvaavat[$lask] = $korvarow["tuoteno"];
      if ($korvarow["tuoteno"] == $tuote["tuoteno"]) {
        $koti = $lask;
      }
      $lask++;
    }
    //tässä listan viimeinen indeksi
    $lask--;

    //edellinen ja seuraava korvaava
    if ($koti == 0) {
      $edellinen  = '';
      $seuraava  = $korvaavat[$koti+1];
    }
    elseif ($koti == $lask) {
      $edellinen  = $korvaavat[$koti-1];
      $seuraava  = '';
    }
    else {
      $edellinen  = $korvaavat[$koti-1];
      $seuraava  = $korvaavat[$koti+1];
    }
  }

  $myyntihinta = hintapyoristys(tuotteen_myyntihinta($laskurow, $tuote, 1, "ei"));

  $out  = sprintf('%-20.20s'  , $tuote["tuoteno"]);
  $out .= sprintf('%-60.60s'  , t_tuotteen_avainsanat($tuote, 'nimitys'));
  $out .= sprintf('%-10.10s'  , t_avainsana("Y", "", "and avainsana.selite='$tuote[yksikko]'", "", "", "selite"));
  $out .= sprintf('%-15.15s'  , $aletun);
  $out .= sprintf('%08d'    , str_replace('.', '', $myyntihinta));
  $out .= sprintf('%08d'    , str_replace('.', '', $myyntihinta));
  $out .= sprintf('%-20.20s'  , $seuraava);
  $out .= sprintf('%-20.20s'  , $edellinen);
  $out .= sprintf('%-3.3s'  , '');
  $out .= "\r\n";

  return $out;
}
