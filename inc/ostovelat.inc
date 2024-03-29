<?php

echo "<font class='head'>".t("Sidottupääoma")."</font><hr>";

echo "<form method='post'><input type='hidden' name='toim' value='ostovelat'>";
echo "<table><tr><th></th><th></th>";

if (isset($tkausi)) {
  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
  $vresult = pupe_query($query);

  if (mysqli_num_rows($vresult) == 1) {
    $tilikaudetrow = mysqli_fetch_array($vresult);
  }
}

if (is_array($tilikaudetrow)) {
  $yhtiorow['tilikausi_alku'] = $tilikaudetrow['tilikausi_alku'];
  $yhtiorow['tilikausi_loppu'] = $tilikaudetrow['tilikausi_loppu'];
}

$raja = '0000-00';

while ($raja < mb_substr($yhtiorow['tilikausi_loppu'], 0, 7)) {
  $vuosi = mb_substr($yhtiorow['tilikausi_alku'], 0, 4);
  $kk = mb_substr($yhtiorow['tilikausi_alku'], 5, 2);
  $kk += $j;

  if ($kk > 12) {
    $vuosi++;
    $kk-=12;
  }
  if ($kk < 10) $kk = '0'.$kk;

  $raja = $vuosi . "-" . $kk;

  echo "<th>$raja</th>";
  $j++;
}
echo "</tr>";

$sarakkeita = $j;

for ($i=1; $i<8; $i++) {

  $query = "SELECT distinct nimi, taso
            FROM taso
            WHERE yhtio = '$kukarow[yhtio]'
            and taso    < '3'
            ORDER BY taso";
  $sresult = pupe_query($query);

  echo "<tr><td><select name='taso[$i]'>";
  echo "<option value=''>".t("Ei valintaa")."</option>";

  while ($srow = mysqli_fetch_array($sresult)) {
    $sel = '';
    if ($taso[$i] == $srow[1]) {
      $sel = "selected";
    }
    echo "<option value='$srow[1]' $sel>$srow[0] ($srow[1])</option>";
  }
  echo "</select></td>";


  if (trim($taso[$i]) != '') {
    $lisa='';
    $raja='0000-00';
    $j=0;

    while ($raja < mb_substr($yhtiorow['tilikausi_loppu'], 0, 7)) {
      $vuosi=mb_substr($yhtiorow['tilikausi_alku'], 0, 4);
      $kk=mb_substr($yhtiorow['tilikausi_alku'], 5, 2);
      $kk += $j;

      if ($kk > 12) {
        $vuosi++;
        $kk-=12;
      }
      if ($kk<10) $kk= '0'.$kk;

      $raja = $vuosi . "-" . $kk;

      if ($kumulatiivinen != '')
        $merkki = "<=";
      else
        $merkki = "=";

      if ($tuhat != '')
        $lisa.= ", round(sum(if(left(tapvm,7) $merkki '$raja', summa,0))/1000,0)";
      else
        $lisa.= ", sum(if(left(tapvm,7) $merkki '$raja', summa,0))";

      $j++;
    }

    $query = "SELECT taso.nimi $lisa
              FROM tili, tiliointi USE INDEX (yhtio_tilino_tapvm), taso
              WHERE tili.yhtio       = '$kukarow[yhtio]'
              and tapvm              >= '$yhtiorow[tilikausi_alku]'
              and tapvm              <= '$yhtiorow[tilikausi_loppu]'
              and korjattu=''
              and tiliointi.yhtio=tili.yhtio
              and tiliointi.tilino=tili.tilino
              and tili.ulkoinen_taso like '$taso[$i]%'
              and tiliointi.yhtio=taso.yhtio
              and taso.taso          = '$taso[$i]'
              and taso.tyyppi ='U'
              GROUP BY 1";
    $result = pupe_query($query);

    while ($trow = mysqli_fetch_array($result)) {
      for ($j=0; $j<mysqli_num_fields($result); $j++) {
        echo "<td align = 'right'>";
        echo $trow[$j];
        echo "</td>";

        if ($j > 0) $col[$j]+=$trow[$j];
      }
    }
  }
  else
    for ($j=0; $j<$sarakkeita+1; $j++) echo "<td></td>";

  echo "</tr>";
}

echo "<tr><th></th><th>".t('Yhteensä')."</th>";
for ($j=1; $j<$sarakkeita +1; $j++) echo "<th>$col[$j]</th>";
echo "</tr>";
if (!isset($kumulatiivinen) and !is_array($taso)) $kumulatiivinen='on';
if ($kumulatiivinen != '') $kumulatiivinen = "checked";
if (!isset($tuhat) and !is_array($taso)) $tuhat='on';
if ($tuhat != '') $tuhat = "checked";
echo "</table>";

echo "<table><tr>";
echo "<td>".t("Tilikausi");

$query = "SELECT *
          FROM tilikaudet
          WHERE yhtio = '$kukarow[yhtio]'
          ORDER BY tilikausi_alku";
$vresult = pupe_query($query);

echo " <select name='tkausi'>";

while ($vrow = mysqli_fetch_array($vresult)) {
  $sel="";
  if ($tkausi == $vrow['tunnus']) {
    $sel = "selected";
  }
  echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
}
echo "</select></td>";

echo "<td><input type='checkbox' name='kumulatiivinen' $kumulatiivinen> ".t("Kumulatiivinen")."</td>";
echo "<td><input type='checkbox' name='tuhat' $tuhat> ".t("Jaa luvut 1000:lla")."</td>";
echo "<td><input type='submit' value='".t("Näytä")."'></td></tr></table></form>";
