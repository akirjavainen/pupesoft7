<?php

require "inc/parametrit.inc";

echo "  <script type='text/javascript'>
      $(document).ready(function() {
        $('.kollibutton').click(function(){
          var kollitunniste = $(this).attr('id');
          $('#kolli').val(kollitunniste);
          $('#formi').submit();
        });

        $('.poistabutton').click(function(){

          if (confirm('", t("Haluatko todella poistaa tämän sanoman?"), "')) {
            var kollitunniste = $(this).attr('id');

            if ($('#valitse').val() == 'asn') {
              $('#kolli').val(kollitunniste);
            }
            else {
              $('#lasku').val(kollitunniste);
            }

            $('#tee').val('poista_sanoma');
            $('#formi').submit();
          }
        });

        $('.ostolaskubutton').click(function(){
          var lasku = $(this).attr('id');
          $('#lasku').val(lasku);
          $('#formi').submit();
        });

        $('.toimittajabutton').click(function(){
          $('#tee').val('vaihdatoimittaja');

          var valitse = $('form#formi').find('#valitse').val();

          if (valitse == 'asn') {
            var asn = $(this).attr('id');
            $('#formi').attr('action', '?valitse=asn&asn_numerot='+asn+'&lopetus={$PHP_SELF}////tee=').submit();
          }
          else {
            var tilausnumero = $(this).attr('id');
            $('#formi').attr('action', '?valitse=ostolasku&tilausnumero='+tilausnumero+'&lopetus={$PHP_SELF}////tee=').submit();
          }
        });

        $('.etsibutton').click(function(){
          var rivitunniste = $(this).attr('id');
          $('#asn_rivi').val(rivitunniste);
          $('#kolliformi').submit();
        });

        $('.etsibutton_osto').click(function(){
          var rivitunniste = $(this).attr('id');
          $('#lasku').val(rivitunniste);
          $('#kolliformi').submit();
        });

        $('.erobutton_osto').click(function(){
          var rivitunniste = $(this).attr('id');
          $('#lasku').val(rivitunniste);

          $('#tee').val('erolistalle');

          var lopetus = $('#lopetus').val();
          lopetukset = lopetus.split('/SPLIT/');

          $('#lopetus').val(lopetukset[0]);

          $('#kolliformi').submit();
        });

        $('.vahvistabutton').click(function(){
          $('#tee').val('vahvistakolli');
          $('#kolliformi').attr('action', '?').submit();
        });

        $('.vahvistavakisinbutton').click(function(){
          $('#tee').val('vahvistavakisinkolli');
          $('#kolliformi').attr('action', '?').submit();
        });

        $('.poistakohdistus').click(function(){
          $('#tee').val('poistakohdistus');
          $('#kolliformi').attr('action', '?').submit();
        });

        $('#kohdista_tilausrivi_formi').submit(function(){
          var kohdistettu = false;

          $('.tunnukset').each(function(){
            if ($(this).is(':checked')) {
              kohdistettu = true;
            }
          });

          if (kohdistettu) {
            var lopetus = $('#lopetus').val();
            lopetukset = lopetus.split('/SPLIT/');

            $('#lopetus').val(lopetukset[0]);
          }
        });
      });
    </script>";

echo "<font class='head'>", t("Ostolasku / ASN-sanomien tarkastelu"), "</font><hr><br />";

if (!isset($tee)) $tee = '';
if (!isset($valitse)) $valitse = '';
if (!isset($asn_rivi)) $asn_rivi = '';
if (!isset($toimipaikka)) $toimipaikka = '';
if (isset($muut_siirrettavat) and trim($muut_siirrettavat) != "") list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl, $valitse, $kolli, $toimittajanumero, $asn_numero, $toimipaikka) = explode("!¡!", $muut_siirrettavat);

if ($tee == 'poista_sanoma') {

  if ($valitse == 'asn') {
    $sanomatunniste = $kolli;
  }
  else {
    $sanomatunniste = $lasku;
  }

  if (trim($sanomatunniste) != '') {

    if ($valitse == 'maventa') {
      $valitse_lisa = 'mav';
    }
    elseif ($valitse == 'finvoice') {
      $valitse_lisa = 'fin';
    }
    elseif ($valitse == 'asn') {
      $valitse_lisa = 'asn';
    }
    else {
      $valitse_lisa = 'tec';
    }

    $query = "DELETE FROM asn_sanomat
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND laji       = '{$valitse_lisa}'
              AND asn_numero = '{$sanomatunniste}'";
    $res = pupe_query($query);

    echo "<br /><font class='message'>", t("Poistettiin sanoma"), " {$sanomatunniste}</font><br /><br />";
  }

  $tee = '';
}

if ($tee == 'erolistalle') {

  if (isset($lasku) and mb_strpos($lasku, '##') !== false) {
    list($lasku, $tuoteno, $tilaajanrivinro, $toimittaja, $kpl, $rivitunnus, $tilausnumero, $toim_tuoteno) = explode('##', $lasku);
  }

  if (isset($rivitunnus) and trim($rivitunnus) != '') {

    $rivitunnus = (int) $rivitunnus;

    $query = "UPDATE asn_sanomat SET
              status      = 'E'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$rivitunnus}'";
    $res = pupe_query($query);
  }

  $tee = 'nayta';
}

if ($tee == 'vaihdatoimittaja') {

  $tila = '';

  if (isset($nimi) and trim($nimi) != '') {

    //tehdään asiakas- ja toimittajahaku yhteensopivuus
    $ytunnus = $nimi;

    $lause = "<font class='head'>".t("Valitse toimittaja").":</font><hr><br>";
    require "inc/kevyt_toimittajahaku.inc";

    if ($ytunnus == '' and $monta > 1) {
      //Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
      $tila = 'monta';
    }
    elseif ($ytunnus == '' and $monta < 1) {
      //yhtään asiakasta ei löytynyt, näytetään otsikko
      $tila = '';
    }
    else {
      //oikea asiakas on löytynyt
      $tunnus = $toimittajaid;
      $tila = 'ok';
    }
  }

  if (isset($toimittajaid) and trim($toimittajaid) != '') {

    $toimittajaid = (int) $toimittajaid;

    $query = "SELECT toimittajanro FROM toimi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$toimittajaid}'";
    $result = pupe_query($query);
    $row = mysqli_fetch_assoc($result);

    if (!empty($row['toimittajanro'])) {
      if ($valitse == 'asn') {
        //n. rivi 187 "&asn_numerot={$asn_numerot}&valitse={$valitse}";
        $asn_numerot = $asn_numerot;

        $query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND asn_numero IN ({$asn_numerot})";
        $res = pupe_query($query);
      }
      else {
        //n. rivi 190 "&tilausnumero={$tilausnumero}&valitse={$valitse}";
        $tilausnumero = (int) $tilausnumero;

        $query = "UPDATE asn_sanomat SET toimittajanumero = '{$row['toimittajanro']}' WHERE yhtio = '{$kukarow['yhtio']}' AND tilausnumero = '{$tilausnumero}'";
        $res = pupe_query($query);
      }

      $tee = '';
      $tila = 'ok';
    }
    else {
      //toimittajalla ei ole toimittaja numeroa, sitä ei voida laittaa asn_sanomien toimittajaksi
      echo "<font class='error'>".t("Toimittajalta puuttuu toimittajanumero").". ".t("Sitä ei voida laittaa asn_sanomien toimittajaksi")."</font>";
      echo "<br/>";
      $tee = 'vaihdatoimittaja';
      $tila = '';
    }
  }

  if ($tila == '') {

    if ($valitse == 'asn') {
      $action = "&asn_numerot={$asn_numerot}&valitse={$valitse}";
    }
    else {
      $action = "&tilausnumero={$tilausnumero}&valitse={$valitse}";
    }

    echo "<form method='post' action='?tee={$tee}{$action}'>";
    echo "<table>";
    echo "<tr><th>", t("Etsi toimittajaa"), " (", t("nimi"), " / ", t("ytunnus"), ")</th><td><input type='text' name='nimi' value='' />&nbsp;<input type='submit' class='hae_btn' value = '".t("Etsi")."' /></td></tr>";
    echo "</table>";
    echo "</form>";
  }
}

