<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 2;

if (mysqli_field_name($result, $i) == "yhtio" or
  mysqli_field_name($result, $i) == "pankki_haltija" or
  mysqli_field_name($result, $i) == "tilinumero" or
  mysqli_field_name($result, $i) == "swift" or
  mysqli_field_name($result, $i) == "pankki1" or
  mysqli_field_name($result, $i) == "pankki2" or
  mysqli_field_name($result, $i) == "pankki3" or
  mysqli_field_name($result, $i) == "pankki4" or
  mysqli_field_name($result, $i) == "ultilno" or
  mysqli_field_name($result, $i) == "maksutyyppi" or
  mysqli_field_name($result, $i) == "suoraveloitus" or
  mysqli_field_name($result, $i) == "maksaja" or
  mysqli_field_name($result, $i) == "maksuaika" or
  mysqli_field_name($result, $i) == "hyvak1" or
  mysqli_field_name($result, $i) == "h1time" or
  mysqli_field_name($result, $i) == "hyvak2" or
  mysqli_field_name($result, $i) == "h2time" or
  mysqli_field_name($result, $i) == "hyvak3" or
  mysqli_field_name($result, $i) == "h3time" or
  mysqli_field_name($result, $i) == "hyvak4" or
  mysqli_field_name($result, $i) == "h4time" or
  mysqli_field_name($result, $i) == "hyvak5" or
  mysqli_field_name($result, $i) == "hyvaksyja_nyt" or
  mysqli_field_name($result, $i) == "h5time" or
  mysqli_field_name($result, $i) == "hyvaksynnanmuutos" or
  mysqli_field_name($result, $i) == "ebid" or
  mysqli_field_name($result, $i) == "popvm" or
  mysqli_field_name($result, $i) == "maksu_kurssi" or
  mysqli_field_name($result, $i) == "maksu_tili" or
  mysqli_field_name($result, $i) == "alv_tili" or
  mysqli_field_name($result, $i) == "tila" or
  mysqli_field_name($result, $i) == "alatila" or
  mysqli_field_name($result, $i) == "huolitsija" or
  mysqli_field_name($result, $i) == "jakelu" or
  mysqli_field_name($result, $i) == "kuljetus" or
  mysqli_field_name($result, $i) == "vakuutus" or
  mysqli_field_name($result, $i) == "viitetxt" or
  mysqli_field_name($result, $i) == "erikoisale" or
  mysqli_field_name($result, $i) == "kerayslista" or
  mysqli_field_name($result, $i) == "kohdistettu" or
  mysqli_field_name($result, $i) == "rahti_huolinta" or
  mysqli_field_name($result, $i) == "rahti" or
  mysqli_field_name($result, $i) == "rahti_etu" or
  mysqli_field_name($result, $i) == "rahti_etu_alv" or
  mysqli_field_name($result, $i) == "pyoristys_erot" or
  mysqli_field_name($result, $i) == "pyoristys_erot_alv" or
  mysqli_field_name($result, $i) == "maa_lahetys" or
  mysqli_field_name($result, $i) == "maa_maara" or
  mysqli_field_name($result, $i) == "maa_alkupera" or
  mysqli_field_name($result, $i) == "kuljetusmuoto" or
  mysqli_field_name($result, $i) == "kauppatapahtuman_luonne" or
  mysqli_field_name($result, $i) == "bruttopaino" or
  mysqli_field_name($result, $i) == "sisamaan_kuljetus" or
  mysqli_field_name($result, $i) == "sisamaan_kuljetus_kansallisuus" or
  mysqli_field_name($result, $i) == "aktiivinen_kuljetus" or
  mysqli_field_name($result, $i) == "kontti" or
  mysqli_field_name($result, $i) == "aktiivinen_kuljetus_kansallisuus" or
  mysqli_field_name($result, $i) == "sisamaan_kuljetusmuoto" or
  mysqli_field_name($result, $i) == "poistumistoimipaikka" or
  mysqli_field_name($result, $i) == "poistumistoimipaikka_koodi" or
  mysqli_field_name($result, $i) == "lisattava_era" or
  mysqli_field_name($result, $i) == "vahennettava_era" or
  mysqli_field_name($result, $i) == "tullausnumero" or
  mysqli_field_name($result, $i) == "vientipaperit_palautettu" or
  mysqli_field_name($result, $i) == "pakkaamo" or
  mysqli_field_name($result, $i) == "jaksotettu" or
  mysqli_field_name($result, $i) == "tunnusnippu" or
  mysqli_field_name($result, $i) == "olmapvm" or
  mysqli_field_name($result, $i) == "varasto" or
  mysqli_field_name($result, $i) == "tulostusalue" or
  mysqli_field_name($result, $i) == "kirjoitin" or
  mysqli_field_name($result, $i) == "vanhatunnus") {

  $tyyppi = 0;
}


