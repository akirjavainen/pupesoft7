<?php

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen järjestelmään"), "</font><hr><br />";

if (empty($ulkoinen_jarjestelma)) {
  echo "<form action='' method='post'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Valitse ulkoinen järjestelmä"), "</th>";
  echo "<td>";
  echo "<select name='ulkoinen_jarjestelma'>";
  echo "<option value='P'>PostNord</option>";
  echo "<option value='L'>Velox</option>";
  echo "</select>";
  echo "</td>";
  echo "<td>";
  echo "<button type='submit' name='tee' value=''>", t("Lähetä"), "</button>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  require "inc/footer.inc";
  exit;
}

$logmaster_itemnumberfield = logmaster_field('ItemNumber');
$logmaster_prodgroup1field = logmaster_field('ProdGroup1');
$logmaster_prodgroup2field = logmaster_field('ProdGroup2');

$query = "SELECT tuote.*,
          ta.selite AS synkronointi,
          ta.tunnus AS ta_tunnus,
          ta_bbd.selite AS best_before_flag,
          tt.toim_tuoteno
          FROM tuote
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
          LEFT JOIN tuotteen_avainsanat AS ta_bbd ON (ta_bbd.yhtio = tuote.yhtio AND ta_bbd.tuoteno = tuote.tuoteno AND ta_bbd.laji = 'parametri_BBD')
          LEFT JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuote.yhtio AND tt.tuoteno = tuote.tuoteno)
          WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
          AND tuote.ei_saldoa = ''
          AND tuote.tuotetyyppi NOT IN ('A', 'B')
          AND tuote.{$logmaster_itemnumberfield} != ''
          GROUP BY tuoteno
          HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                  # jos avainsanaa ei ole olemassa ja status P niin ei haluta näitä tuotteita jatkossakaan
                 (ta.tunnus IS NULL AND tuote.status != 'P') OR
                 # paitsi jos kyseessä Velox niin siirretään
                 (ta.tunnus IS NULL AND '{$ulkoinen_jarjestelma}' = 'L')";
$res = pupe_query($query);

