<?php
/*
 * controller.katelaskenta.php
 *
 * Kontrolleri -tiedoston tehtävä on hoitaa tietojen alustaminen
 * katelaskenta toimintoa varten. Myös määrittää oikeat toimenpiteet
 * kun tietoja lähetetään katelaskenta-toiminnosta käsin.
 *
 * Ohjelmakoodissa on vielä osioita entisestä tiedostota, josta pohja
 * on otettu. Osittain pohjaa on refaktoroitu mutta isommat osiot,
 * joissa ollut enemmän työtä, on jätetty ennalleen. Osa entisestä
 * koodista siirretty katelaskenta_functions.php tiedostoon.
 *
 * Kontrolleri ohjaa kaikki tietojen tulostukset template.katelaskenta.php
 * tiedostolle. Template tiedostossa tulostetaan toistaiseksi vain
 * hakutulokset. Hakulaatikko piirretään vielä entisillä koodeilla sen
 * monimutkaisuuden takia.
 *
 * Liittyvät tiedostot:
 *
 * /controller.katelaskenta.php
 * /functions.katelaskenta.php
 * /katelaskenta_functions.php
 * /scripts.katelaskenta.js
 * /template.katelaskenta.php
 * /tietokantamuutokset-katelaskenta.sql
 *
 * Tiedossa olevat riippuvuudet muualle
 *
 * ../inc/katelaskenta_functions.php
 * ../tilauskasittely/monivalintalaatikot.inc
 *
 */
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require "../inc/parametrit.inc";

// Haetaan _funktiot.php -tiedosto, jossa katelaskennan toimintalogiikka.
require "katelaskenta_functions.php";
require "functions.katelaskenta.php";

// Tempalte array on luotu sivupohjan tietoja varten.
$template = array();

// Jos on painettu "Laske kaikki ja tallenna" -nappia, käydään lähetetyt
// tiedot läpi ja tallennetaan muutokset tietokantaan. Mikäli virheitä
// ilmenee tiedoissa, virheelliset rivit palautetaan taulukkona.
$submit_katelaskenta = (isset($_POST["submit-katelaskenta"]) ? $_POST["submit-katelaskenta"] : "");

// Jos laajennettu näkymä
$_laajennettu = false;
if($kayta_laajennettu_ver = t_avainsana("LAAJEN_KATELAS")) {
  $kayt_laaj_ver = mysqli_fetch_assoc($kayta_laajennettu_ver);
  if(mysqli_num_rows($kayta_laajennettu_ver) > 0 and $kayt_laaj_ver['selite'] == 1) {
    $_laajennettu = true;
  }
}

if (strlen($submit_katelaskenta) > 0) {
  // Tallennetaan post-tiedot omaan muuttujaan
  $post_array = $_POST;

  // Tallennetaan katemuutokset.
  $virheelliset_rivit = tallenna_valitut_katemuutokset($post_array);

  //Tiedot tallennettu onnistuneesti, ilmoitus käyttäjälle
  $template["flash_success"] = "Katemuutokset tallennettu onnistuneesti.";

  // Jos virheellisiä rivejä ilmeni, tehdään niistä ilmoitus käyttäjälle.
  $virheiden_lkm = count($virheelliset_rivit);
  if ($virheiden_lkm > 0)
    $template["flash_error"] = "Lähetetyissä tiedoissa oli {$virheiden_lkm} virhettä.";
}

/**
 * ALKUPERÄISTÄ KOODIA KOPIOIDUISTA POHJISTA, JOTA
 * EI OLE PILKOTTU.
 *
 * SEURAAVASSA MUUTAMAN SADAN RIVIN AIKANA TULOSTETAAN
 * SIVULLE TUOTEHAKULOMAKE, JOKA ON SAMA KUIN ALKUPERÄISESSÄ
 * SIVUSSA.
 */


/**
 * Seuraavat kaksi if-lausetta liittyvät poistetut valintaan tuotteita
 * hakiessa.
 */
if (!isset($poistetut)) {
  $poistetut = '';
}

if ($poistetut != "") {
  $poischeck = "CHECKED";
  $ulisa .= "&poistetut=checked";
  $poislisa = "";
}
else {
  $poislisa = " and (tuote.status not in ('P','X')
          or (SELECT sum(saldo)
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio=tuote.yhtio
              AND tuotepaikat.tuoteno=tuote.tuoteno
              AND tuotepaikat.saldo > 0) > 0) ";
  $poischeck = "";
}

/**
 * Seuraavilla riveillä valitaan järjestys hakutuloksille.
 */
$jarjestys = "tuote.tuoteno";