if ($tee == 'vahvistakolli' or $tee == 'vahvistavakisinkolli') {

  $automaattikohdistukseen = true;

  if ($valitse != 'asn' and $tee == 'vahvistavakisinkolli') {

    // Kun vahvistetaan katsotaan onko lasku liitetty jo saapumiseen
    // Jos on, niin merkataan asn_sanoma status x eikä mennä automaattikohdistukseen
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND tila     IN ('H','Y','M','P','Q')
              AND vienti   in ('B', 'C', 'E', 'F', 'H', 'I')
              AND laskunro = '".sanitize_string($lasku)."'
              ORDER BY tapvm desc";
    $ostolasku_chk_res = pupe_query($query);

    if (mysqli_num_rows($ostolasku_chk_res) > 0) {
      $ostolasku_chk_row = mysqli_fetch_assoc($ostolasku_chk_res);

      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tila        = 'K'
                AND vanhatunnus = '{$ostolasku_chk_row['tunnus']}'";
      $liitosotsikko_chk_res = pupe_query($query);

      if (mysqli_num_rows($liitosotsikko_chk_res) > 0) {

        $query = "UPDATE asn_sanomat SET
                  status         = 'X',
                  tilausrivi     = NULL
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND asn_numero = '{$lasku}'
                  AND status     not in ('E', 'D')";
        $upd_res = pupe_query($query);

        $automaattikohdistukseen = false;

        echo "<font class='message'>", t("Lasku oli jo liitetty saapumiseen"), ". ", t("Päivitetään lasku käsitellyksi"), ".</font><br /><br />";
      }
    }
  }

  if ($automaattikohdistukseen) {

    if ($valitse == 'asn') {
      $kolli = sanitize_string($kolli);
      $wherelisa = "AND paketintunniste != '' AND paketintunniste = '{$kolli}'";
    }
    else {
      $wherelisa = "AND asn_numero = '".sanitize_string($lasku)."'";
    }

    $paketin_rivit     = array();
    $paketin_tunnukset   = array();
    $rtuoteno      = array();
    $laskuttajan_toimittajanumero = "";
    $lasku_manuaalisesti_check = 0;

    $tilausrivilisa = $tee == 'vahvistavakisinkolli' ? "" : "AND tilausrivi IS NOT NULL";

    $query = "SELECT *
              FROM asn_sanomat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND status  not in ('E', 'D')
              {$tilausrivilisa}
              {$wherelisa}
              ORDER BY asn_sanomat.tuoteno ASC";
    $kollires = pupe_query($query);

    $i = $x = $y = 0;
    $keikoilla  = array();
    $ostoilla  = array();
    $tullaan_virhetarkistuksesta = $tee == 'vahvistavakisinkolli' ? false : true;

    while ($kollirow = mysqli_fetch_assoc($kollires)) {

      if ($valitse == 'asn') {

        $toimittaja = $kollirow['toimittajanumero'];
        $asn_numero = $kollirow['asn_numero'];
        $paketin_tunnukset[] = $kollirow['tunnus'];

        // Otetaan ASN-sanomalta tilausrivi(e)n tunnus ja laitetaan $paketin_rivit muuttujaan
        if (mb_strpos($kollirow['tilausrivi'], ",") !== false) {
          foreach (explode(",", $kollirow['tilausrivi']) as $tunnus) {
            $tunnus = trim($tunnus);

            if ($tunnus != '') $paketin_rivit[] = $tunnus;
          }
        }
        else {
          if (!is_null($kollirow['tilausrivi'])) $paketin_rivit[] = $kollirow['tilausrivi'];
        }

        // Haetaan tuotteen lapset jotka ovat runkoveloituksia
        $query = "SELECT GROUP_CONCAT(tuoteperhe.tuoteno) lapset
                  FROM tuoteperhe
                  WHERE yhtio       = '{$kukarow['yhtio']}'
                  AND tyyppi        = 'P'
                  AND isatuoteno    = '{$kollirow['tuoteno']}'
                  AND ohita_kerays != ''";
        $result = pupe_query($query);
        $lapset = mysqli_fetch_assoc($result);

        // Lapsia löytyi, tämä on isätuote
        if (!is_null($lapset["lapset"])) {

          // Haetaan tilausnumerot joilla tämä tuote on
          $query = "SELECT group_concat(otunnus) tilaukset
                    FROM tilausrivi
                    WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                    AND tilausrivi.tunnus  IN ({$kollirow['tilausrivi']})";
          $result = pupe_query($query);
          $tilaukset = mysqli_fetch_assoc($result);

          foreach (explode(",", $lapset['lapset']) as $lapsi_tuoteno) {

            if ($tilaukset['tilaukset'] != '') {
              // Haetaan tämän isätuotteen lapsituotteiden tunnukset
              $query = "SELECT tunnus, tuoteno
                        FROM tilausrivi
                        WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                        AND tilausrivi.otunnus IN ({$tilaukset['tilaukset']})
                        AND tilausrivi.tuoteno = '{$lapsi_tuoteno}'
                        AND (tilausrivi.perheid IN ('{$kollirow['tilausrivi']}') OR tilausrivi.perheid = 0)";
              $result = pupe_query($query);
            }

            if ($tilaukset['tilaukset'] == '' or mysqli_num_rows($result) == 0) {

              // otetaan ensimmäisen isätuotteen tilausrivin tiedot
              $query = "SELECT *
                        FROM tilausrivi
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  IN ({$kollirow['tilausrivi']})";
              $isa_chk_res = pupe_query($query);
              $isa_chk_row = mysqli_fetch_assoc($isa_chk_res);

              $query = "SELECT tuoteperhe.isatuoteno,
                        tuoteperhe.tuoteno,
                        tuote.tuoteno,
                        tuote.try,
                        tuote.osasto,
                        tuote.nimitys,
                        tuote.yksikko,
                        tuote.myyntihinta,
                        tuotepaikat.hyllyalue,
                        tuotepaikat.hyllynro,
                        tuotepaikat.hyllytaso,
                        tuotepaikat.hyllyvali
                        FROM tuote
                        JOIN tuoteperhe ON (tuoteperhe.yhtio = tuote.yhtio AND tuoteperhe.isatuoteno = '{$kollirow['tuoteno']}' AND tuoteperhe.tyyppi IN ('P','')  AND tuoteperhe.ohita_kerays != '')
                        JOIn tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.oletus !='')
                        WHERE tuote.yhtio  = '{$kukarow['yhtio']}'
                        AND tuote.status  != 'P'
                        AND tuote.tuoteno  = '{$lapsi_tuoteno}'";
              $lapsiresult = pupe_query($query);

              while ($lapsitieto = mysqli_fetch_assoc($lapsiresult)) {
                // hae tuotteen ostohinta
                $laskuselect = "SELECT *
                                FROM lasku
                                WHERE yhtio = '{$kukarow['yhtio']}'
                                AND tunnus  = '{$isa_chk_row['otunnus']}'";
                $laskures  = pupe_query($laskuselect);
                $laskurow  = mysqli_fetch_assoc($laskures);

                list($hinta, , $ale, ) = alehinta_osto($laskurow, $lapsitieto, $isa_chk_row["tilkpl"]);

                $lisainsert = "INSERT INTO tilausrivi SET
                               yhtio       = '{$kukarow['yhtio']}',
                               tyyppi      = 'O',
                               toimaika    = '{$isa_chk_row['toimaika']}',
                               kerayspvm   = '{$isa_chk_row['kerayspvm']}',
                               otunnus     = '{$isa_chk_row['otunnus']}',
                               tuoteno     = '{$lapsitieto['tuoteno']}',
                               try         = '{$lapsitieto['try']}',
                               osasto      = '{$lapsitieto['osasto']}',
                               nimitys     = '{$lapsitieto['nimitys']}',
                               yksikko     = '{$lapsitieto['yksikko']}',
                               tilkpl      = '{$isa_chk_row['tilkpl']}',
                               varattu     = '{$isa_chk_row['varattu']}',
                               ale1        = '{$ale['ale1']}',
                               ale2        = '{$ale['ale2']}',
                               ale3        = '{$ale['ale3']}',
                               kpl         = '{$isa_chk_row['kpl']}',
                               hinta       = '{$hinta}',
                               laatija     = 'lapset',
                               kommentti   = 'ASN-sanomalta: TL:{$lapsitieto['tunnus']} tuotteelle: {$lapsitieto['isatuoteno']} lisätään lapsituote: {$lapsitieto['tuoteno']}',
                               laadittu    =  now(),
                               hyllyalue   = '{$lapsitieto['hyllyalue']}',
                               hyllynro    = '{$lapsitieto['hyllynro']}',
                               hyllytaso   = '{$lapsitieto['hyllytaso']}',
                               hyllyvali   = '{$lapsitieto['hyllyvali']}',
                               uusiotunnus = '{$isa_chk_row['uusiotunnus']}',
                               perheid     = '{$isa_chk_row['tunnus']}'";

                // MUOKKAUS: mysqli_insert_id():
                $inskres = pupe_query($lisainsert, $GLOBALS["link"]);
                $id = mysqli_insert_id($GLOBALS["link"]);

                $paketin_rivit[] = $id;

                // päivitetään isä
                $updateisa = "UPDATE tilausrivi SET
                              perheid     = tunnus
                              WHERE yhtio = '{$kukarow['yhtio']}'
                              AND tunnus  = '{$isa_chk_row['tunnus']}'";
                $updateres = pupe_query($updateisa);

                $query = "SELECT *
                          FROM asn_sanomat
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND laji    = 'asn'
                          AND MATCH (tilausrivi) AGAINST ('{$isa_chk_row['tunnus']}' IN BOOLEAN MODE)";
                $info_res = pupe_query($query);
                $info_row = mysqli_fetch_assoc($info_res);

                // Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
                $fields = "yhtio";
                $values = "'{$kukarow['yhtio']}'";

                // Ei monisteta tunnusta
                for ($ii = 1; $ii < mysqli_num_fields($info_res) - 1; $ii++) {

                  $fieldname = mysqli_field_name($info_res, $ii);

                  $fields .= ", ".$fieldname;

                  switch ($fieldname) {
                  case 'tilausrivi':
                    $values .= ", '{$id}'";
                    break;
                  case 'tuoteno':
                  case 'toim_tuoteno':
                  case 'toim_tuoteno2':
                    $values .= ", '{$lapsitieto['tuoteno']}'";
                    break;
                  case 'hinta':
                    $values .= ", '{$hinta}'";
                    break;
                  default:
                    $values .= ", '".$info_row[$fieldname]."'";
                  }
                }

                $kysely  = "INSERT INTO asn_sanomat ({$fields}) VALUES ({$values})";
		
		// MUOKKAUS: mysqli_insert_id():
                $uusires = pupe_query($kysely, $GLOBALS["link"]);
                $asn_lapsi_uusi_id = mysqli_insert_id($GLOBALS["link"]);

                $paketin_tunnukset[] = $asn_lapsi_uusi_id;
              }

              $paketin_rivit[] = $isa_chk_row["tunnus"];
            }
            else {
              while ($rivi = mysqli_fetch_assoc($result)) {
                $paketin_rivit[] = $rivi["tunnus"];
              }
            }
          }
        }

        $sscc_paketti_tunnus = $kollirow["paketintunniste"];
      }
      else {

        if (!$tullaan_virhetarkistuksesta) {
          $rtuoteno[$i]['tuoteno']    = $kollirow['toim_tuoteno'];
        }
        else {
          $rtuoteno[$i]['tuoteno']    = trim($kollirow['tuoteno']) != "" ? $kollirow['tuoteno'] : $kollirow['toim_tuoteno'];
        }

        $rtuoteno[$i]['ostotilausnro']     = $kollirow['tilausnumero'];
        $rtuoteno[$i]['tilaajanrivinro']   = $kollirow['tilausrivinpositio'];
        $rtuoteno[$i]['kpl']         = $kollirow['kappalemaara'];
        $rtuoteno[$i]['hinta']         = $kollirow['hinta'];
        $rtuoteno[$i]['ale1']         = $kollirow['lasku_ale1'];
        $rtuoteno[$i]['ale2']         = $kollirow['lasku_ale2'];
        $rtuoteno[$i]['ale3']         = $kollirow['lasku_ale3'];
        $rtuoteno[$i]['lisakulu']       = $kollirow['lisakulu'];
        $rtuoteno[$i]['kulu']         = $kollirow['kulu'];
        $rtuoteno[$i]['kauttalaskutus']    = "";
        $rtuoteno[$i]['insert_id']      = $kollirow['tunnus'];
        $rtuoteno[$i]['status']        = $kollirow['status'];

        if ($tullaan_virhetarkistuksesta) {
          $query = "SELECT *
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  IN ({$kollirow['tilausrivi']})";
          $tilausrivires = pupe_query($query);

          while ($tilausrivirow = mysqli_fetch_assoc($tilausrivires)) {

            if ($tilausrivirow['uusiotunnus'] == 0) {
              // löytyi, ei ole keikalla
              $ostoilla[$y]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
              $ostoilla[$y]["hinta"] = $rtuoteno[$i]["hinta"];
              $ostoilla[$y]["kpl"] = $rtuoteno[$i]["kpl"];
              $ostoilla[$y]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
              $ostoilla[$y]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
              $ostoilla[$y]["insert_id"] = $rtuoteno[$i]["insert_id"];
              $ostoilla[$y]["lisakulu"] = $rtuoteno[$i]["lisakulu"];
              $ostoilla[$y]["kulu"] = $rtuoteno[$i]["kulu"];
              $ostoilla[$y]["ale1"] = $rtuoteno[$i]["ale1"];
              $ostoilla[$y]["ale2"] = $rtuoteno[$i]["ale2"];
              $ostoilla[$y]["ale3"] = $rtuoteno[$i]["ale3"];
              $ostoilla[$y]['tuoteno'] = $tilausrivirow['tuoteno'];

              $y++;
            }
            else {
              // löytyi, on jo keikalla
              $keikoilla[$x]["tunnus"] = $tilausrivirow['tunnus'];
              $keikoilla[$x]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
              $keikoilla[$x]["hinta"] = $rtuoteno[$i]["hinta"];
              $keikoilla[$x]["kpl"] = $rtuoteno[$i]["kpl"];
              $keikoilla[$x]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
              $keikoilla[$x]["insert_id"] = $rtuoteno[$i]["insert_id"];
              $keikoilla[$x]["lisakulu"] = $rtuoteno[$i]["lisakulu"];
              $keikoilla[$x]["kulu"] = $rtuoteno[$i]["kulu"];
              $keikoilla[$x]["ale1"] = $rtuoteno[$i]["ale1"];
              $keikoilla[$x]["ale2"] = $rtuoteno[$i]["ale2"];
              $keikoilla[$x]["ale3"] = $rtuoteno[$i]["ale3"];
              $keikoilla[$x]['tuoteno'] = $tilausrivirow['tuoteno'];

              // jos tilausrivin saapumisella onkin jo vaihto-omaisuuslasku, ei edetä ja nollataan asn_sanomat.tilausrivi
              $query = "SELECT saapuminen.tunnus
                        FROM lasku AS saapuminen
                        WHERE saapuminen.yhtio = '{$kukarow['yhtio']}'
                        AND saapuminen.tunnus  = '{$tilausrivirow['uusiotunnus']}'
                        AND saapuminen.tapvm   = '0000-00-00'
                        AND saapuminen.mapvm   = '0000-00-00'";
              $saapres = pupe_query($query);

              // Jos ei löydy laskua, irrotetaan kohdistus
              if (mysqli_num_rows($saapres) == 0) {
                $lasku_manuaalisesti_check = 1;

                $query = "UPDATE asn_sanomat SET
                          tilausrivi  = NULL
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tunnus  = '{$kollirow['tunnus']}'";
                $upd_res = pupe_query($query);
              }

              $x++;
            }
          }
        }

        $i++;

        $laskuttajan_toimittajanumero = $kollirow['toimittajanumero'];
      }
    }

    if ($valitse != 'asn' and count($rtuoteno) > 0 and $laskuttajan_toimittajanumero != "" and $lasku_manuaalisesti_check == 0) {

      if ($tee == 'vahvistavakisinkolli' and !$tullaan_virhetarkistuksesta) {

        if ($valitse == 'maventa') {
          $lajilisa = 'mav';
        }
        elseif ($valitse == 'finvoice') {
          $lajilisa = 'fin';
        }
        else {
          $lajilisa = 'tec';
        }

        $query = "UPDATE asn_sanomat SET
                  status         = '',
                  tilausrivi     = NULL
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND laji       = '{$lajilisa}'
                  AND asn_numero = '{$lasku}'
                  AND status     not in ('E', 'D')";
        $upd_res = pupe_query($query);
      }

      $query = "SELECT tunnus, yhtio_toimipaikka
                FROM lasku
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND laskunro = '{$lasku}'
                AND tila     IN ('H','Y','M','P','Q')
                AND vienti   in ('B', 'C', 'E', 'F', 'H', 'I')";
      $tunnus_fetch_res = pupe_query($query);
      $tunnus_fetch_row = mysqli_fetch_assoc($tunnus_fetch_res);

      $tunnus = $tunnus_fetch_row['tunnus'];
      $_yhtion_toimipaikka = $tunnus_fetch_row['yhtio_toimipaikka'];

      $query = "SELECT *
                FROM toimi
                WHERE yhtio        = '{$kukarow['yhtio']}'
                and toimittajanro  = '{$laskuttajan_toimittajanumero}'
                and tyyppi        != 'P'";
      $result = pupe_query($query);

      if (mysqli_num_rows($result) == 1) {
        $trow = mysqli_fetch_assoc($result);

        require 'inc/verkkolasku-in-luo-keikkafile.inc';

        if ($virheet == 0) {

          if (isset($keikantunnus) and $keikantunnus != '' and $keikantunnus != 0) {

            // Haetaan saapumisen numero
            $query = "SELECT laskunro
                      FROM lasku
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$keikantunnus}'";
            $saapumisen_nro_chk_res = pupe_query($query);
            $saapumisen_nro_chk_row = mysqli_fetch_assoc($saapumisen_nro_chk_res);

            echo "<br /><font class='message'>", t("Lasku liitettiin saapumiseen"), " {$saapumisen_nro_chk_row['laskunro']}</font><br /><br />";
          }

          $query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND status not in ('E','D') {$wherelisa}";
          $kollires = pupe_query($query);

          while ($kollirow = mysqli_fetch_assoc($kollires)) {
            $query = "UPDATE asn_sanomat SET
                      status      = 'X'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$kollirow['tunnus']}'";
            $updateres = pupe_query($query);
          }

        }

        // verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
      }
    }
    elseif ($valitse != 'asn' and count($rtuoteno) > 0 and $laskuttajan_toimittajanumero != "" and $lasku_manuaalisesti_check != 0) {
      echo "<font class='message'>", t("Laskun rivejä oli liitetty saapumiseen jossa oli jo vaihto-omaisuus lasku"), ". ", t("Poistetaan tämmöisten rivien liitos"), ". ", t("Käsittele lasku"), " $lasku ", t("uudestaan"), ".</font><br /><br />";
    }

    if ($valitse == 'asn' and count($paketin_rivit) > 0) {

      $query = "SELECT GROUP_CONCAT(tuoteno) AS tuotenumerot FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN (".implode(",", $paketin_rivit).")";
      $tuotenores = pupe_query($query);
      $tuotenorow = mysqli_fetch_assoc($tuotenores);

      $paketin_tuotteet = explode(",", $tuotenorow['tuotenumerot']);

      require 'inc/asn_kohdistus.inc';

      asn_kohdista_suuntalava($toimittaja, $asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $sscc_paketti_tunnus);

    }
  }

  $tee = '';
}

