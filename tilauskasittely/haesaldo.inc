<?php

// tarvitaan tuotenumero, hyllyalue, hyllynro, hyllyvali, hyllytaso, $rivi
// palautetaan $ennakkopoistot, $jtssa ja $saldo (jossa todellinen saldo)

//ennaakkopoistot
$query = "  select sum(varattu)
      from tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
      where hyllyalue='$hyllyalue'
      and hyllynro='$hyllynro'
      and hyllytaso='$hyllytaso'
      and hyllyvali='$hyllyvali'
      and tunnus<>'$rivi'
      and yhtio='$kukarow[yhtio]'
      and tuoteno='$tuotenumero'
      and tyyppi in ('L','G','V')
      and varattu!=0";
$sres  = pupe_query($query);
$srow  = mysqli_fetch_array($sres);
$ennakkopoistot  = $srow[0];

//jälkitoimituksessa olevat
$query = "SELECT sum(jt) jt, lasku.ytunnus ytunnus
          FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus), lasku use index (PRIMARY)
          WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
          and tilausrivi.tyyppi='L'
          and tilausrivi.var='J'
          and tilausrivi.tuoteno = '$tuotenumero'
          and tilausrivi.tunnus  <> '$rivi'
          and lasku.yhtio=tilausrivi.yhtio
          and tilausrivi.otunnus=lasku.tunnus
          and tilausrivi.varattu=0
          and tilausrivi.kpl=0
          and tilausrivi.jt!=0
          GROUP BY lasku.ytunnus";
$jsres  = pupe_query($query);

$jtssa = "";
$jtsum = 0;

while ($jsrow  = mysqli_fetch_array($jsres)) {
  $jtssa .= t("HUOM: Asiakkaalle")." $jsrow[ytunnus] ".t("on ennaltavarattu")." $jsrow[jt] ".t("kpl tätä tuotetta")."!<br>";
  $jtsum += $jsrow["jt"];
}


$query = "select * from tuotepaikat where tuoteno='$tuotenumero' and yhtio='$kukarow[yhtio]' and hyllyalue='$hyllyalue' and hyllynro='$hyllynro' and hyllytaso='$hyllytaso' and hyllyvali='$hyllyvali'";
$tpres = pupe_query($query);

// jos pyydetyltä tuotepaikalta ei löydy saldoa laitetaan nollaksi
if (mysqli_num_rows($tpres)==0) {
  $saldo = 0;
}
else {
  $tprow = mysqli_fetch_array($tpres);
  $saldo = $tprow['saldo'] - $ennakkopoistot;
}