$lisa = "";
$ulisa = "";
$toimtuotteet = "";
$poislisa_mulsel = "";
$lisa_parametri = "";
if (!$mul_asiakashinnasto_asiakas and is_numeric($mul_asiakashinnasto_asiakas)) { $mul_asiakashinnasto_asiakas = ""; }
if ($mul_asiakashinnasto_asiakas) { $mul_asiakasryhma = $mul_asiakaspiiri = ''; }
if ($mul_asiakasryhma) { $mul_asiakaspiiri = ''; }
if ($mul_asiakaspiiri) { $mul_asiakasryhma = ''; }

/**
 * Seuraavat kaksi if-lausetta liittyvät "Näytä vain saldolliset tuotteet"
 * -valintaan tuotehaussa.
 */
if (!isset($saldotonrajaus)) {
  $saldotonrajaus = '';
}
if ($saldotonrajaus != '') {
  $saldotoncheck = "CHECKED";
  $ulisa .= "&saldotonrajaus=checked";
}
else {
  $saldotoncheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Lisätiedot"
 * -valintaan tuotehaussa.
 */
if (!isset($lisatiedot)) {
  $lisatiedot = '';
}
if ($lisatiedot != "") {
  $lisacheck = "CHECKED";
  $ulisa .= "&lisatiedot=checked";
}
else {
  $lisacheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Laske Kate"
 * -valintaan tuotehaussa.
 */
if (!isset($laskekate)) {
  $laskekate = '';
}
if ($laskekate != "") {
  $laskekatecheck = "CHECKED";
  $ulisa .= "&laskekate=checked";
}
else {
  $laskekatecheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Hintojen muutos"
 * -valintaan tuotehaussa.
 */
if (!isset($hintojen_muutos)) {
  $hintojen_muutos = '';
}
if ($hintojen_muutos != "") {
  $hintojen_muutoscheck = "CHECKED";
  $ulisa .= "&hintojen_muutos=checked";
}
else {
  $hintojen_muutoscheck = "";
}

if($hintojen_muutoscheck == "CHECKED") {
  $ulisa .= "&laskekate=checked";
  $laskekatecheck = "CHECKED";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Nimitys"-hakuehtoon.
 */
if (!isset($nimitys)) {
  $nimitys = '';
}
if (trim($nimitys) != '') {
  $nimitys = sanitize_string(trim($nimitys));
  $lisa .= " and tuote.nimitys like '%$nimitys%' ";
  $ulisa .= "&nimitys=$nimitys";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Tuotenumero"-hakuehtoon.
 */
if (!isset($tuotenumero)) {
  $tuotenumero = '';
}
if (trim($tuotenumero) != '') {
  $tuotenumero = sanitize_string(trim($tuotenumero));

  if (isset($alkukoodilla) and $alkukoodilla != "") {
    $lisa .= " and tuote.tuoteno like '$tuotenumero%' ";
  }
  else {
    $lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
  }
  $ulisa .= "&tuotenumero=$tuotenumero";
}

/**
 * Seuraavat liittyy "Asiakashinnan asiakas"-hakuehtoon.
 */
if (!isset($mul_asiakashinnasto_asiakas)) {
  $mul_asiakashinnasto_asiakas = '';
}
if (trim($mul_asiakashinnasto_asiakas) != '') {
  if($mul_asiakashinnasto_asiakas) {
    $lisa .= " and asiakashinta.asiakas = '{$mul_asiakashinnasto_asiakas}'";
  }
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Toimittajan tuotenumero"-hakuehtoon.
 */
if (!isset($toim_tuoteno)) {
  $toim_tuoteno = '';
}
if (trim($toim_tuoteno) != '') {
  $toim_tuoteno = sanitize_string(trim($toim_tuoteno));

  // Katsotaan löytyykö tuotenumero toimittajan vaihtoehtoisista tuotenumeroista
  $query = "SELECT GROUP_CONCAT(DISTINCT toim_tuoteno_tunnus SEPARATOR ',') toim_tuoteno_tunnukset
            FROM tuotteen_toimittajat_tuotenumerot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$toim_tuoteno}'";
  $vaih_tuoteno_res = pupe_query($query);
  $vaih_tuoteno_row = mysqli_fetch_assoc($vaih_tuoteno_res);

  $vaihtoehtoinen_tuoteno_lisa = $vaih_tuoteno_row['toim_tuoteno_tunnukset'] != '' ? " OR tunnus IN ('{$vaih_tuoteno_row['toim_tuoteno_tunnukset']}')" : "";

  $query = "SELECT DISTINCT tuoteno
            FROM tuotteen_toimittajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND (toim_tuoteno LIKE '%{$toim_tuoteno}%' $vaihtoehtoinen_tuoteno_lisa)
            LIMIT 500";
  $pres = pupe_query($query);

  while ($prow = mysqli_fetch_assoc($pres)) {
    $toimtuotteet .= "'" . $prow["tuoteno"] . "',";
  }

  $toimtuotteet = substr($toimtuotteet, 0, -1);

  if ($toimtuotteet != "") {
    $lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
  }

  $ulisa .= "&toim_tuoteno=$toim_tuoteno";
}

echo "<font class='head'>".t("Katelaskenta").":</font><br/><br/>";

// Seuraavaksi aletaan piirtämään tuotehakulomaketta.
echo "<form action = '' method = 'post'>";
echo "<table style='display:inline-table; padding-right:4px; padding-top:4px;' valign='top'>";
echo "<tr><th>" . t("Tuotenumero") . "</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
echo "<tr><th>" . t("Toim tuoteno") . "</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";
echo "<tr><th>" . t("Nimitys") . "</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
if ($_laajennettu) {
  echo "<tr class=\"tumma\"><th class=\"tumma\">" . t("Asiakashinnan asiakasnro") . "
<br><small style=\"font-size: 80%;text-transform: none;\">" . t("Jos täytetty:<br> valinnat \"asiakasryhmä\" ja \"asiakaspiiri\" eivät ole saatavilla.") . "</small></th><td><input type='text' size='25' name='mul_asiakashinnasto_asiakas' id='mul_asiakashinnasto_asiakas' value = '$mul_asiakashinnasto_asiakas'></td></tr>";
}
echo "<tr><th>" . t("Poistetut") . "</th>";
echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
echo "<tr><th>" . t("Lisätiedot") . "</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
echo "<tr>";
echo "<th>" . t("Näytä vain saldolliset tuotteet") . "</th>";
echo "<td><input type='checkbox' name='saldotonrajaus' $saldotoncheck></td>";
echo "</tr>";
echo "<tr>";
echo "<th>" . t("Laske kate") . "</th>";
echo "<td><input type='checkbox' name='laskekate' $laskekatecheck>
<small style=\"font-size: 80%;text-transform: none;position:relative;top:-2px;\">".t('Päällä aina, jos "Hintojen muutos" on valittu')."</small>
</td>";
echo "</tr>";
echo "<tr>";
echo "<th>" . t("Hintojen muutos") . "</th>";
echo "<td><input type='checkbox' name='hintojen_muutos' $hintojen_muutoscheck>
</td>";
echo "</tr>";
echo "</table><br/>";
echo "<br/>";

if ($_laajennettu) {
  $monivalintalaatikot = array(
    "OSASTO", 
    "TRY", 
    "TUOTEMERKKI", 
    "MALLI", 
    "MALLI/MALLITARK", 
    "<br>ASIAKASRYHMA",
    "ASIAKASPIIRI",
    "<br>DYNAAMINEN_TUOTE"
  );
} else {
  $monivalintalaatikot = array(
    "OSASTO", 
    "TRY", 
    "TUOTEMERKKI", 
    "MALLI", 
    "MALLI/MALLITARK", 
    "<br>DYNAAMINEN_TUOTE"
  );
}

$monivalintalaatikot_normaali = array();

// asiakashinnat valinnat
$piirivalinta = "asiakashinta";
$asiakasryhmavalinta = "asiakashinta";
?>
<style>
.asiakaspiirimonivalintadiv:before {
  content: "<?php echo t("tai"); ?>";
  float: left;
  margin: 5px 5px 0px 0px;
}
</style>
<script>
  $(document).ready(function () {
    $(".asiakasryhmamonivalintadiv td select, .asiakasryhmamonivalintadiv th, .asiakaspiirimonivalintadiv td select, .asiakaspiirimonivalintadiv th").addClass("tumma");
  });
</script>
<?php

/**
 * REFACTOR: Include tiedosto joudutaan hakemaan toisesta kansiosta.
 *
 * Saattaa auheuttaa ongelmia jossain vaiheessa, laatikoita muutellaan.
 */
require "../tilauskasittely/monivalintalaatikot.inc";

echo "<input type='submit' name='submit_button' id='submit_button' class='hae_btn' value = '" . t("Etsi") . "'></form>";
echo "&nbsp;<form action = '".basename(__FILE__)."' method = 'post'>
  <input type='submit' name='submit_button2' id='submit_button2' value = '" . t("Tyhjennä") . "'>
  </form>";

/**
 *  ALKUPERÄINEN KOPIOITU KOODI PÄÄTTYY.
 *
 *  SEURAAVAT RIVIT OSITTAIN KATELASKENNAN OMAA TOIMINTAA
 *  VARTEN MUUTAMAA RIVIÄ LUKUUNOTTAMATTA.
 */

/**
 * Seuraava if lähettää hakukyselyn tietokantaan.
 *
 * Lisäksi if-lohkon sisällä käsitellään tuotteiden tulostus omassa
 * if-lohkossa. Jos tuotteita ei löydy yhtään, tulostetaan siitä ilmoitus.
 */
if (!isset($submit_button)) {
  $submit_button = '';
}

if ($submit_button != '' and ($lisa != '' or $lisa_parametri != '')) {

  if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
    $lisa_parametri .= "JOIN asiakashinta on (tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno) ";
    $asiakashinta_lisays = "
    asiakashinta.hinta as asiakashinta_hinta,
    asiakashinta.piiri as asiakashinta_piiri,
    asiakashinta.asiakas as asiakashinta_asiakas,
    asiakashinta.asiakas_ryhma as asiakashinta_asiakas_ryhma, 
    asiakashinta.tunnus as asiakashinta_asiakas_tunnus, 
    asiakashinta.myyntikate as asiakashinta_asiakas_myyntikate, 
    ";
  } else {
    $asiakashinta_lisays = "";
  }

  // Hakukysely tuotehakuun.
  $query = "SELECT
            if (tuote.tuoteno = '$tuotenumero', 1, if(left(tuote.tuoteno, length('$tuotenumero')) = '$tuotenumero', 2, 3)) jarjestys,
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.myyntihinta,
            tuote.myymalahinta,
            tuote.nettohinta,
            tuote.aleryhma,
            tuote.status,
            tuote.ei_saldoa,
            tuote.yksikko,
            tuote.tunnus,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            tuote.kehahin,
            tuote.myyntikate,
            tuote.myymalakate,
            tuote.nettokate,
            $asiakashinta_lisays
            (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
            tuote.sarjanumeroseuranta,
            tuote.status
            FROM tuote use index (tuoteno, nimitys)
            $lisa_parametri
            WHERE tuote.yhtio     = '$kukarow[yhtio]'
            and tuote.tuotetyyppi NOT IN ('A', 'B')
            $kieltolisa
            $lisa
            $extra_poislisa
            $poislisa
            ORDER BY jarjestys, $jarjestys
            LIMIT 500";
  $result = pupe_query($query);

  // Täytetään template muuttuja tiedoilla, jotka halutaan
  // tulostaa template.katelaskenta.php -tiedostossa.
  $template["tuotteet"] = array();
  // Sort tietoja, joita käytetään kun tietoja lähetetään ja
  // samat hakutulokset tulevat näkyviin myös sivun uudelleen
  // latauksen jälkeen.
  $template["edsort"] = (isset($edsort) ? $edsort : "");
  $template["ojarj"] = (isset($ojarj) ? $orarj : "");
  $template["ulisa"] = (isset($ulisa) ? $ulisa : "");
  $template["variaatio_query_param"] = (isset($variaatio_query_param) ? $variaatio_query_param : "");

  // Jos tuotteita ei löydy, tulostetaan ilmoitus
  if (mysqli_num_rows($result) <= 0)
    $template["ilmoitus"] = t("Yhtään tuotetta ei löytynyt");

  // Jos tuotteita yli 500, tulostetaan ilmoitus
  if (mysqli_num_rows($result) >= 500)
    $template["ilmoitus"] = t("Löytyi yli 500 tuotetta, tarkenna hakuasi");

  // Jos aikaisemmat tarkistukset on läpäisty, eikä ilmoitusta ole
  // taulukossa, voidaan jatkaa hakurivien käsittelyä.
  if (!array_key_exists("ilmoitus", $template)) {
    $rows = array();

    if($mul_asiakasryhma) {
      while ($mrow = mysqli_fetch_assoc($result)) {
        $rows[$mrow['asiakashinta_asiakas_ryhma']][$mrow["tuoteno"]] = $mrow;
      }
    } else if($mul_asiakaspiiri) {
      while ($mrow = mysqli_fetch_assoc($result)) {
        $rows[$mrow['asiakashinta_piiri']][$mrow["tuoteno"]] = $mrow;
      }
    } else if($mul_asiakashinnasto_asiakas) {
      while ($mrow = mysqli_fetch_assoc($result)) {
        $rows[$mrow['asiakashinta_asiakas']][$mrow["tuoteno"]] = $mrow;
      }
    } else {
      while ($mrow = mysqli_fetch_assoc($result)) {
        $rows[0][$mrow["tuoteno"]] = $mrow;
      }
    }

    // Valmistelee hakutulokset templatea varten.
    $template["tuotteet"] = valmistele_hakutulokset($rows);
    
    $template["yhtio"] = $yhtiorow;

  }
  // _hakutulokset.php template käytetään tulostaulukon tulostamiseen.
  require_once 'template.katelaskenta.php';
}


/**
 * Tulostetaan sivuston footer osio.
 */
require "inc/footer.inc";