if ($tee == 'poistakohdistus') {

  $kolli = sanitize_string($kolli);
  $wherelisa = "AND paketintunniste = '{$kolli}'";

  $query = "SELECT * from asn_sanomat where yhtio = '{$kukarow['yhtio']}' and status != 'D' {$wherelisa}";
  $kollires = pupe_query($query);
  $rivi = mysqli_fetch_assoc($kollires);

  // poistetaan STATUS-täppä
  $query = "UPDATE asn_sanomat SET status = '', tilausrivi= NULL WHERE yhtio = '{$kukarow['yhtio']}' {$wherelisa}";
  $kollires = pupe_query($query);

  $tee = '';
}

if ($tee == 'uusirivi') {

  $var = 'O';

  if (trim($tilausnro) == '' or $tilausnro == 0) {

    $asn_rivi = (int) $asn_rivi;

    $query = "SELECT tilausnumero FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
    $result = pupe_query($query);
    $row = mysqli_fetch_assoc($result);

    $tilausnro = $row['tilausnumero'];
  }

  $tilausnro = (int) $tilausnro;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$tilausnro}'";
  $res = pupe_query($query);

  $laskuloytyi = 0;

  if (mysqli_num_rows($res) > 0) {
    $laskuloytyi = 1;
  }
  else {
    $query = "SELECT tunnus
              FROM toimi
              WHERE yhtio        = '{$kukarow['yhtio']}'
              AND toimittajanro  = '{$toimittaja}'
              AND tyyppi        != 'P'
              ORDER BY tunnus DESC
              LIMIT 1";
    $toimires = pupe_query($query);

    if (mysqli_num_rows($toimires) == 1) {
      $toimirow = mysqli_fetch_assoc($toimires);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio        = '{$kukarow['yhtio']}'
                AND tila           = 'O'
                AND alatila        = 'A'
                AND liitostunnus   = '{$toimirow['tunnus']}'
                AND vanhatunnus    = '{$toimipaikka}'
                AND suoraveloitus != 'S'
                ORDER BY tunnus
                DESC LIMIT 1";
      $res = pupe_query($query);

      if (mysqli_num_rows($res) == 1) {
        $laskuloytyi = 1;
      }
    }
  }

  if ($laskuloytyi == 1) {
    $laskurow = mysqli_fetch_assoc($res);
    $tilausnro = $laskurow['tunnus'];

    if ($laskurow['alatila'] == 'X') {
      $error = t("Tilaus").' '.$tilausnro.' '.t("ei ole sopiva")."!";
      $tee = 'etsi';
    }
    else {
      $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$tuoteno}'";
      $result = pupe_query($query);
      $trow = mysqli_fetch_assoc($result);

      list($hinta, , , ) = alehinta_osto($laskurow, $trow, $kpl);

      //pidetään kaikki muuttujat tallessa
      $muut_siirrettavat = $asn_rivi."!¡!".$toimittaja."!¡!".$tilausnro."!¡!".$tuoteno."!¡!".$tilaajanrivinro."!¡!".$kpl."!¡!".$valitse."!¡!".$kolli."!¡!".$toimittajanumero."!¡!".$asn_numero."!¡!".$toimipaikka;

      $rivinotunnus = $tilausnro;
      $toimaika = date('Y')."-".date('m')."-".date('d');

      echo t("Tee uusi rivi").":<br>";

      require 'tilauskasittely/syotarivi_ostotilaus.inc';
      require 'inc/footer.inc';
      exit;
    }
  }
  else {
    $error = t("Ostotilausta").' '.$tilausnro.' '.t("ei löydy")."!";
    $tee = 'etsi';
  }
}

//poistetaan rivi, näytetään lista
if ($tee == 'TI' and isset($tyhjenna)) {
  $tee = 'etsi';
}

//tarkastetaan tilausrivi
if ($tee == 'TI') {
  // Parametreja joita tarkistarivi tarvitsee
  $laskurow["tila"]   = "O";
  $toim_tarkistus   = "EI";
  $prow["tuoteno"]   = $tuoteno;
  $prow["var"]    = $rivinvar;
  $kukarow["kesken"]  = $rivinotunnus;

  $kpl   = str_replace(',', '.', $kpl);
  $hinta   = str_replace(',', '.', $hinta);

  for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
    ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
  }

  if (checkdate($toimkka, $toimppa, $toimvva)) {
    $toimaika = $toimvva."-".$toimkka."-".$toimppa;
  }

  if ($hinta == "") {

    $toimittaja = (int) $toimittaja;

    $query = "SELECT tunnus
              FROM toimi
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND toimittajanro = '{$toimittaja}'";
    $toimires = pupe_query($query);
    $toimirow = mysqli_fetch_assoc($toimires);

    $laskurow['liitostunnus'] = $toimirow['tunnus'];

    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE tuoteno    = '{$prow['tuoteno']}'
              AND yhtio        = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$laskurow['liitostunnus']}'";
    $rarres1 = pupe_query($query);
    $hinrow1 = mysqli_fetch_assoc($rarres1);

    $prow["hinta"] = $hinrow1["ostohinta"];
  }
  else {
    $prow["hinta"] = $hinta;
  }

  $multi = "";
  require "inc/tuotehaku.inc";

  $prow["tuoteno"]      = $tuoteno;
  $oikeusostohintapaiv  = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat", "check");

  require 'tilauskasittely/tarkistarivi_ostotilaus.inc';

  //näytetään virhe ja annetaan mahis korjata se
  if (trim($varaosavirhe) != '' or trim($tuoteno) == "") {

    //rivien splittausvaihtoehtot näkyviin
    $automatiikka = 'ON';

    //pidetään kaikki muuttujat tallessa
    $muut_siirrettavat = $asn_rivi."!¡!".$toimittaja."!¡!".$tilausnro."!¡!".$tuoteno."!¡!".$tilaajanrivinro."!¡!".$kpl."!¡!".$valitse."!¡!".$kolli."!¡!".$toimittajanumero."!¡!".$asn_numero."!¡!".$toimipaikka;

    $rivinotunnus = $tilausnro;

    echo t("Muuta riviä"), ":<br>";
    require 'tilauskasittely/syotarivi_ostotilaus.inc';
    require 'inc/footer.inc';
    exit;
  }
}