if (mysqli_field_name($result, $i) == "nimi" or
  mysqli_field_name($result, $i) == "nimitark" or
  mysqli_field_name($result, $i) == "osoite" or
  mysqli_field_name($result, $i) == "osoitetark" or
  mysqli_field_name($result, $i) == "postino" or
  mysqli_field_name($result, $i) == "postitp" or
  mysqli_field_name($result, $i) == "toim_nimi" or
  mysqli_field_name($result, $i) == "toim_nimitark" or
  mysqli_field_name($result, $i) == "toim_osoite" or
  mysqli_field_name($result, $i) == "toim_osoitetark" or
  mysqli_field_name($result, $i) == "toim_postino" or
  mysqli_field_name($result, $i) == "toim_postitp" or
  mysqli_field_name($result, $i) == "ytunnus" or
  mysqli_field_name($result, $i) == "verkkotunnus" or
  mysqli_field_name($result, $i) == "ovttunnus" or
  mysqli_field_name($result, $i) == "factoringsiirtonumero" or
  mysqli_field_name($result, $i) == "toim_ovttunnus") {

  $tyyppi = 1;
}

if (mysqli_field_name($result, $i) == "toimaika") {
  $tyyppi = 1.5;
}

if (mysqli_field_name($result, $i) == "laskutyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '-9'>".t("Oletus")."</option>";

  foreach (pupesoft_laskutyypit() as $ltyynumero => $ltyyteksti) {
    $ulos .= "<option value = '$ltyynumero' $sel[$ltyynumero]>".t($ltyyteksti)."</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "sisviesti1") {
  $ulos = "<td><textarea cols='45' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "maa" or
  mysqli_field_name($result, $i) == "toim_maa") {

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $ulos .= "<option value = '' ></option>";

  while ($vrow=mysqli_fetch_array($vresult)) {
    $sel="";
    if (mb_strtoupper($trow[$i]) == mb_strtoupper($vrow[0])) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and mb_strtoupper($vrow[0]) == mb_strtoupper($yhtiorow["maa"])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".mb_strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "chn") {

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value='100' $sel[100]>".t("Verkkolasku, tulostuspalvelu")."</option>";
  $ulos .= "<option value='010' $sel[010]>".t("Verkkolasku")."</option>";
  //$ulos .= "<option value='001' $sel[001]>".t("Itella Pupevoice: EDI, sähköinen lasku")."</option>";
  $ulos .= "<option value='020' $sel[020]>".t("Vienti-Verkkolasku (EU)")."</option>";
  $ulos .= "<option value='030' $sel[030]>".t("Vienti-Verkkolasku, tulostuspalvelu (EU)")."</option>";
  $ulos .= "<option value='111' $sel[111]>".t("Itella EDI: EIH-1.4 sähköinen lasku")."</option>";
  $ulos .= "<option value='112' $sel[112]>".t("Pupesoft-Finvoice: Verkkolasku Pupesoftista-Pupesoftiin")."</option>";
  $ulos .= "<option value='666' $sel[666]>".t("Sähköposti")."</option>";
  $ulos .= "<option value='667' $sel[667]>".t("Paperilasku, käsitellään manuaalisesti")."</option>";
  $ulos .= "<option value='999' $sel[999]>".t("Laskutuskielto, laskutusta ei tehdä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysqli_field_name($result, $i) == "tilaustyyppi") {

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value='0' $sel[0]>".t("Ylläpitosopimus")."</option>";
  $ulos .= "<option value='2' $sel[2]>".t("Varastotäydennys")."</option>";
  $ulos .= "<option value='7' $sel[7]>".t("Tehdastilaus")."</option>";
  $ulos .= "<option value='8' $sel[8]>".t("Muiden mukana")."</option>";
  $ulos .= "<option value='9' $sel[9]>".t("Tehdaspalautus")."</option>";
  $ulos .= "<option value='A' $sel[A]>".t("Työmääräys")."</option>";
  $ulos .= "<option value='E' $sel[E]>".t("Ennakkotilaus")."</option>";
  $ulos .= "<option value='M' $sel[M]>".t("Myyntitili")."</option>";
  $ulos .= "<option value='N' $sel[N]>".t("Normaalitilaus")."</option>";
  $ulos .= "<option value='P' $sel[P]>".t("Projekti")."</option>";
  $ulos .= "<option value='R' $sel[R]>".t("Reklamaatio")."</option>";
  $ulos .= "<option value='S' $sel[S]>".t("Sarjatilaus")."</option>";
  $ulos .= "<option value='T' $sel[T]>".t("Tarjoustilaus")."</option>";
  $ulos .= "<option value='V' $sel[V]>".t("Valmistus-Tilaus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
