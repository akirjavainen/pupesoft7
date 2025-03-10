<?php

$pupe_DataTables = "asiakaslista";

require "inc/parametrit.inc";
require "inc/asiakas.inc";

echo "<font class='head'>".t("Kopioi asiakas").":</font><hr>";

if ($tee == "write") {

  // Luodaan puskuri, jotta saadaan taulukot kuntoon
  $query = "SELECT *
            FROM asiakas
            WHERE tunnus = '$id'";
  $result = pupe_query($query);
  $trow = mysqli_fetch_array($result);

  // Tarkistetaan
  $errori = '';
  require "inc/asiakastarkista.inc";

  for ($i=1; $i < mysqli_num_fields($result)-1; $i++) {
    asiakastarkista ($t, $i, $result, $tunnus, $virhe, $trow);

    if ($virhe[$i] != "") {
      $errori = 1;
    }
  }

  if ($errori != '') {
    echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivittaa!")."</font>";
  }

  // Luodaan tietue
  if ($errori == '') {
    // Taulun ensimmäinen kenttä on aina yhtiö
    $query = "INSERT into asiakas values ('$kukarow[yhtio]'";
    for ($i=1; $i < mysqli_num_fields($result); $i++) {
      $query .= ",'" . $t[$i] . "'";
    }
    $query .= ")";

    // MUOKKAUS: mysqli_insert_id():
    $result = pupe_query($query, $GLOBALS["link"]);
    $uusiidee = mysqli_insert_id($GLOBALS["link"]);

    //  Tämä funktio tekee myös oikeustarkistukset!
    synkronoi($kukarow["yhtio"], "asiakas", $uusiidee, "", "");

    if (isset($tapahtumat) !== FALSE) {
      $query = "SELECT ytunnus FROM asiakas WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$uusiidee'";
      $result = pupe_query($query);
      $ytrow = mysqli_fetch_array($result);


      $query = "UPDATE kalenteri SET liitostunnus = '$uusiidee', asiakas = '$ytrow[ytunnus]' WHERE yhtio = '$kukarow[yhtio]' AND liitostunnus = '$id' ORDER BY tunnus;";
      $result = pupe_query($query);
    }

    unset($tapahtumat);
    $tee = '';

  }
  else {
    $tee = 'edit';
  }
}

if ($tee == "edit") {

  echo "<form method = 'post' id='mainform'>";
  echo "<input type = 'hidden' name = 'tee' value ='write'>";
  echo "<input type = 'hidden' name = 'id' value ='$id'>";

  // Kokeillaan geneeristä
  $query = "SELECT *
            FROM asiakas
            WHERE tunnus='$id' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);
  $trow = mysqli_fetch_array($result);
  echo "<table>";

  for ($i=0; $i < mysqli_num_fields($result) - 1; $i++) {
    $nimi = "t[$i]";

    require "inc/asiakasrivi.inc";

    if (mysqli_field_name($result, $i) == "muutospvm") {
      $tyyppi = 0;
      $jatko = 0;
      $ulos = '';
    }

    if (mysqli_field_name($result, $i) == "muuttaja") {
      $tyyppi = 0;
      $jatko = 0;
      $ulos = '';
    }
    
    if (mysqli_field_name($result, $i) == "tunnus") { // MUOKKAUS: BUGIKORJAUS (ei monisteta tunnusta)
      $tyyppi = 0;
      $jatko = 0;
      $ulos = '';
    }

    if (mysqli_field_name($result, $i) == 'laatija') {  //speciaali tapaukset
      $tyyppi = 2;
      $trow[$i] = $kukarow["kuka"];
    }
    if (mysqli_field_name($result, $i) == 'luontiaika') {  //speciaali tapaukset
      $tyyppi = 2;
      $trow[$i] = date('Y-m-d H:i:s');
    }

    if   (mysqli_field_len($result, $i)>10) $size='35';
    elseif  (mysqli_field_len($result, $i)<5)  $size='5';
    else  $size='10';

    if ($tyyppi > 0) {
      echo "<tr>";
    }

    if ($tyyppi > 0) {
      echo "<th align='left'>".t(mysqli_field_name($result, $i))."</th>";
    }

    if ($jatko == 0) {
      echo $ulos;
    }
    else {
      $mita = 'text';
      if ($tyyppi != 1) {
        $mita='hidden';
        echo "<td class='back'>";
      }
      else {
        echo "<td>";
      }

      echo "<input type = '$mita' name = '$nimi'";

      if ($errori == '') {
        echo " value = '$trow[$i]' size='$size' maxlength='" . mysqli_field_len($result, $i) ."'>";
      }
      else {
        echo " value = '$t[$i]' size='$size' maxlength='" . mysqli_field_len($result, $i) ."'>";
      }

      if ($tyyppi == 2) {
        echo "$trow[$i]";
      }

      echo "</td>";
    }

    if ($tyyppi > 0 and !empty($virhe[$i])) {
      echo "<td class='back'><font class='error'>$virhe[$i]</font></td></tr>\n";
    }
  }
  echo "</table>";

  if (isset($tapahtumat) !== FALSE) {
    $chk = 'CHECKED';
  }

  echo "<table>";
  echo "<tr><td>";
  echo t("Siirrä kalenteritapahtumat ja asiakasmemot")." <input type = 'checkbox' name = 'tapahtumat' $chk>";
  echo "</td></tr>";
  echo "<tr><td class='back'>";
  echo "<input type = 'submit' value = '".t("Perusta")."'>";
  echo "</td></tr>";
  echo "</table>";
  echo "</form>";

  echo "<form action = 'kopioiasiakas.php' method = 'post'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='submit' value='".t("Takaisin asiakkaan valintaan")."'>";
  echo "</form>";
}