//rivi on tarkistettu ja se lisataan tietokantaan
if ((isset($varaosavirhe) and trim($varaosavirhe) == '') and ($tee == "TI") and trim($tuoteno) != '') {

  $laskurow["tila"] = "O";
  $kukarow["kesken"] = $rivinotunnus;

  $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivinotunnus}'";
  $laskures = pupe_query($query);
  $laskurow = mysqli_fetch_assoc($laskures);

  if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
    $tuoteno_array[] = $tuoteno;
  }

  //Käyttäjän syöttämä hinta ja ale ja netto, pitää säilöä jotta tuotehaussakin voidaan syöttää nämä
  for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
    ${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
  }

  $kayttajan_hinta  = $hinta;
  $kayttajan_netto   = $netto;
  $kayttajan_var    = $var;
  $kayttajan_kpl    = $kpl;
  $kayttajan_alv    = $alv;

  foreach ($tuoteno_array as $tuoteno) {

    $query  = "SELECT *
               FROM tuote
               WHERE tuoteno = '{$tuoteno}'
               and yhtio     = '{$kukarow['yhtio']}'
               and ei_saldoa = ''";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) > 0) {
      //Tuote löytyi
      $trow = mysqli_fetch_array($result);
    }
    else {
      //Tuotetta ei löydy, arvataan muutamia muuttujia
      $trow["alv"] = $laskurow["alv"];
    }

    if (checkdate($toimkka, $toimppa, $toimvva)) {
      $toimaika = $toimvva."-".$toimkka."-".$toimppa;
    }
    if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
      $kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
    }
    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }
    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    $varasto = $laskurow["varasto"];

    //Tehdään muuttujaswitchit
    if (is_array($hinta_array)) {
      $hinta = $hinta_array[$tuoteno];
    }
    else {
      $hinta = $kayttajan_hinta;
    }

    for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
      if (is_array(${'ale_array'.$alepostfix})) {
        ${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
      }
      else {
        ${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
      }
    }

    if (is_array($netto_array)) {
      $netto = $netto_array[$tuoteno];
    }
    else {
      $netto = $kayttajan_netto;
    }

    if (is_array($var_array)) {
      $var = $var_array[$tuoteno];
    }
    else {
      $var = $kayttajan_var;
    }

    if (is_array($kpl_array)) {
      $kpl = $kpl_array[$tuoteno];
    }
    else {
      $kpl = $kayttajan_kpl;
    }

    if (is_array($alv_array)) {
      $alv = $alv_array[$tuoteno];
    }
    else {
      $alv = $kayttajan_alv;
    }

    $tmp_rivitunnus = $rivitunnus;

    $rivitunnus = 0;

    if ($kpl != 0) {
      require 'tilauskasittely/lisaarivi.inc';
    }

    $rivitunnus = $tmp_rivitunnus;

    $query = "UPDATE asn_sanomat SET
              tuoteno     = '{$tuoteno}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$rivitunnus}'";
    $upd_res = pupe_query($query);

    $hinta   = '';
    $netto   = '';
    $var   = '';
    $kpl   = '';
    $alv   = '';
    $paikka  = '';

    for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }
  }

  if ($lisavarusteita == "ON" and $perheid2 > 0) {
    //Päivitetään isälle perheid jotta tiedetään, että lisävarusteet on nyt lisätty
    $query = "UPDATE tilausrivi set
              perheid2    = '{$perheid2}'
              where yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$perheid2}'";
    $updres = pupe_query($query);
  }

  $tee = 'etsi';
}

//korjataan siirrettävät muuttujat taas talteen
if (isset($muut_siirrettavat) and trim($muut_siirrettavat) != "") {
  list($asn_rivi, $toimittaja, $tilausnro, $tuoteno, $tilaajanrivinro, $kpl, $valitse, $kolli, $toimittajanumero, $asn_numero, $toimipaikka) = explode("!¡!", $muut_siirrettavat);
}

if ($tee == 'kohdista_tilausrivi') {

  if (count($tunnukset) == 0) {
    $error = t("Halusit kohdistaa, mutta et valinnut yhtään riviä")."!";
    $tee = 'etsi';
  }
  else {
    // typecastataan formista tulleet tunnukset stringeistä inteiksi
    $tunnukset = array_map('intval', $tunnukset);
    $poista_tilausrivi = array_map('intval', $poista_tilausrivi);
    $ostotilauksella_tilaajanrivinro = array_map('intval', $ostotilauksella_tilaajanrivinro);

    // otetaan ostotilausrivin kpl määrä, splitataan ja mennää eteenpäin...
    // Tämä pitää sitten jollain tavalla muuttaa paremmaksi, tämä on versio 1.0

    $kaytettavat_tunnukset = array();
    $kaytettava_kappalemaara_yhteensa = 0;
    $splitattava_tilausrivi = 0;

    $_tunn = $valitse == 'asn' ? $asn_rivi : $rivitunnus;

    if ($valitse == 'asn') {
      $lajilisa = 'asn';
    }
    elseif ($valitse == 'maventa') {
      $lajilisa = 'mav';
    }
    elseif ($valitse == 'finvoice') {
      $lajilisa = 'fin';
    }
    else {
      $lajilisa = 'tec';
    }

    // haetaan ASN-sanomalta kpl määrä
    $hakuquery = "SELECT *
                  FROM asn_sanomat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$_tunn}'
                  AND laji    = '{$lajilisa}'";
    $hakures = pupe_query($hakuquery);
    $asn_row_haku = mysqli_fetch_assoc($hakures);

    foreach ($tunnukset as $tmp_tunn) {
      if ($kaytettava_kappalemaara_yhteensa < $asn_row_haku['kappalemaara']) {
        $kaytettavat_tunnukset[] = $tmp_tunn;
        $kaytettava_kappalemaara_yhteensa += $ostotilauksella_kpl[$tmp_tunn];
        $splitattava_tilausrivi = $tmp_tunn;
      }
    }

    if ($kaytettava_kappalemaara_yhteensa < $asn_row_haku['kappalemaara']) {
      $error = t("Valitse vähintään")." {$asn_row_haku['kappalemaara']}!";
      $tee = 'etsi';
    }
    else {

      // haetaan ostotilauksen rivitiedot kyseiselle riville.
      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$splitattava_tilausrivi}'";
      $ostores = pupe_query($query);
      $ostotilausrivirow = mysqli_fetch_assoc($ostores);

      $query = "SELECT liitostunnus
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$ostotilausrivirow['otunnus']}'";
      $liit_res = pupe_query($query);
      $liit_row = mysqli_fetch_assoc($liit_res);

      $query = "SELECT tuotteen_toimittajat.toim_tuoteno
                FROM tuotteen_toimittajat
                JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P' AND tuote.tuoteno = '{$tuoteno}')
                WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.liitostunnus = '{$liit_row['liitostunnus']}'";
      $tuoteno_res = pupe_query($query);
      $tuoteno_row = mysqli_fetch_assoc($tuoteno_res);

      // tehdään splitti
      if ($kaytettava_kappalemaara_yhteensa > $asn_row_haku['kappalemaara']) {

        $erotus = abs($kaytettava_kappalemaara_yhteensa - $asn_row_haku['kappalemaara']);

        $upd_kpl = ($ostotilausrivirow['varattu'] + $ostotilausrivirow['kpl']) - $erotus;

        $yks_rivihinta = 0;

        if ($ostotilausrivirow['kpl'] != 0) {
          $yks_rivihinta = $ostotilausrivirow['rivihinta'] / $ostotilausrivirow['kpl'];
        }

        $updatelisa = $ostotilausrivirow['kpl'] != 0 ? "kpl = '{$upd_kpl}'," : "varattu = '{$upd_kpl}',";
        $rivihintalisa = $yks_rivihinta != 0 ? "rivihinta = '".($yks_rivihinta * $upd_kpl)."'," : "";

        // Päivitetään alkuperäiselle riville saapunut kappalemäärä
        $query = "UPDATE tilausrivi SET
                  {$updatelisa}
                  {$rivihintalisa}
                  tilkpl      = '{$upd_kpl}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$ostotilausrivirow['tunnus']}'";
        $upres = pupe_query($query);

        $insertlisa = $ostotilausrivirow['kpl'] != 0 ? "kpl = '{$erotus}'," : "varattu = '{$erotus}',";

        // Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
        $fields = "yhtio";
        $values = "'{$kukarow['yhtio']}'";

        // Ei monisteta tunnusta
        for ($ii = 1; $ii < mysqli_num_fields($ostores) - 1; $ii++) {

          $fieldname = mysqli_field_name($ostores, $ii);

          $fields .= ", ".$fieldname;

          switch ($fieldname) {
          case 'varattu':
            if ($ostotilausrivirow['kpl'] == 0) $values .= ", '{$erotus}'";
            else $values .= ", 0";
            break;
          case 'kpl':
            if ($ostotilausrivirow['kpl'] != 0) $values .= ", '{$erotus}'";
            else $values .= ", 0";
            break;
          case 'tilkpl':
            $values .= ", '{$erotus}'";
            break;
          case 'rivihinta':
            if ($yks_rivihinta != 0) $values .= ", '".($yks_rivihinta * $erotus)."'";
            else $values .= ", 0";
            break;
          case 'kate_korjattu':
            $values .= ", NULL";
            break;
          default:
            $values .= ", '".$ostotilausrivirow[$fieldname]."'";
          }
        }

        $kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
        
        // MUOKKAUS: mysqli_insert_id():
        $uusires = pupe_query($kysely, $GLOBALS["link"]);
        $tilausrivi_id = mysqli_insert_id($GLOBALS["link"]);

        if ($ostotilausrivirow['kpl'] != 0) {

          $query = "SELECT *
                    FROM tapahtuma
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND laji       = 'tulo'
                    AND rivitunnus = '{$ostotilausrivirow['tunnus']}'";
          $tapahtuma_res = pupe_query($query);
          $tapahtuma_row = mysqli_fetch_assoc($tapahtuma_res);

          // Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
          $fields = "yhtio";
          $values = "'{$kukarow['yhtio']}'";

          // Ei monisteta tunnusta
          for ($ii = 1; $ii < mysqli_num_fields($tapahtuma_res) - 1; $ii++) {

            $fieldname = mysqli_field_name($tapahtuma_res, $ii);

            $fields .= ", ".$fieldname;

            switch ($fieldname) {
            case 'rivitunnus':
              $values .= ", '{$tilausrivi_id}'";
              break;
            case 'kpl':
              $values .= ", '{$erotus}'";
              break;
            default:
              $values .= ", '".$tapahtuma_row[$fieldname]."'";
            }
          }

          $kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
	
	  // MUOKKAUS: mysqli_insert_id():
          $uusires = pupe_query($kysely, $GLOBALS["link"]);
          $tapahtuma_id = mysqli_insert_id($GLOBALS["link"]);

          $query = "UPDATE tapahtuma SET
                    kpl            = '{$upd_kpl}'
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND laji       = 'tulo'
                    AND rivitunnus = '{$ostotilausrivirow['tunnus']}'";
          $upd_res = pupe_query($query);
        }
      }

      // päivitetään tässä vaiheessa tilaukselle tilaajanrivipositio tälle uudelle riville, mikäli ollaan poistamassa samalla vanha.
      if ($poista_tilausrivi["0"] != 0) {

        $updatequery2 = "UPDATE tilausrivi SET
                         tilaajanrivinro = '{$poista_tilausrivi[0]}'
                         WHERE yhtio     = '{$kukarow['yhtio']}'
                         AND otunnus     = '{$asn_row_haku['tilausnumero']}'
                         AND tunnus      IN (".implode(",", $tunnukset).")";
        pupe_query($updatequery2);
      }

      $query = "SELECT paketintunniste, asn_numero
                FROM asn_sanomat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$_tunn}'";
      $res = pupe_query($query);
      $row = mysqli_fetch_assoc($res);

      $kolli = $row['paketintunniste'];

      $toim_tuotenolisa = trim($tuoteno_row['toim_tuoteno']) != "" ? ", toim_tuoteno = '{$tuoteno_row['toim_tuoteno']}' " : "";

      $tunnukset = array_intersect_key($tunnukset, $kaytettavat_tunnukset);

      $query = "UPDATE asn_sanomat SET
                tilausrivi  = '".implode(",", $tunnukset)."',
                muuttaja    = '{$kukarow['kuka']}',
                muutospvm   = now(),
                tuoteno     = '{$ostotilausrivirow['tuoteno']}'
                {$toim_tuotenolisa}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$_tunn}'";
      $updres = pupe_query($query);

      $tee = 'nayta';
    }
  }
}