if (mysqli_num_rows($res) > 0) {

  if ($tee == '') {
    echo "<font class='message'>", t("Tuotteet joita ei ole synkronoitu"), "</font><br />";
    echo "<font class='message'>", t("Yhteensä %d kappaletta", "", mysqli_num_rows($res)), "</font><br /><br />";

    echo "<form action='' method='post'>";
    echo "<input type='hidden' name='ulkoinen_jarjestelma' value='{$ulkoinen_jarjestelma}' />";
    echo "<table>";
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("Lähetä"), "' />";
    echo "</td></tr>";
    echo "<tr>";
    echo "<th>", t("Tuotenumero"), "</th>";
    echo "<th>", t("Nimitys"), "</th>";
    echo "</tr>";
  }
  else {
    if ($ulkoinen_jarjestelma == 'L') {
      $uj_nimi = "Velox";
    }
    else {
      $uj_nimi = "PostNord";
    }

    $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Message></Message>");

    $messageheader = $xml->addChild('MessageHeader');
    $messageheader->addChild('MessageType', 'MaterialMaster');
    $messageheader->addChild('Sender',      xml_cleanstring($yhtiorow['nimi']));
    $messageheader->addChild('Receiver',    $uj_nimi);

    $iteminformation = $xml->addChild('ItemInformation');
    $iteminformation->addChild('TransDate', date('d-m-Y'));
    $iteminformation->addChild('TransTime', date('H:i:s'));

    $items = $iteminformation->addChild('Items');

    $i = 1;
  }

  while ($row = mysqli_fetch_assoc($res)) {

    if ($tee == '') {
      echo "<tr>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['nimitys']}</td>";
      echo "</tr>";
    }
    else {
      // statuskoodi
      switch ($row['status']) {
      case 'A':
        $status = 1;
        break;
      case 'P':
        $status = 9;
        break;
      default:
        $status = 0;
        break;
      }

      // tyyppi
      if (!is_null($row['synkronointi']) and $row['synkronointi'] == '') {
        $type = 'M';
      }
      else {
        $type = 'U';
      }

      $line = $items->addChild('Line');
      $line->addAttribute('No', $i);
      $line->addChild('Type',                  $type);
      if ($uj_nimi == "Velox") {
        $line->addChild('ItemNumber',          xml_cleanstring($row[$logmaster_itemnumberfield], 32));
      }
      else {
        $line->addChild('ItemNumber',          xml_cleanstring($row[$logmaster_itemnumberfield], 20));
      }
      $line->addChild('ItemName',              xml_cleanstring($row['nimitys'], 50));
      $line->addChild('ProdGroup1',            xml_cleanstring($row[$logmaster_prodgroup1field], 6));
      $line->addChild('ProdGroup2',            xml_cleanstring($row[$logmaster_prodgroup2field], 6));
      $line->addChild('SalesPrice',            '');
      $line->addChild('Unit1',                 xml_cleanstring($row['yksikko'], 10));
      $line->addChild('Unit2',                 '');
      $line->addChild('Relation',              '');
      $line->addChild('Weight',                round($row['tuotemassa'], 3));
      $line->addChild('NetWeight',             '');
      $line->addChild('Volume',                '');
      $line->addChild('Height',                '');
      $line->addChild('Width',                 '');
      $line->addChild('Length',                '');
      $line->addChild('PackageSize',           '');
      $line->addChild('PalletSize',            '');
      $line->addChild('Status',                $status);
      $line->addChild('WholesalePackageSize',  '');
      $line->addChild('EANCode',               xml_cleanstring($row['eankoodi'], 20));
      $line->addChild('EANCode2',              '');
      $line->addChild('CustomsTariffNum',      xml_cleanstring($row['tullinimike1'], 14));
      if ($uj_nimi == "Velox") {
        $line->addChild('CustomsTariffTreat',  xml_cleanstring($row['tullikohtelu'], 4));
        $line->addChild('Brand',               xml_cleanstring($row['tuotemerkki'], 30));
        $line->addChild('ProviderNum',         xml_cleanstring($row['toim_tuoteno'], 30));
      }
      if ($uj_nimi == "PostNord") {
        $line->addChild('AlarmLimit',            $row['halytysraja']);
      }
      else {
        $line->addChild('AlarmLimit',          '');
      }
      $line->addChild('QualPeriod1',           '');
      $line->addChild('QualPeriod2',           '');
      $line->addChild('QualPeriod3',           '');
      $line->addChild('FactoryNum',            '');
      $line->addChild('UNCode',                '');

      // Dest before date seuranta päällä vai ei
      $BBDateCollect = '';
      if (!empty($row['best_before_flag'])) {
        $BBDateCollect = '1';
      }

      $line->addChild('BBDateCollect',         $BBDateCollect);
      $line->addChild('SerialNumbers',         '');
      $line->addChild('SerialNumInArrival',    '');
      $line->addChild('TaxCode',               '');
      $line->addChild('CountryofOrigin',       '');
      $line->addChild('PlatformQuantity',      '');
      $line->addChild('PlatformType',          '');
      $line->addChild('PurchasePrice',         '');
      $line->addChild('ConsumerPrice',         '');
      $line->addChild('OperRecommendation',    '');
      $line->addChild('FreeText',              xml_cleanstring($row['tuoteno'], 100));
      $line->addChild('PurchaseUnit',          '');
      $line->addChild('ManufactItemNum',       '');
      $line->addChild('InternationalItemNum',  '');
      $line->addChild('Flashpoint',            '');
      $line->addChild('SalesCurrency',         '');
      $line->addChild('PurchaseCurrency',      '');
      $line->addChild('Model',                 '');
      $line->addChild('ModelOrder',            '');
      $line->addChild('TransportTemperature',  '');

      if (is_null($row['synkronointi'])) {
        $query = "INSERT INTO tuotteen_avainsanat SET
                  yhtio      = '{$kukarow['yhtio']}',
                  tuoteno    = '{$row['tuoteno']}',
                  kieli      = '{$yhtiorow['kieli']}',
                  laji       = 'synkronointi',
                  selite     = 'x',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now(),
                  muutospvm  = now(),
                  muuttaja   = '{$kukarow['kuka']}'";
        pupe_query($query);
      }
      else {
        $query = "UPDATE tuotteen_avainsanat SET
                  selite      = 'x'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$row['tuoteno']}'
                  AND laji    = 'synkronointi'";
        pupe_query($query);
      }

      $i++;
    }
  }

  if ($tee == '') {
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("Lähetä"), "' />";
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
  }
  else {
    $_name = substr("tuote_".md5(uniqid()), 0, 25);
    $filename = $pupe_root_polku."/dataout/{$_name}.xml";

    if (file_put_contents($filename, $xml->asXML())) {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";

      $palautus = logmaster_send_file($filename);

      if ($palautus == 0) {
        pupesoft_log('logmaster_synchronize_products', "Siirretiin synkronointitiedosto {$_name}.xml.");
      }
      else {
        pupesoft_log('logmaster_synchronize_products', "Synkronointitiedoston {$_name}.xml siirtäminen epäonnistui.");
      }
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti epäonnistui"), "</font><br />";
    }
  }
}
else {
  echo "<font class='message'>", t("Kaikki tuotteet ovat synkronoitu"), "</font><br />";
}

require "inc/footer.inc";