if ($tee == '') {

  $jarjestys = 'nimi';

  $array = explode(",", $kentat);
  $count = count($array);

  foreach ($haku as $ind => $val) {
    if (mb_strlen($val) > 0) {
      $ulisa .= "&haku[$ind]=" . $val;

      // Tutkitaan myös löytyykö hakukentän arvoa toimitusosoiteen vastaavasta kentästä
      // Ytunnuksella ja asiakasnumerolal ei kuitenkaan ole omaa kenttää
      // toimitusosoitteessa, joten katsoteen ne vain varsinaisesta kentästä.
      if ($ind == "ytunnus" or $ind == "asiakasnro") {
        $lisa .= " and $ind like '%$val%'";
      }
      else {
        $lisa .= " and ($ind like '%$val%' or toim_$ind like '%$val%')";
      }
    }
  }

  if (mb_strlen($ojarj) > 0) {
    $jarjestys = $ojarj;
  }

  $query = "SELECT asiakas.tunnus,
            concat(if(asiakas.nimi='', '**N/A**', asiakas.nimi), '<br>', asiakas.toim_nimi) nimi,
            concat(asiakas.nimitark, '<br>', asiakas.toim_nimitark) nimitark,
            concat(asiakas.postitp, '<br>', asiakas.toim_postitp) postitp,
            concat(asiakas.ytunnus) ytunnus,
            concat(asiakas.ovttunnus, '<br>', asiakas.toim_ovttunnus) ovttunnus,
            asiakas.asiakasnro
            FROM asiakas
            WHERE yhtio = '$kukarow[yhtio]'
            $lisa
            $ryhma
            ORDER BY $jarjestys
            LIMIT 100";
  $result = pupe_query($query);

  echo "<form action = '$PHP_SELF' method = 'post'>";
  echo "<table><tr>";

  echo "<th valign='top' align='left'><a href = '?ojarj=nimi".$ulisa ."'>".t("Nimi")."</a>";
  echo "<br><input type='text' name = 'haku[nimi]' value = '$haku[nimi]'>";
  echo "</th>";

  echo "<th valign='top' align='left'><a href = '?ojarj=nimitark".$ulisa ."'>".t("Nimitark")."</a>";
  echo "<br><input type='text' name = 'haku[nimitark]' value = '$haku[nimitark]'>";
  echo "</th>";

  echo "<th valign='top' align='left'><a href = '?ojarj=postitp".$ulisa ."'>".t("Postitp")."</a>";
  echo "<br><input type='text' name = 'haku[postitp]' value = '$haku[postitp]'>";
  echo "</th>";

  echo "<th valign='top' align='left'><a href = '?ojarj=ytunnus".$ulisa ."'>".t("Ytunnus")."</a>";
  echo "<br><input type='text' name = 'haku[ytunnus]' value = '$haku[ytunnus]'>";
  echo "</th>";

  echo "<th valign='top' align='left'><a href = '?ojarj=ovttunnus".$ulisa ."'>".t("Ovttunnus")."</a>";
  echo "<br><input type='text' name = 'haku[ovttunnus]' value = '$haku[ovttunnus]'>";
  echo "</th>";

  echo "<th valign='top' align='left'><a href = '?ojarj=asiakasnro".$ulisa ."'>".t("Asiakasnro")."</a>";
  echo "<br><input type='text' name = 'haku[asiakasnro]' value = '$haku[asiakasnro]'>";
  echo "</th>";

  echo "<td valign='bottom' class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></form></tr>";

  while ($trow = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td><a href='$PHP_SELF?id=$trow[tunnus]&tee=edit'>$trow[nimi]</a></td>";
    echo "<td>$trow[nimitark]</td>";
    echo "<td>$trow[postitp]</td>";
    echo "<td>".tarkistahetu($trow["ytunnus"])."</td>";
    echo "<td>$trow[ovttunnus]</td>";
    echo "<td>$trow[asiakasnro]</td>";

    echo "</tr>";
  }
  echo "</table>";
}

require "inc/footer.inc";