if ($tee == 'etsi') {

  if ($valitse == 'asn') {
    if (isset($asn_rivi) and mb_strpos($asn_rivi, '##') !== false) {
      list($asn_rivi, , $tilaajanrivinro) = explode('##', $asn_rivi); // ei otetan linkistä tuoteno:a koska jos siinä on väli niin hajoilee

      $asn_rivi = (int) $asn_rivi;

      $query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
      $result = pupe_query($query);
      $asn_row = mysqli_fetch_assoc($result);

      $poikkeus_tuoteno = trim($asn_row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$asn_row[toim_tuoteno]}','{$asn_row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$asn_row[toim_tuoteno]}'";

      $query = "SELECT tt.*
                FROM tuotteen_toimittajat AS tt
                JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus AND toimi.toimittajanro = '{$asn_row['toimittajanumero']}' AND toimi.tyyppi != 'P')
                WHERE tt.yhtio = 'artr'
                {$poikkeus_tuoteno}";
      $chk_res = pupe_query($query);

      if (mysqli_num_rows($chk_res) == 0) {
        // haetaan vaihtoehtoisten tuotenumeroiden (tuotteen_toimittajat_tuotenumerot) kautta tuotteen_toimittajat.toim_tuoteno. Osataan myös hakea vaihtoehtoinen tuotenumero ilman että
        $chk_res = tuotteen_toimittajat_tuotenumerot_haku($asn_row['toim_tuoteno'], $asn_row['toimittajanumero']);

        if (mysqli_num_rows($chk_res) != 0) {
          $chk_row = mysqli_fetch_assoc($chk_res);
          $asn_row['toim_tuoteno'] = $chk_row['toim_tuoteno'];

          $poikkeus_tuoteno = trim($asn_row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$asn_row[toim_tuoteno]}','{$asn_row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$asn_row[toim_tuoteno]}'";
        }
        else {

          if (trim($asn_row["toim_tuoteno2"]) != "") {
            $chk_res = tuotteen_toimittajat_tuotenumerot_haku($asn_row["toim_tuoteno2"], $asn_row['toimittajanumero']);

            if (mysqli_num_rows($chk_res) != 0) {
              $chk_row = mysqli_fetch_assoc($chk_res);
              $asn_row['toim_tuoteno'] = $chk_row['toim_tuoteno'];

              $poikkeus_tuoteno = trim($asn_row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$asn_row[toim_tuoteno]}','{$asn_row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$asn_row[toim_tuoteno]}'";
            }
          }
        }
      }


      $query = "SELECT tt.tuoteno ttuoteno, tt.toim_tuoteno, tuote.tuoteno tuoteno
                FROM tuotteen_toimittajat AS tt
                JOIN toimi ON (toimi.tunnus = tt.liitostunnus AND toimi.yhtio = tt.yhtio AND toimi.toimittajanro = '{$asn_row['toimittajanumero']}' AND toimi.tyyppi != 'P')
                JOIN tuote ON (tuote.yhtio = toimi.yhtio AND tuote.tuoteno = tt.tuoteno AND tuote.status != 'P')
                WHERE tt.yhtio = '{$kukarow['yhtio']}'
                {$poikkeus_tuoteno}";
      $result = pupe_query($query);
      $apurivi = mysqli_fetch_assoc($result);

      if ($apurivi["tuoteno"] !="") {
        $tuoteno = $apurivi['tuoteno'];
      }
      else {
        $tuoteno = $asn_row['toim_tuoteno'];
      }
    }

    // pakotetaan tuoteno asn_sanomasta. Välilyönnit tekee kiusaa
    // if (!isset($tuoteno)) $tuoteno =  $asn_row['toim_tuoteno'];
    if (!isset($toimittaja) and isset($asn_row)) $toimittaja = $asn_row['toimittajanumero'];
    if (!isset($tilausnro) and isset($asn_row)) $tilausnro = $asn_row['tilausnumero'];
    if (!isset($kpl) and isset($asn_row)) $kpl = $asn_row['kappalemaara'];
  }
  else {
    if (isset($lasku) and mb_strpos($lasku, '##') !== false) {
      list($lasku, $tuoteno, $tilaajanrivinro, $toimittaja, $kpl, $rivitunnus, $tilausnumero, $toim_tuoteno, $toimipaikka) = explode('##', $lasku);

      if ($tuoteno == '') $tuoteno = $toim_tuoteno;

      $tilausnro = $tilausnumero;
    }
  }

  echo "<form method='post' action='?tee=etsi&valitse={$valitse}&kolli={$kolli}&asn_numero={$asn_numero}&toimipaikka={$toimipaikka}&toimittajanumero={$toimittajanumero}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&lopetus={$lopetus}'>";

  echo "<table>";
  echo "<tr><th colspan='6'>", t("Etsi tilausrivi"), "</th></tr>";

  echo "<tr>";
  echo "<th>", t("Toimittaja"), "</th>";
  echo "<th>", t("Tilausnro"), "</th>";
  echo "<th>", t("Tuotenro"), "</th>";
  echo "<th>", t("Tilaajan rivinro"), "</th>";
  echo "<th>", t("Kpl"), "</th>";
  echo "<th>&nbsp;</th>";
  echo "</tr>";

  $toimittaja = (int) $toimittaja;

  $query = "SELECT tunnus, asn_sanomat
            FROM toimi
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND toimittajanro  = '{$toimittaja}'
            AND tyyppi        != 'P'";
  $toimires = pupe_query($query);
  $toimirow = mysqli_fetch_assoc($toimires);

  $chk = (isset($rivit_ei_asnlla) and trim($rivit_ei_asnlla) != '') ? " checked" : "";

  echo "<tr>";
  echo "<td>{$toimittaja}</td>";
  echo "<td><input type='text' name='tilausnro' value='{$tilausnro}' /></td>";
  echo "<td><input type='text' name='tuoteno' value='{$tuoteno}' /></td>";
  echo "<td><input type='text' name='tilaajanrivinro' value='{$tilaajanrivinro}' /></td>";
  echo "<td><input type='text' name='kpl' value='{$kpl}' /></td>";
  echo "<td><input type='submit' class='hae_btn' value = '".t("Etsi")."' /></td>";
  echo "</tr>";

  if ($toimirow['asn_sanomat'] == 'K') {
    echo "<tr>";
    echo "<td colspan='6' class='back'><input type='checkbox' name='rivit_ei_asnlla'{$chk} /> ", t("Näytä myös rivit joita ei ole ASN:llä tullut"), "</td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "</form>";

  echo "<br /><hr /><br />";

  echo "<form method='post' action='?tee=uusirivi&valitse={$valitse}&kolli={$kolli}&asn_numero={$asn_numero}&toimittajanumero={$toimittajanumero}&lasku={$lasku}&rivitunnus={$rivitunnus}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno=".urlencode($tuoteno)."&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}&toimipaikka={$toimipaikka}&lopetus={$lopetus}'>";
  echo "<input type='submit' value='", t("Tee uusi tilausrivi"), "' />";
  echo "</form>";
  echo "<br />";

  if (trim($toimittaja) != '' or trim($tilausnro) != '' or trim($tuoteno) != '' or trim($tilaajanrivinro) != '' or trim($kpl) != '') {
    echo "<br /><hr /><br />";

    if (isset($error)) echo "<font class='error'>{$error}</font><br /><br />";

    echo "<form method='post' id='kohdista_tilausrivi_formi' action='?tee=kohdista_tilausrivi&rivitunnus={$rivitunnus}&valitse={$valitse}&kolli={$kolli}&asn_numero={$asn_numero}&toimittajanumero={$toimittajanumero}&lasku={$lasku}&asn_rivi={$asn_rivi}&toimittaja={$toimittaja}&tilausnro={$tilausnro}&tuoteno={$tuoteno}&tilaajanrivinro={$tilaajanrivinro}&kpl={$kpl}&toimipaikka={$toimipaikka}'>";
    echo "<input type='hidden' name='lopetus' id='lopetus' value='{$lopetus}' />";

    echo "<table>";
    echo "<tr><th colspan='6'>", t("Haun tulokset"), "</th><th><input type='submit' value='", t("Kohdista"), "' /></th></tr>";
    echo "<tr>";
    echo "<th>", t("Tilausnro"), "</th>";
    echo "<th>", t("Tuoteno"), "</th>";
    echo "<th>", t("Varattu"), " / ", t("Kpl"), "</th>";
    echo "<th>", t("Keikka"), "</th>";
    echo "<th>", t("Keikan tila"), "</th>";
    echo "<th>", t("Kohdistus"), "</th>";
    echo "<th>", t("Poista"), "</th>";
    echo "</tr>";

    $tilausnro = (int) $tilausnro;

    $tilaajanrivinrolisa = trim($tilaajanrivinro) != '' ? " and tilausrivi.tilaajanrivinro = ".(int) $tilaajanrivinro : '';
    $tilausnrolisa = (trim($tilausnro) != '' and trim($tilausnro) != 0) ? " and lasku.tunnus LIKE '%{$tilausnro}'" : '';
    $tilausnrolisa2 = (trim($tilausnro) != '' and trim($tilausnro) != 0) ? " and tilausrivi.otunnus LIKE '%{$tilausnro}'" : '';
    $tuoteno_valeilla = str_replace(' ', '_', $tuoteno);
    $tuoteno_ilman_valeilla =str_replace(' ', '', $tuoteno);
    $tuotenolisa = trim($tuoteno) != '' ? " and (tuote.tuoteno like '".sanitize_string($tuoteno)."%' or tuote.tuoteno = '".sanitize_string($tuoteno_valeilla)."' or tuote.tuoteno = '".sanitize_string($tuoteno_ilman_valeilla)."')" : '';
    $kpllisa = trim($kpl) != '' ? " and tilausrivi.varattu = ".(float) $kpl : '';

    if (trim($kpl) != '' and $valitse != 'asn') {
      $kpl = (float) $kpl;
      $kpllisa = " and (tilausrivi.varattu + tilausrivi.kpl = '{$kpl}')";
    }

    if ($tuotenolisa != "") {
      $query = "SELECT status, tuoteno
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND status  = 'P'
                {$tuotenolisa}";
      $status_chk_res = pupe_query($query);

      if (mysqli_num_rows($status_chk_res) > 0) {

        while ($status_chk_row = mysqli_fetch_assoc($status_chk_res)) {
          echo "<tr>";
          echo "<td colspan='7'>", t("Tuote on poistunut"), "! ({$status_chk_row['tuoteno']})</td>";
          echo "</tr>";
        }
      }
    }

    $tuotenolisa = trim($tuoteno) != '' ? " and (tilausrivi.tuoteno like '".sanitize_string($tuoteno)."%' or tilausrivi.tuoteno = '".sanitize_string($tuoteno_valeilla)."' or tilausrivi.tuoteno = '".sanitize_string($tuoteno_ilman_valeilla)."')" : '';

    if (isset($toimipaikka) and trim($toimipaikka) != '') {

      if ($toimipaikka != 'kaikki') {
        $toimipaikka_ostotilaus_lisa = "AND lasku.vanhatunnus = '{$toimipaikka}'";
        $toimipaikka_saapuminen_lisa = "AND lasku.yhtio_toimipaikka = '{$toimipaikka}'";
      }
    }
    else {
      $toimipaikka_ostotilaus_lisa = $toimipaikka_saapuminen_lisa = "";
    }

    // Ostotilaukset ja suoraan saapumiselle lisätyt, mutta ei saa olla saapumiselle kohdistettu
    $query1 = "(SELECT DISTINCT tilausrivi.tunnus,
                tilausrivi.tuoteno,
                tilausrivi.otunnus,
                tilausrivi.varattu,
                tilausrivi.kpl,
                tilausrivi.tilaajanrivinro,
                tilausrivi.uusiotunnus,
                lasku.tunnus laskutunnus
                FROM lasku
                JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'O' AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.uusiotunnus = 0 AND tilausrivi.kpl+tilausrivi.varattu != 0 {$tilaajanrivinrolisa} {$tuotenolisa} {$kpllisa})
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.status != 'P')
                WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
                AND lasku.tila         = 'O'
                AND lasku.alatila      = 'A'
                AND lasku.liitostunnus = '{$toimirow['tunnus']}'
                {$toimipaikka_ostotilaus_lisa}
                {$tilausnrolisa})
                UNION
                (SELECT DISTINCT tilausrivi.tunnus,
                tilausrivi.tuoteno,
                tilausrivi.otunnus,
                tilausrivi.varattu,
                tilausrivi.kpl,
                tilausrivi.tilaajanrivinro,
                tilausrivi.uusiotunnus,
                lasku.tunnus laskutunnus
                FROM lasku
                JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'O' AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.uusiotunnus = 0 AND tilausrivi.kpl+tilausrivi.varattu != 0 {$tilaajanrivinrolisa} {$tuotenolisa} {$kpllisa})
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.status != 'P')
                WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
                AND lasku.tila         = 'K'
                AND lasku.liitostunnus = '{$toimirow['tunnus']}'
                {$toimipaikka_saapuminen_lisa}
                {$tilausnrolisa})";

    // Saapumiset, ei loppulasketut eikä sellaset joihin on jo vaihto-omaisuuslasku liitetty, pitää olla saapumiselle kohdistettu
    $query2 = "SELECT DISTINCT tilausrivi.tunnus,
               tilausrivi.tuoteno,
               tilausrivi.otunnus,
               tilausrivi.varattu,
               tilausrivi.kpl,
               tilausrivi.tilaajanrivinro,
               tilausrivi.uusiotunnus,
               lasku.tunnus laskutunnus
               FROM lasku
               JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'O' AND tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.uusiotunnus != 0 AND tilausrivi.kpl+tilausrivi.varattu != 0 {$tilausnrolisa2} {$tilaajanrivinrolisa} {$tuotenolisa} {$kpllisa})
               JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.status != 'P')
               WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
               AND lasku.tila         = 'K'
               AND lasku.tapvm        = '0000-00-00'
               AND lasku.mapvm        = '0000-00-00'
               {$toimipaikka_saapuminen_lisa}
               AND lasku.liitostunnus = '{$toimirow['tunnus']}'";

    if ($valitse == 'asn') {
      $query = "{$query1} ORDER BY tunnus, uusiotunnus, laskutunnus";
    }
    else {
      $query = "{$query1} UNION ({$query2}) ORDER BY tunnus, uusiotunnus, laskutunnus";
    }

    $result = pupe_query($query);

    while ($row = mysqli_fetch_assoc($result)) {

      if ($valitse == 'maventa') {
        $lajilisa = 'mav';
      }
      elseif ($valitse == 'finvoice') {
        $lajilisa = 'fin';
      }
      elseif ($valitse == 'asn') {
        $lajilisa = 'asn';
      }
      else {
        $lajilisa = 'tec';
      }

      // katsotaan ettei riviä ole jo kohdistettu muuhun riviin
      $query = "SELECT tunnus
                FROM asn_sanomat
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND toimittajanumero = '{$toimittaja}'
                AND MATCH (tilausrivi) AGAINST ('{$row['tunnus']}' IN BOOLEAN MODE)
                AND laji             = '{$lajilisa}'";
      $chkres = pupe_query($query);

      if (mysqli_num_rows($chkres) > 0) continue;

      if ($valitse != 'asn' and $toimirow['asn_sanomat'] == 'K' and (!isset($rivit_ei_asnlla) or trim($rivit_ei_asnlla) == '')) {
        // katsotaan että asn rivi pitää olla olemassa
        $query = "SELECT *
                  FROM asn_sanomat
                  WHERE yhtio          = '{$kukarow['yhtio']}'
                  AND toimittajanumero = '{$toimittaja}'
                  AND MATCH (tilausrivi) AGAINST ('{$row['tunnus']}' IN BOOLEAN MODE)
                  AND laji             = 'asn'";
        $chkres = pupe_query($query);

        if (mysqli_num_rows($chkres) == 0) continue;
      }

      echo "<tr>";
      echo "<td align='right'>{$row['otunnus']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td align='right'>{$row['varattu']} / {$row['kpl']}</td>";

      if ($row['uusiotunnus'] > 0) {
        $query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['uusiotunnus']}'";
        $keikkares = pupe_query($query);
        $keikkarow = mysqli_fetch_assoc($keikkares);
        $row['uusiotunnus'] = $keikkarow['laskunro'];
      }

      echo "<td align='right'>$row[uusiotunnus]</td>";

      if ($row['uusiotunnus'] > 0 and $row['kpl'] == 0 and $valitse == 'asn') {
        echo "<td>", t("Rivi on kohdistettu"), "</td>";
        echo "<td>&nbsp;</td>";
      }
      elseif ($valitse == 'asn' and $row['uusiotunnus'] > 0 and $row['kpl'] != 0) {
        echo "<td>", t("Viety varastoon"), "</td>";
        echo "<td>&nbsp;</td>";
      }
      else {
        if ($row['uusiotunnus'] > 0 and $row['kpl'] != 0) {
          echo "<td>", t("Viety varastoon"), "</td>";
        }
        else {
          echo "<td>&nbsp;</td>";
        }
        echo "<td align='center'>";
        echo "<input type='checkbox' name='tunnukset[]' class='tunnukset' value='{$row['tunnus']}' />";
        echo "<input type='hidden' name='ostotilauksella_kpl[{$row['tunnus']}]' value='", ($row['varattu'] + $row['kpl']), "' />";
        echo "<input type='hidden' name='ostotilauksella_tilaajanrivinro[{$row['tunnus']}]' value='{$row['tilaajanrivinro']}' />";
        echo "</td>";
      }
      echo "<td><input type='checkbox' name='poista_tilausrivi[]' class='tunnukset' value='{$row['tunnus']}' /></td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "</form>";
  }
}

