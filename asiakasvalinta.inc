<?php

if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

if (isset($toiminto) and $toiminto == 'vaihda_oletusasiakkuus' and $asiakasvalinta != '') {
  paivita_kayttajan_oletusasiakkuus($asiakasvalinta);
}

piirra_multiasiakkuusdropdown();

function piirra_multiasiakkuusdropdown() {
  global $kukarow;

  // Haetaan käyttäjän asiakkuudet
  $query = "SELECT asiakas.tunnus, asiakas.nimi, asiakas.nimitark, asiakas.selaus
            FROM customers_users
            JOIN asiakas ON (customers_users.customer_id = asiakas.tunnus)
            WHERE user_id = '{$kukarow['tunnus']}'";
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

  // Piirretään dropdown
  echo "<table><tr>";
  echo "<td><select name='asiakasvalinta' value='{$kukarow['oletus_asiakas']}' onchange='submit();'>";
  echo "<option value='' selected>".t("Oletusasiakkuus")."</option>";

  while ($multirow = mysqli_fetch_assoc($multiresult)) {
    if (empty($multirow['selaus']))  {
      echo "<option value='{$multirow['tunnus']}'>".$multirow['nimi'].', '.$row['nimitark']."</option>";
    }
    else {
      echo "<option value='{$multirow['tunnus']}'>".$multirow['selaus']."</option>";
    }
  }

  echo "</select>";
  echo "</td>";

  if (isset($row['nimi'])) {
    if (empty($row['selaus'])) {
      echo "<td class='back'>".$row['nimi'].', '.$row['nimitark']."</td>";
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
