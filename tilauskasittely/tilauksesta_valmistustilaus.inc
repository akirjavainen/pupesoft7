<?php

// jos halutaan generoida ostotilaus tämän tilauksen riveistä
// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

if (!function_exists("tilauksesta_valmistustilaus")) {
  function tilauksesta_valmistustilaus($otunnus) {
    global $yhtiorow, $kukarow, $tuoteno_array, $kpl_array, $kommentti_array, $lapsenlap_array;

    $tilauksesta_valmistustilaus = "";
    $kukakesken  = $kukarow["kesken"];
    $tehtiin_uusi_otsikko = false;

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$otunnus'";
    $result = pupe_query($query);
    $myytilrow = mysqli_fetch_array($result);

    //otetaan ensin vain lisävarusteettomat tuotteet tai lisävarusteperheiden isät
    $query = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*, jt+varattu kpl, tilausrivi.tunnus rivitunnus
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
              WHERE tilausrivi.yhtio   = '$kukarow[yhtio]'
              and tilausrivi.otunnus   = '$otunnus'
              and tilausrivi.tyyppi   != 'D'
              and tilausrivi.var       = 'R'
              and tilausrivi.perheid   = 0
              and tilausrivi.perheid2  = 0
              ORDER BY tilausrivi.tunnus";
    $lsresult = pupe_query($query);

    if (mysqli_num_rows($lsresult) > 0) {
      while ($valmlisaarivi = mysqli_fetch_array($lsresult)) {

        $viesti = trim($myytilrow["tunnusnippu"]." ".$myytilrow["toim_nimi"]);

        // Tarkistetaan onko meillä jo otsikko
        $query = "SELECT tunnus
                  from lasku
                  where yhtio       = '$kukarow[yhtio]'
                  and liitostunnus  = '999999999'
                  and tila          = 'V'
                  and tilaustyyppi  = 'W'
                  and alatila       = ''
                  and toim_nimi     = '$myytilrow[toim_nimi]'
                  and toim_nimitark = '$myytilrow[toim_nimitark]'
                  and toim_osoite   = '$myytilrow[toim_osoite]'
                  and toim_postino  = '$myytilrow[toim_postino]'
                  and toim_postitp  = '$myytilrow[toim_postitp]'
                  and toim_maa      = '$myytilrow[toim_maa]'
                  and toimaika      = '$myytilrow[toimaika]'
                  and kerayspvm     = '$myytilrow[kerayspvm]'
                  and viesti        = '$viesti'
                  and left(luontiaika,10) = left(now(),10)
                  and laatija       = '$kukarow[kuka]'";
        $jtsre = pupe_query($query);

        // ei löydy, tehdään uus otsikko
        if (mysqli_num_rows($jtsre) == 0) {

          $query = "SELECT *
                    FROM varastopaikat
                    WHERE yhtio  = '$kukarow[yhtio]'
                    and tyyppi  != 'E'
                    ORDER BY tyyppi, nimitys
                    LIMIT 1";
          $vtresult = pupe_query($query);
          $vrow = mysqli_fetch_array($vtresult);

          $jatka         = TRUE;
          $toim        = "VALMISTAVARASTOON";
          $kukarow["kesken"]  = 0;
          $clearing       = $vrow["tunnus"];
          $comments       = $viesti;
          $eilahe       = "";
          $viesti       = $viesti;
          $alatila      = "";
          $varasto       = "";
          $luokka       = "";
          $tunnusnippu    = $myytilrow["tunnusnippu"];
          $toimitustapa    = "";
          $tilausnumero     = 0;
          $nokeskupdate     = TRUE;
          $toim_nimi      = $myytilrow['toim_nimi'];
          $toim_nimitark    = $myytilrow['toim_nimitark'];
          $toim_osoite    = $myytilrow['toim_osoite'];
          $toim_postino    = $myytilrow['toim_postino'];
          $toim_postitp    = $myytilrow['toim_postitp'];
          $toim_maa      = $myytilrow['toim_maa'];

          if ($yhtiorow["kerataanko_valmistukset"] == "E" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON")) {
            $eilahe = 'CHECKED';
          }

          list($toimvv, $toimkk, $toimpp) = explode('-', $myytilrow["toimaika"]);
          list($kervv, $kerkk, $kerpp)    = explode('-', $myytilrow["kerayspvm"]);

          require "tilauskasittely/otsik_siirtolista.inc";

          $tunnus = $id;
          $tehtiin_uusi_otsikko = true;

          $tilauksesta_valmistustilaus = "<font class='message'>".t("Perustettiin valmistus %s", $kukarow["kieli"], $tunnus)."</font><br>";
        }
        else {
          // tilaus löyty, otetaan tunnus
          $jtsro = mysqli_fetch_array($jtsre);
          $tunnus = $jtsro["tunnus"];
        }

        if (!isset($laskurow)) {
          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$tunnus'";
          $lresult = pupe_query($query);
          $laskurow = mysqli_fetch_array($lresult);
        }

        // $myy_sarjatunnus  --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
        // $osto_sarjatunnus --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin

        //funktio populoi globaalit muuttujat $tuoteno_array $kpl_array $kommentti_array $lapsenlap_array
        $tuoteno_array = array($valmlisaarivi['tuoteno']);
        $kpl_array = array(
          $valmlisaarivi['tuoteno'] => $valmlisaarivi['kpl']
        );

        pupesoft_lisaa_valmisteen_rekursiiviset_reseptit();

        foreach ($tuoteno_array as $tuoteno_temp) {
          $kukarow["kesken"]   = $tunnus;
          $kpl         = $kpl_array[$tuoteno_temp];
          $tuoteno      = $tuoteno_temp;
          $toimaika      = $myytilrow["kerayspvm"];
          $kerayspvm      = $myytilrow["kerayspvm"];
          $hinta        = 0;
          $var         = "";
          $varasto      = "";
          $paikka        = "";
          $varataan_saldoa  = "EI";
          $kommentti      = $kommentti_array[$tuoteno_temp];

          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$tuoteno'";
          $tuoteres = pupe_query($query);
          $trow = mysqli_fetch_assoc($tuoteres);

          require 'lisaarivi.inc';
        }

        // Ainoastaan asiakkaalle valmistetut jälkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydessä
        $suoraan_laskutukseen_lisa = $yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S' ? 'o' : '';

        //  Päivitetään tilausrivin lisatietoihin tilausrivitunnus
        $query = "UPDATE tilausrivin_lisatiedot SET
                  tilausrivilinkki     = '$perheid_lisaarivi',
                  suoraan_laskutukseen = '{$suoraan_laskutukseen_lisa}',
                  muutospvm            = now(),
                  muuttaja             = '$kukarow[kuka]'
                  WHERE yhtio          = '$kukarow[yhtio]' and tilausrivitunnus = '$valmlisaarivi[tunnus]'";
        $updres = pupe_query($query);

        //Kopioidaan otsikon lisatiedot
        if ($tehtiin_uusi_otsikko) {
          $query = "SELECT * FROM laskun_lisatiedot WHERE otunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
          $lisatiedotres = pupe_query($query);
          $lisatiedotrow = mysqli_fetch_array($lisatiedotres);

          $fields = mysqli_field_name($lisatiedotres, 0);
          $values = "'".$lisatiedotrow[0]."'";

          for ($i=1; $i < mysqli_num_fields($lisatiedotres)-1; $i++) { // Ei monisteta tunnusta

            $fields .= ", ".mysqli_field_name($lisatiedotres, $i);

            switch (mysqli_field_name($lisatiedotres, $i)) {
            case 'otunnus':
              $values .= ", '$tunnus'";
              break;
            default:
              $values .= ", '".$lisatiedotrow[$i]."'";
            }
          }

          $kysely  = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
          $insres2 = pupe_query($kysely);

          $tehtiin_uusi_otsikko = false;
        }

        //  Päivitetään myös tilausriville oikeammat tiedot!
        $query = "SELECT *
                  FROM tilausrivin_lisatiedot
                  WHERE tilausrivitunnus='$valmlisaarivi[tunnus]' and yhtio ='$kukarow[yhtio]'";
        $trlisatiedotres = pupe_query($query);
        $trlisatiedotrow = mysqli_fetch_array($trlisatiedotres);

        $values = mysqli_field_name($trlisatiedotres, 0)." = '".$trlisatiedotrow[0]."'";

        for ($i=1; $i < mysqli_num_fields($trlisatiedotres)-1; $i++) { // Ei monisteta tunnusta

          switch (mysqli_field_name($trlisatiedotres, $i)) {
          case 'tilausrivitunnus':
            $values .= ", ".mysqli_field_name($trlisatiedotres, $i)." = '$perheid_lisaarivi'";
            break;
          case 'tilausrivilinkki':
            $values .= ", ".mysqli_field_name($trlisatiedotres, $i)." = ''";
            break;
          case 'pituus':
            $pituus = $trlisatiedotrow[$i];
            $uusiPituus = $pituus;
            $values .= ", ".mysqli_field_name($trlisatiedotres, $i)." = '1000'";
            break;
          default:
            $values .= ", ".mysqli_field_name($trlisatiedotres, $i)." = '".$trlisatiedotrow[$i]."'";
          }
        }

        $kysely  = "UPDATE tilausrivin_lisatiedot SET $values WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus = '$perheid_lisaarivi'";
        $insres2 = pupe_query($kysely);

        //  Tehdään rivistä normi JT
        $query = "UPDATE tilausrivi set var='J' where yhtio='$kukarow[yhtio]' and tunnus='$valmlisaarivi[tunnus]'";
        $updre = pupe_query($query);
      }
    }

    if ($yhtiorow['valmistuksien_kasittely'] == 'Y') {
      $valmistus_tunnukset = splittaa_valmistukset($tunnus);
    }

    $kukarow["kesken"] = $kukakesken;

    return $tilauksesta_valmistustilaus;
  }
}