if ($tee == 'nayta') {

  if ($valitse == 'asn') {

    if (mb_strpos($kolli, "##") !== FALSE) list($kolli, $asn_numero, $toimittajanumero) = explode("##", $kolli);

    $query = "SELECT asn_sanomat.toimittajanumero,
              asn_sanomat.toim_tuoteno,
              asn_sanomat.tilausrivinpositio,
              asn_sanomat.kappalemaara,
              asn_sanomat.status,
              asn_sanomat.tilausnumero,
              tilausrivi.tuoteno,
              tilausrivi.otunnus,
              tilausrivi.tilkpl as tilattu,
              if(tilausrivi.ale1 = 0, '', tilausrivi.ale1) AS alennus
              FROM asn_sanomat
              JOIN tilausrivi ON (tilausrivi.yhtio = asn_sanomat.yhtio AND tilausrivi.tunnus IN (asn_sanomat.tilausrivi))
              JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi!='P')
              WHERE asn_sanomat.yhtio          = '{$kukarow['yhtio']}'
              AND asn_sanomat.paketintunniste  = '{$kolli}'
              AND asn_sanomat.asn_numero       = '{$asn_numero}'
              AND asn_sanomat.toimittajanumero = '{$toimittajanumero}'
              AND asn_sanomat.laji             = 'asn'
              ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
    $result = pupe_query($query);

    echo "<form method='post' action='?valitse={$valitse}&lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//kolli={$kolli}//asn_numero={$asn_numero}//toimittajanumero={$toimittajanumero}//valitse={$valitse}' id='kolliformi'>";
    echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
    echo "<input type='hidden' id='kolli' name='kolli' value='{$kolli}' />";
    echo "<input type='hidden' id='asn_numero' name='asn_numero' value='{$asn_numero}' />";
    echo "<input type='hidden' id='toimittajanumero' name='toimittajanumero' value='{$toimittajanumero}' />";
    echo "<input type='hidden' id='asn_rivi' name='asn_rivi' value='' />";
    echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Toimittajanro"), "</th>";
    echo "<th>", t("Laskun numero"), "</th>";
    echo "<th>", t("Ostotilausnro"), "</th>";
    echo "<th>", t("Tuotenro"), "</th>";
    echo "<th>", t("Toimittajan"), "<br />", t("Tuotenro"), "</th>";
    echo "<th>", t("Nimitys"), "</th>";
    echo "<th>", t("Tilattu kpl"), "</th>";
    echo "<th>", t("Asn-kpl"), "</th>";
    echo "<th>", t("Alennukset"), "</th>";
    echo "<th>", t("Status"), "</th>";
    echo "<td class='back'></td>";
    echo "</tr>";

    $ok = 0;

    while ($row = mysqli_fetch_assoc($result)) {

      $ok++;

      echo "<tr>";

      $query = "SELECT nimitys FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$row['tuoteno']}' and status !='P'";
      $tuoteres = pupe_query($query);
      $tuoterow = mysqli_fetch_assoc($tuoteres);

      $row['nimitys'] = $tuoterow['nimitys'];

      echo "<td align='right'>{$row['toimittajanumero']}</td>";
      echo "<td align='right'></td>";
      echo "<td align='right'>{$row['otunnus']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['toim_tuoteno']}</td>";
      echo "<td>{$row['nimitys']}</td>";
      echo "<td align='right'>{$row['tilattu']}</td>";
      echo "<td align='right'>{$row['kappalemaara']}</td>";
      echo "<td></td>";
      echo "<td><font class='ok'>", t("Ok"), "</font></td>";
      echo "<td class='back'></td>";
      echo "</tr>";
    }

    $virhe = 0;

    $query = "SELECT asn_sanomat.toimittajanumero,
              asn_sanomat.toim_tuoteno,
              asn_sanomat.toim_tuoteno2,
              asn_sanomat.tilausrivinpositio,
              asn_sanomat.kappalemaara,
              asn_sanomat.status,
              asn_sanomat.tilausnumero,
              toimi.tunnus AS toimi_tunnus, asn_sanomat.tunnus AS asn_tunnus
              FROM asn_sanomat
              JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
              WHERE asn_sanomat.yhtio          = '{$kukarow['yhtio']}'
              AND asn_sanomat.paketintunniste  = '{$kolli}'
              AND (asn_sanomat.tilausrivi is NULL or asn_sanomat.tilausrivi = '')
              AND asn_sanomat.laji             = 'asn'
              AND asn_sanomat.asn_numero       = '{$asn_numero}'
              AND asn_sanomat.toimittajanumero = '{$toimittajanumero}'
              ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
    $result = pupe_query($query);


    while ($row = mysqli_fetch_assoc($result)) {

      $virhe++;

      echo "<tr>";

      $poikkeus_tuoteno = trim($row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$row[toim_tuoteno]}','{$row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$row[toim_tuoteno]}'";

      $query = "SELECT tt.*
                FROM tuotteen_toimittajat AS tt
                JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus AND toimi.toimittajanro = '{$toimittajanumero}' AND toimi.tyyppi != 'P')
                WHERE tt.yhtio = 'artr'
                {$poikkeus_tuoteno}";
      $chk_res = pupe_query($query);

      if (mysqli_num_rows($chk_res) == 0) {
        // haetaan vaihtoehtoisten tuotenumeroiden (tuotteen_toimittajat_tuotenumerot) kautta tuotteen_toimittajat.toim_tuoteno. Osataan myös hakea vaihtoehtoinen tuotenumero ilman että
        $chk_res = tuotteen_toimittajat_tuotenumerot_haku($row['toim_tuoteno'], $toimittajanumero);

        if (mysqli_num_rows($chk_res) != 0) {
          $chk_row = mysqli_fetch_assoc($chk_res);
          $row['toim_tuoteno'] = $chk_row['toim_tuoteno'];

          $poikkeus_tuoteno = trim($row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$row[toim_tuoteno]}','{$row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$row[toim_tuoteno]}'";
        }
        else {

          if (trim($row["toim_tuoteno2"]) != "") {
            $chk_res = tuotteen_toimittajat_tuotenumerot_haku($row["toim_tuoteno2"], $toimittajanumero);

            if (mysqli_num_rows($chk_res) != 0) {
              $chk_row = mysqli_fetch_assoc($chk_res);
              $row['toim_tuoteno'] = $chk_row['toim_tuoteno'];

              $poikkeus_tuoteno = trim($row["toim_tuoteno2"]) != "" ? "AND tt.toim_tuoteno IN ('{$row[toim_tuoteno]}','{$row["toim_tuoteno2"]}')" : "AND tt.toim_tuoteno = '{$row[toim_tuoteno]}'";
            }
          }
        }
      }

      $query = "SELECT tt.tuoteno
                FROM tuotteen_toimittajat as tt
                JOIN tuote on (tuote.yhtio=tt.yhtio and tt.tuoteno = tuote.tuoteno and tuote.status !='P')
                WHERE tt.yhtio      = '{$kukarow['yhtio']}'
                {$poikkeus_tuoteno}
                AND tt.liitostunnus = '{$row['toimi_tunnus']}'";
      $res = pupe_query($query);

      if (mysqli_num_rows($res) > 0) {
        $ttrow = mysqli_fetch_assoc($res);

        $row['tuoteno'] = $ttrow['tuoteno'];

        $query = "SELECT nimitys
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$ttrow['tuoteno']}'";
        $tres = pupe_query($query);
        $trow = mysqli_fetch_assoc($tres);

        $row['nimitys'] = $trow['nimitys'];

        $query = "SELECT tuoteno, uusiotunnus, tilaajanrivinro
                  FROM tilausrivi
                  WHERE yhtio         = '{$kukarow['yhtio']}'
                  AND otunnus         = '{$row['tilausnumero']}'
                  AND tuoteno         = '{$row['tuoteno']}'
                  AND tilaajanrivinro = '{$row['tilausrivinpositio']}'
                  AND tyyppi          = 'O'";
        $tilres = pupe_query($query);


        if (mysqli_num_rows($tilres) > 0) {
          $tilrow = mysqli_fetch_assoc($tilres);
          // $row['nimitys'] = $tilrow['nimitys'];
          $row['uusiotunnus'] = $tilrow['uusiotunnus'];
          $row['tilausrivinpositio'] = $tilrow['tilaajanrivinro'];
        }
        else {
          $row['uusiotunnus'] = 0;
        }
      }
      else {
        $row['tuoteno'] = '';
        $row['nimitys'] = t("Tuntematon tuote");
      }

      echo "<td align='right'>{$row['toimittajanumero']}</td>";
      echo "<td align='right'></td>";
      echo "<td align='right'>{$row['tilausnumero']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['toim_tuoteno']}</td>";
      echo "<td>{$row['nimitys']}</td>";
      echo "<td align='right'>{$row['tilattu']}</td>";
      echo "<td align='right'>{$row['kappalemaara']}</td>";
      echo "<td></td>";

      echo "<td><font class='error'>", t("Virhe"), "</font></td>";

      echo "<td class='back'>";

      if ($row['uusiotunnus'] == 0 or $virhe) echo "<input type='button' class='etsibutton' id='{$row['asn_tunnus']}##{$row['tuoteno']}##{$row['tilausrivinpositio']}' value='", t("Etsi"), "' />";

      echo "</td>";
      echo "</tr>";
    }

    if ($ok and !$virhe) {
      echo "<tr><th colspan='10' class='back'><input type='button' class='vahvistabutton' value='", t("Vahvista"), "' /></th></tr>";
      if ($valitse == "asn") {
        echo "<tr><th colspan='10' class='back'><input type='button' class='poistakohdistus' value='", t("Poista Kohdistus"), "' /></th></tr>";
      }
    }

    echo "</table>";
    echo "</form>";
  }
  else {

    echo "<form method='post' action='?valitse={$valitse}' id='kolliformi'>";
    echo "<input type='hidden' id='tee' name='tee' value='etsi' />";
    echo "<input type='hidden' id='lopetus' name='lopetus' value='{$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta//lasku={$lasku}//valitse={$valitse}//toimipaikka={$toimipaikka}' />";
    echo "<input type='hidden' id='lasku' name='lasku' value='{$lasku}' />";
    echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
    echo "<input type='hidden' id='toimipaikka' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Toimittajanro"), "</th>";
    echo "<th>", t("Ostotilausnro"), "</th>";
    echo "<th>", t("Tuotenro"), "</th>";
    echo "<th>", t("Toimittajan"), "<br />", t("Tuotenro"), "</th>";
    echo "<th>", t("Nimitys"), "</th>";
    echo "<th>", t("Rivinro"), "</th>";
    echo "<th>", t("Kpl"), "</th>";
    echo "<th>", t("Hinta"), "</th>";
    echo "<th>", t("Status"), "</th>";
    echo "<td class='back'>&nbsp;</td>";
    echo "</tr>";

    $query = "SELECT liitostunnus, tunnus, yhtio_toimipaikka
              FROM lasku
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND laskunro = '{$lasku}'
              AND tila     in ('H','Y','M','P','Q')
              AND vienti   in ('B', 'C', 'E', 'F', 'H', 'I')
              ORDER BY tapvm desc";
    $laskures = pupe_query($query);

    if (mysqli_num_rows($laskures) == 0) {
      $query = "SELECT liitostunnus, tunnus, yhtio_toimipaikka
                FROM lasku
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND comments = '{$lasku}'
                AND tila     in ('H','Y','M','P','Q')
                AND vienti   in ('B', 'C', 'E', 'F', 'H', 'I')
                ORDER BY tapvm desc";
      $laskures = pupe_query($query);
    }

    $laskurow = mysqli_fetch_assoc($laskures);

    $toimipaikka = $laskurow['yhtio_toimipaikka'];

    if ($valitse == 'maventa') {
      $lajilisa = 'mav';
    }
    elseif ($valitse == 'finvoice') {
      $lajilisa = 'fin';
    }
    else {
      $lajilisa = 'tec';
    }

    $query = "SELECT asn_sanomat.toimittajanumero,
              asn_sanomat.toim_tuoteno,
              IF(asn_sanomat.toim_tuoteno2 = '', asn_sanomat.toim_tuoteno, asn_sanomat.toim_tuoteno2) toim_tuoteno2,
              asn_sanomat.tuoteno,
              asn_sanomat.tilausrivinpositio,
              asn_sanomat.status,
              asn_sanomat.tilausnumero,
              asn_sanomat.kappalemaara,
              asn_sanomat.tilausrivi,
              asn_sanomat.hinta,
              asn_sanomat.keikkarivinhinta,
              asn_sanomat.tunnus,
              toimi.asn_sanomat
              FROM asn_sanomat
              JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
              WHERE asn_sanomat.yhtio    = '{$kukarow['yhtio']}'
              AND asn_sanomat.asn_numero LIKE '%{$lasku}'
              AND asn_sanomat.laji       = '{$lajilisa}'
              #AND asn_sanomat.tilausrivi != ''
              ORDER BY asn_sanomat.tuoteno ASC";
    $result = pupe_query($query);

    $ok = $virhe = 0;

    while ($row = mysqli_fetch_assoc($result)) {

      $query = "SELECT tuote.tuoteno
                FROM tuotteen_toimittajat
                JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
                WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.toim_tuoteno IN ('{$row['toim_tuoteno']}', '{$row['toim_tuoteno2']}')
                AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}'";
      $res = pupe_query($query);

      if (mysqli_num_rows($res) > 0 and $row['tuoteno'] == '') {
        $ttrow = mysqli_fetch_assoc($res);

        $row['tuoteno'] = $ttrow['tuoteno'];

        $query = "SELECT nimitys
                  FROM tuote
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND tuoteno  = '{$ttrow['tuoteno']}'
                  AND status  != 'P'";
        $tres = pupe_query($query);
        $trow = mysqli_fetch_assoc($tres);

        $row['nimitys'] = $trow['nimitys'];
      }

      if (!is_null($row['tilausrivi']) and !empty($row['tilausrivi'])) {
        $query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$row['tilausrivi']})";
        $hinta_chk_res = pupe_query($query);
        $hinta_chk_row = mysqli_fetch_assoc($hinta_chk_res);

        $row['tilausnumero'] = $hinta_chk_row['otunnus'];
      }

      echo "<tr>";
      echo "<td align='right'>{$row['toimittajanumero']}</td>";
      echo "<td align='right'>{$row['tilausnumero']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['toim_tuoteno']}</td>";
      echo "<td>{$row['nimitys']}</td>";
      echo "<td align='right'>{$row['tilausrivinpositio']}</td>";
      echo "<td align='right'>{$row['kappalemaara']}</td>";
      echo "<td align='right'>", hintapyoristys($row['hinta']), "</td>";

      echo "<td>";

      if ($row['status'] == 'E') {
        echo "<font class='message'>", t("Erolistalla"), "</font>";
      }
      elseif (!is_null($row['tilausrivi']) and !empty($row['tilausrivi'])) {
        echo "<font class='ok'>Ok</font>";
        $ok++;

        if ($row['hinta'] != $hinta_chk_row['hinta']) {
          echo "<br /><font class='error'>", t("Hintapoikkeavuus"), "</font>";
        }
      }
      else {
        echo "<font class='error'>", t("Virhe"), "</font>";
        $virhe++;
      }
      echo "</td>";

      echo "<td class='back'>";

      if ((is_null($row['tilausrivi']) or empty($row['tilausrivi']) ) and $row['status'] != 'E') {
        echo "<input type='button' class='etsibutton_osto' id='{$lasku}##{$row['tuoteno']}##{$row['tilausrivinpositio']}##{$row['toimittajanumero']}##{$row['kappalemaara']}##{$row['tunnus']}##{$row['tilausnumero']}##{$row['toim_tuoteno']}##{$toimipaikka}' value='", t("Etsi"), "' />";

        if ($row['hinta'] == 0) {
          echo "<input type='button' class='erobutton_osto' id='{$lasku}##{$row['tuoteno']}##{$row['tilausrivinpositio']}##{$row['toimittajanumero']}##{$row['kappalemaara']}##{$row['tunnus']}##{$row['tilausnumero']}##{$row['toim_tuoteno']}' value='", t("Erolistalle"), "' />";
        }
      }

      echo "</td>";
      echo "</tr>";
    }

    if ($ok and !$virhe) {
      echo "<tr><th colspan='9'><input type='button' class='vahvistabutton' value='", t("Vahvista"), "' /></th></tr>";
    }

    echo "<tr><th colspan='9'><input type='button' class='vahvistavakisinbutton' value='", t("Aja automaattikohdistus uudestaan kaikille riveille"), "' /></th></tr>";

    if ($laskurow['tunnus'] != '') echo "<tr><th colspan='9'>", ebid($laskurow['tunnus']), "</th></tr>";

    echo "</table>";
    echo "</form>";
  }
}

