<?php

if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

if (isset($toiminto) and $toiminto == 'vaihda_oletusasiakkuus' and $asiakasvalinta != '') {
  paivita_kayttajan_oletusasiakkuus($asiakasvalinta);

  // päivitetään kukarow kesken jos on vaihdettu asiakas
  $til_qry = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laatija  = '{$kukarow['laatija']}'
              AND ohjelma_moduli = 'EXTRANET'
              AND tila = 'N'
              AND alatila = ''
              AND liitostunnus = '{$asiakasvalinta}'
              ORDER BY tunnus desc
              LIMIT 1";
  $til_res = pupe_query($til_qry);
  if (mysqli_num_rows($til_res) == 1) {
    $tilrow = mysqli_fetch_assoc($til_res);
    $kukarow['kesken'] == $tilrow['tunnus'];

    $query  = "UPDATE kuka set kesken='{$tilrow['tunnus']}' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
  else {
    $kukarow['kesken'] == 0;

    $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
}

if (!isset($livesearch_tee)) {
  piirra_multiasiakkuusdropdown($asiakasvalikko_rajaus);
}

function piirra_multiasiakkuusdropdown($asiakasvalikko_rajaus = "") {
  global $kukarow, $toim, $palvelin2, $tilauskaslisa;

  echo "<form>";
  echo "<th>
          <label for='asiakasvalikko_rajaus'>" . t("Rajaa alasvetovalikkoa") . ":</label>
        </th>";
  echo "<br><td>
          <input type='text'
                 name='asiakasvalikko_rajaus'
                 id='asiakasvalikko_rajaus'
                 value='$asiakasvalikko_rajaus'>
          <input type='submit' value='" . t("Rajaa") . "'>
        </td>";
  echo "<input type='hidden' name='toim'     value='$toim'>";
  echo "</form><br><br>";

  if (!empty($asiakasvalikko_rajaus)) {
    $wherelisa = " AND concat(asiakas.nimi, asiakas.nimitark) LIKE '%{$asiakasvalikko_rajaus}%'";
  }

  // Haetaan käyttäjän asiakkuudet
  $query = "SELECT asiakas.tunnus, asiakas.nimi, asiakas.nimitark, asiakas.selaus
            FROM customers_users
            JOIN asiakas ON (customers_users.customer_id = asiakas.tunnus)
            WHERE user_id = '{$kukarow['tunnus']}'
            {$wherelisa}
            ORDER BY asiakas.nimi, asiakas.nimitark, asiakas.selaus";
  $multiresult = pupe_query($query);

  // Jos ei ole linkattu mitään asiakkaita, ei piirretä mitään
  if (mysqli_num_rows($multiresult) == 0 and empty($kukarow['oletus_asiakas'])) {
    return;
  }

  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*
            FROM asiakas
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus  = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $row = mysqli_fetch_assoc($result);
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='vaihda_oletusasiakkuus'>";
  echo "<input type='hidden' name='toim'     value='$toim'>";

  // Piirretään dropdown
  echo "<table><tr>";
  echo "<td><select name='asiakasvalinta' value='{$kukarow['oletus_asiakas']}' onchange='submit();'>";
  echo "<option value='' selected>".t("Oletusasiakkuus")."</option>";

  while ($multirow = mysqli_fetch_assoc($multiresult)) {
    if (empty($multirow['selaus']))  {

      $_nimitark = !empty($multirow['nimitark']) ? ', '.$multirow['nimitark'] : '';
      echo "<option value='{$multirow['tunnus']}'>".$multirow['nimi'].$_nimitark."</option>";
    }
    else {
      echo "<option value='{$multirow['tunnus']}'>".$multirow['selaus']."</option>";
    }
  }

  echo "</select>";
  echo "</td>";

  if (isset($row['nimi'])) {
    if (empty($row['selaus'])) {
      $_nimitark = !empty($row['nimitark']) ? ', '.$row['nimitark'] : '';
      echo "<td class='back'>".$row['nimi'].$_nimitark."</td>";
    }
    else {
      echo "<td class='back'>".$row['selaus']."</td>";
    }
  }
  echo "</tr></table></form>";
}

function paivita_kayttajan_oletusasiakkuus($asiakasvalinta) {
  global $kukarow;

  $query = "UPDATE kuka
            SET oletus_asiakas = '$asiakasvalinta'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$kukarow['tunnus']}'";
  pupe_query($query);
  $kukarow['oletus_asiakas'] = $asiakasvalinta;

  return true;
}
