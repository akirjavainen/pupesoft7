<?php

// MUOKKAUS: Uusi tiedosto - asiakkaan nimen samankaltaisuustarkistus.
//
// Tarkoitus: varoittaa käyttäjää, jos "Uusi asiakas" (tai olemassa olevan
// asiakkaan nimenmuutos) muistuttaa jotain jo tietokannassa olevaa
// asiakas.nimi-arvoa, jotta samaa asiakasta ei vahingossa perusteta
// kahteen kertaan. EI KOSKAAN estä tallennusta - ainoastaan palauttaa
// listan mahdollisista osumista näytettäväksi varoituksena.
//
// Tämä tiedosto on kokonaan uusi eikä muokkaa mitään Pupesoftin omaa
// tiedostoa, joten se ei aiheuta yhdistämisristiriitoja Pupesoftin
// päivityksissä. Käyttö: ks. inc/asiakas.inc ("asiakas_nimi_samankaltaiset"
// -ajax-toiminto) ja inc/asiakas_samankaltaiset.js.

if (!defined("ASIAKAS_SAMANKALTAISUUS_RAJA")) {
  // 0-100: kuinka samankaltainen nimen pitää olla, jotta se näytetään
  // varoituksena. Mitä pienempi luku, sitä herkemmin varoitus näytetään.
  define("ASIAKAS_SAMANKALTAISUUS_RAJA", 72);
}

if (!function_exists("asiakas_siisti_nimi_vertailua_varten")) {
  // Siistii nimen vertailukelpoiseksi: isot kirjaimet, yleisimmät
  // yhtiömuodot pois (jotta "Esimerkki Oy" ja "Esimerkki Oy Ab" tunnistetaan
  // samaksi) ja välimerkit/ylimääräiset välilyönnit pois.
  function asiakas_siisti_nimi_vertailua_varten($nimi) {

    $nimi = mb_strtoupper(trim($nimi), "UTF-8");

    $yhtiomuodot = array(
      "OYJ", "OY AB", "AB OY", "OY", "AB", "KY", "AVOIN YHTIÖ", "AY",
      "TMI", "TOIMINIMI", "RY", "SÄÄTIÖ", "OSK", "OSUUSKUNTA",
    );

    foreach ($yhtiomuodot as $yhtiomuoto) {
      $nimi = preg_replace('/\b'.preg_quote($yhtiomuoto, '/').'\b\.?/u', '', $nimi);
    }

    // Poistetaan välimerkit ja ylimääräiset välilyönnit
    $nimi = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $nimi);
    $nimi = trim(preg_replace('/\s+/u', ' ', $nimi));

    return $nimi;
  }
}

if (!function_exists("asiakas_hae_samankaltaiset")) {
  // Palauttaa listan (max 8 kpl) asiakkaista, joiden nimi muistuttaa
  // annettua nimiehdotusta. $pl_tunnus jätetään pois vertailusta (käytössä
  // kun muokataan olemassa olevaa asiakasta, ettei asiakas "osu" itseensä).
  function asiakas_hae_samankaltaiset($yhtio, $nimi_ehdotus, $pl_tunnus = 0) {

    $osumat = array();

    $nimi_ehdotus = trim($nimi_ehdotus);

    if (mb_strlen($nimi_ehdotus, "UTF-8") < 3) {
      return $osumat;
    }

    $vertailtava = asiakas_siisti_nimi_vertailua_varten($nimi_ehdotus);

    if ($vertailtava == '') {
      return $osumat;
    }

    $pl_tunnus = (int) $pl_tunnus;

    // Haetaan kandidaatit yhtiön asiakkaista (ei poistetut) ja verrataan
    // nimiä PHP:ssa, koska halutaan sallia pieniä kirjoitusvirheitä yms.,
    // mihin pelkkä SQL LIKE ei taivu.
    $query = "SELECT tunnus, asiakasnro, nimi, nimitark, ytunnus, postitp
              FROM asiakas
              WHERE yhtio       = '$yhtio'
              AND laji         != 'P'
              AND tunnus       != $pl_tunnus
              AND trim(nimi)   != ''
              LIMIT 20000";
    $result = pupe_query($query);

    while ($row = mysqli_fetch_assoc($result)) {

      $vertailtava_kandidaatti = asiakas_siisti_nimi_vertailua_varten($row["nimi"]);

      if ($vertailtava_kandidaatti == '') {
        continue;
      }

      if ($vertailtava_kandidaatti == $vertailtava) {
        // Täysi osuma siistimisen jälkeen (esim. eri yhtiömuodon lyhenne)
        $prosentti = 100;
      }
      elseif (mb_strlen($vertailtava, "UTF-8") >= 4 and
        (mb_strpos($vertailtava_kandidaatti, $vertailtava) !== false or
        mb_strpos($vertailtava, $vertailtava_kandidaatti) !== false)) {
        // Toinen nimi sisältyy kokonaan toiseen
        $prosentti = 90;
      }
      else {
        similar_text($vertailtava, $vertailtava_kandidaatti, $prosentti);
      }

      if ($prosentti >= ASIAKAS_SAMANKALTAISUUS_RAJA) {
        $row["samankaltaisuus"] = round($prosentti);
        $osumat[] = $row;
      }
    }

    usort($osumat, function($a, $b) {
      return $b["samankaltaisuus"] <=> $a["samankaltaisuus"];
    });

    return array_slice($osumat, 0, 8);
  }
}