if ($tee == '') {

  if (isset($nayta_asn)) {
    $valitse = "asn";
  }
  elseif (isset($nayta_ostolasku)) {
    $valitse = "ostolasku";
  }
  elseif (isset($nayta_maventa)) {
    $valitse = "maventa";
  }
  elseif (isset($nayta_finvoice)) {
    $valitse = "finvoice";
  }

  if ($valitse == '') {
    echo "<form method='post' action='?tee='>";
    echo "<table>";

    $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']);

    if (mysqli_num_rows($toimipaikat_res) != 0) {

      echo "<tr>";
      echo "<th>", t("Toimipaikka"), "<br />(Finvoice, Maventa, ", t("Ostolaskut"), ")</th>";
      echo "<td colspan='4'>";
      echo "<select name='toimipaikka'>";
      echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
      echo "<option value='0'>", t("Ei toimipaikkaa"), "</option>";

      while ($toimipaikat_row = mysqli_fetch_assoc($toimipaikat_res)) {

        $sel = '';

        if ($kukarow['toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }

        echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
      }

      echo "</select>";
      echo "</td>";
      echo "</tr>";
    }

    echo "<tr>";
    echo "<th>", t("Valitse"), "</th>";
    echo "<td><input type='submit' name='nayta_finvoice' value='", t("Finvoice"), "' /></td>";
    echo "<td><input type='submit' name='nayta_maventa' value='", t("Maventa"), "' /></td>";
    echo "<td><input type='submit' name='nayta_ostolasku' value='", t("Ostolaskut"), "' /></td>";
    echo "<td><input type='submit' name='nayta_asn' value='", t("ASN-sanomat"), "' /></td>";
    echo "</tr></table>";
    echo "</form>";
  }
  elseif ($valitse == 'asn') {

    $query = "SELECT toimi.ytunnus,
              toimi.nimi,
              toimi.nimitark,
              toimi.osoite,
              toimi.osoitetark,
              toimi.postino,
              toimi.postitp,
              toimi.maa,
              toimi.swift,
              asn_sanomat.saapumispvm,
              asn_sanomat.asn_numero,
              asn_sanomat.paketintunniste,
              asn_sanomat.toimittajanumero,
              asn_sanomat.status,
              count(asn_sanomat.tunnus) AS rivit,
              sum(if((asn_sanomat.tilausrivi is NULL or asn_sanomat.tilausrivi = ''), 0, 1)) AS ok
              FROM asn_sanomat
              JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero and toimi.tyyppi !='P')
              WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
              AND asn_sanomat.laji    = 'asn'
              AND asn_sanomat.status  NOT IN ('X', 'E', 'D')
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14
              HAVING count(asn_sanomat.tunnus) != sum(if((asn_sanomat.tilausrivi is NULL or asn_sanomat.tilausrivi = ''), 0, 1))
              ORDER BY asn_sanomat.asn_numero, asn_sanomat.paketintunniste";
    $result = pupe_query($query);

    echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse=asn//toimipaikka={$toimipaikka}&tee=' id='formi'>";
    echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
    echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
    echo "<input type='hidden' id='kolli' name='kolli' value='' />";
    echo "<input type='hidden' id='toimipaikka' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Ytunnus"), "</th>";
    echo "<th>", t("Nimi"), "</th>";
    echo "<th>", t("Osoite"), "</th>";
    echo "<th>", t("Swift"), "</th>";
    echo "<th>", t("ASN sanomanumero"), "</th>";
    echo "<th>", t("ASN kollinumero"), "</th>";
    echo "<th>", t("Saapumispvm"), "</th>";
    echo "<th>", t("Rivimäärä"), "<br />", t("ok"), " / ", t("kaikki"), "</th>";
    echo "</tr>";

    $ed_asn = '';
    $ed_toimittaja = '';
    $naytetaanko_toimittajabutton = true;
    $asn_numerot = array();

    while ($row = mysqli_fetch_assoc($result)) {
      $naytetaanko_toimittajabutton = true;

      $asn_numerot[$row['asn_numero']] = $row['asn_numero'];

      if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

        if ($naytetaanko_toimittajabutton) {
          //asn_numerot array:ssa on tässä kohtaa yks value liikaa, joka ei kuulu tälle toimittajalle. poisettaan se arraystä
          $seuraavaan_asn_array = array_pop($asn_numerot);

          echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='".implode(',', $asn_numerot)."' value='", t("Vaihda toimittajaa"), "' />";

          foreach ($asn_numerot as $pasn) {
            echo "<span style='float:right;'><input type='button' class='poistabutton' id='{$pasn}' value='", t("Poista sanoma"), ": $pasn' /></span> ";
          }

          echo "</th></tr>";

          $asn_numerot = array();
          $asn_numerot[$seuraavaan_asn_array] = $seuraavaan_asn_array;
        }

        echo "<tr><td colspan='8' class='back'>&nbsp;</td></tr>";
      }

      echo "<tr>";
      echo "<td>{$row['ytunnus']}</td>";

      echo "<td>{$row['nimi']}";
      if (trim($row['nimitark']) != '') echo " {$row['nimitark']}";
      echo "</td>";

      echo "<td>{$row['osoite']} ";
      if (trim($row['osoitetark']) != '') echo "{$row['osoitetark']} ";
      echo "{$row['postino']} {$row['postitp']} {$row['maa']}</td>";

      echo "<td>{$row['swift']}</td>";
      echo "<td align='right'>{$row['asn_numero']}</td>";
      echo "<td>{$row['paketintunniste']}</td>";
      echo "<td>".tv1dateconv($row['saapumispvm'])."</td>";
      echo "<td>{$row['ok']} / {$row['rivit']}</td>";

      echo "<td class='back'>";
      echo "<input type='button' class='kollibutton' id='{$row['paketintunniste']}##{$row['asn_numero']}##{$row['toimittajanumero']}' value='", t("Valitse"), "' />";
      echo "</td>";

      echo "</tr>";

      if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
        $naytetaanko_toimittajabutton = false;
      }

      $ed_asn = $row['asn_numero'];
      $ed_toimittaja = $row['toimittajanumero'];
    }

    if (mysqli_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
      echo "<tr><th colspan='8'><input type='button' class='toimittajabutton' id='".implode(',', $asn_numerot)."' value='", t("Vaihda toimittajaa"), "' />";

      foreach ($asn_numerot as $pasn) {
        echo "<span style='float:right;'><input type='button' class='poistabutton' id='{$pasn}' value='", t("Poista sanoma"), ": $pasn' /></span> ";
      }

      echo "</th></tr>";
    }

    echo "</table>";
    echo "</form>";
  }
  elseif (in_array($valitse, array('ostolasku', 'maventa', 'finvoice'))) {

    if ($valitse == 'maventa') {
      $lajilisa = 'mav';
    }
    elseif ($valitse == 'finvoice') {
      $lajilisa = 'fin';
    }
    else {
      $lajilisa = 'tec';
    }

    $toimipaikkalisa = (isset($toimipaikka) and trim($toimipaikka) != "kaikki") ? " AND lasku.yhtio_toimipaikka = '{$toimipaikka}' " : "";

    $query = "SELECT toimi.ytunnus,
              toimi.nimi,
              toimi.nimitark,
              toimi.osoite,
              toimi.osoitetark,
              toimi.postino,
              toimi.postitp,
              toimi.maa,
              toimi.swift,
              asn_sanomat.saapumispvm,
              asn_sanomat.asn_numero as tilausnumero,
              asn_sanomat.paketintunniste,
              asn_sanomat.toimittajanumero,
              count(asn_sanomat.tunnus) AS rivit,
              sum(if((asn_sanomat.tilausrivi is NULL or asn_sanomat.tilausrivi = ''), 0, 1)) AS ok
              FROM asn_sanomat
              JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero AND toimi.tyyppi != 'P')
              JOIN lasku ON (lasku.yhtio = asn_sanomat.yhtio AND lasku.laskunro = asn_sanomat.asn_numero AND vienti in ('B', 'C', 'E', 'F', 'H', 'I') AND lasku.liitostunnus = toimi.tunnus {$toimipaikkalisa})
              WHERE asn_sanomat.yhtio           = '{$kukarow['yhtio']}'
              AND asn_sanomat.laji              = '{$lajilisa}'
              AND asn_sanomat.status            NOT IN ('X', 'E', 'D')
              AND asn_sanomat.toimittajanumero != ''
              GROUP BY asn_sanomat.asn_numero, asn_sanomat.toimittajanumero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
              ORDER BY toimi.nimi, toimi.ytunnus, asn_sanomat.saapumispvm, asn_sanomat.asn_numero";
    $result = pupe_query($query);

    echo "<form method='post' action='?lopetus={$PHP_SELF}////valitse={$valitse}//toimipaikka={$toimipaikka}&tee=' id='formi'>";
    echo "<input type='hidden' id='tee' name='tee' value='nayta' />";
    echo "<input type='hidden' id='valitse' name='valitse' value='{$valitse}' />";
    echo "<input type='hidden' id='lasku' name='lasku' value='' />";
    echo "<input type='hidden' id='toimipaikka' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Ytunnus"), "</th>";
    echo "<th>", t("Nimi"), "</th>";
    echo "<th>", t("Osoite"), "</th>";
    echo "<th>", t("Swift"), "</th>";
    echo "<th>", t("Ostolaskunro"), "</th>";
    echo "<th>", t("Saapumispvm"), "</th>";
    echo "<th>", t("Rivimäärä"), "<br />", t("ok"), " / ", t("kaikki"), "</th>";
    echo "</tr>";

    $ed_toimittaja = '';
    $ed_tilausnumero = '';
    $naytetaanko_toimittajabutton = false;

    while ($row = mysqli_fetch_assoc($result)) {

      if ($ed_toimittaja != '' and $ed_toimittaja != $row['toimittajanumero']) {

        echo "<tr><th colspan='7'>";

        if ($naytetaanko_toimittajabutton) {
          echo "<input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='", t("Vaihda toimittajaa"), "' />&nbsp;";
        }

        echo "</th></tr>";
        echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";
      }

      echo "<tr>";
      echo "<td>{$row['ytunnus']}</td>";
      echo "<td>{$row['nimi']}</td>";
      echo "<td>{$row['osoite']} {$row['postino']} {$row['postitp']} {$row['maa']}</td>";
      echo "<td>{$row['swift']}</td>";
      echo "<td>{$row['tilausnumero']}</td>";
      echo "<td>".tv1dateconv($row['saapumispvm'])."</td>";
      echo "<td>{$row['ok']} / {$row['rivit']}</td>";
      echo "<td class='back'>";
      echo "<input type='button' class='ostolaskubutton' id='{$row['tilausnumero']}' value='", t("Valitse"), "' />";
      echo "<span style='float:right;'><input type='button' class='poistabutton' id='{$row['tilausnumero']}' value='", t("Poista"), "' /></span>";
      echo "</td>";
      echo "</tr>";

      if (($ed_toimittaja == '' or $ed_toimittaja == $row['toimittajanumero']) and $row['ok'] == $row['rivit']) {
        $naytetaanko_toimittajabutton = false;
      }

      $ed_toimittaja = $row['toimittajanumero'];
      $ed_tilausnumero = $row['tilausnumero'];
    }

    echo "<tr><th colspan='7'>";

    if (mysqli_num_rows($result) > 0 and $naytetaanko_toimittajabutton) {
      echo "<input type='button' class='toimittajabutton' id='{$ed_tilausnumero}' value='", t("Vaihda toimittajaa"), "' />&nbsp;";
    }

    echo "</th></tr>";

    echo "</table>";
    echo "</form>";
  }
}

require "inc/footer.inc";
