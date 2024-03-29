<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiö!!!\n";
    die;
  }

  if (!isset($argv[2]) or $argv[2] == '') {
    echo "Anna tiedosto!!!\n";
    die;
  }

  date_default_timezone_set('Europe/Helsinki');

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";
  require "tilauskasittely/tilauksesta_varastosiirto.inc";

  $kukarow['yhtio'] = $argv[1];
  $filu             = $argv[2];

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(dirname(__FILE__)));

  $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

  if ($yhtiorow["yhtio"] == "") {
    die ("Yhtiö $kukarow[yhtio] ei löydy!");
  }

  $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

  $tee = 'aja';
}
else {
  require "../../inc/parametrit.inc";
  require "tilauskasittely/tilauksesta_varastosiirto.inc";

  echo "<font class='head'>".t("Relex-varastosiirron sisäänluku")."</font><hr>";

  if (isset($tee) and trim($tee) == 'aja') {
    if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
      if ($_FILES['userfile']['size'] == 0) {
        die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
      }
      else {
        $filu = $_FILES['userfile']['tmp_name'];
      }
    }
  }
}

if (isset($tee) and trim($tee) == 'aja') {
  /* Tiedostomuoto:
    COLUMN        TYPE  COMMENT
    order_number  INT    Row level unique order number
    product_code  TXT    Identifier of the product/item
    quantity      FLOAT  Number of products to be ordered
    order_type    TXT    NORMAL, EXTRA ORDER, ALLOCATION, ORDER IN ADVANCE, INITIAL REPLENISHMENT
    location_code  TXT    Identifier for location/warehouse
    supplier_code  TXT    Supplier code for proposed item
    delivery_date  DATE  Expected delivery date for order
    comment_1      TXT    Comment or identifier set in RELEX user interface
    comment_2      TXT    Comment or identifier set in RELEX user interface
    comment_3      TXT    Comment or identifier set in RELEX user interface
    comment_4      TXT    Comment or identifier set in RELEX user interface
    comment_5      TXT    Comment or identifier set in RELEX user interface
    order_date    DATE  When should the item be ordered
  */

  $rivit = file($filu);

  // Heitetään eka rivi roskiin
  array_shift($rivit);

  foreach ($rivit as $line) {
    $fields = explode(";", $line);

    $order_number  = pupesoft_cleanstring($fields[0]);
    $product_code  = pupesoft_cleanstring($fields[1]);
    $quantity      = pupesoft_cleannumber($fields[2]);
    $order_type    = pupesoft_cleanstring($fields[3]);
    $location_code = pupesoft_cleanstring($fields[4]);
    $supplier_code = pupesoft_cleanstring($fields[5]);
    $delivery_date = pupesoft_cleanstring($fields[6]);
    $comment_1     = pupesoft_cleanstring($fields[7]);
    $comment_2     = pupesoft_cleanstring($fields[8]);
    $comment_3     = pupesoft_cleanstring($fields[9]);
    $comment_4     = pupesoft_cleanstring($fields[10]);
    $comment_5     = pupesoft_cleanstring($fields[11]);
    $order_date    = pupesoft_cleanstring($fields[12]);

    // Poistetaan maa-etuliitteet
    $product_code  = mb_substr($product_code, 3);
    $location_code = mb_substr($location_code, 3);
    $supplier_code = mb_substr($supplier_code, 3);

    // Haetaan tuotteen tiedot
    $query = "SELECT tuoteno
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '{$product_code}'";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) == 1) {
      $tuote = mysqli_fetch_assoc($result);
    }
    else {
      echo "Tuotetta '$product_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    // Haetaan lähdevaraston tiedot
    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '{$supplier_code}'";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) == 1) {
      $lahde = mysqli_fetch_assoc($result);
    }
    else {
      echo "Lähdevarastoa '$supplier_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    // Haetaan kohdevaraston tiedot
    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '{$location_code}'";
    $result = pupe_query($query);

    if (mysqli_num_rows($result) == 1) {
      $kohde = mysqli_fetch_assoc($result);
    }
    else {
      echo "Varastoa '$location_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    if (strtotime($order_date) < time()) {
      $ehdotus_pvm = date("Y-m-d");
    }
    else {
      $ehdotus_pvm = $order_date;
    }

    // Löytyykö sopiva tilaus?
    $varastosiirto = hae_avoin_varastosiirto($lahde["tunnus"], $kohde["tunnus"], "G");

    // Ei löydy, tehdään uus siirto
    if (empty($varastosiirto)) {

      aseta_kukarow_kesken(0);

      $ttqry = "SELECT tpa.tunnus
                FROM avainsana AS ana
                JOIN toimitustapa AS tpa
                ON ( ana.yhtio = tpa.yhtio AND ana.selitetark_2 = tpa.tunnus )
                WHERE ana.yhtio    = '$kukarow[yhtio]'
                AND ana.laji       = 'SIIRTOVARASTOT'
                AND ana.selite     = '{$lahde["tunnus"]}'
                AND ana.selitetark = '{$kohde["tunnus"]}'";
      $ttresult = pupe_query($ttqry);

      if (mysqli_num_rows($ttresult) > 0) {
        $ttrow = mysqli_fetch_assoc($ttresult);
        $def_toimitustapa = $ttrow['tunnus'];
      }
      else {
        $def_toimitustapa = '';
      }

      $varastosiirto = luo_varastosiirto($lahde["tunnus"], $kohde["tunnus"], $def_toimitustapa, "", "G");
    }

    aseta_kukarow_kesken($varastosiirto['tunnus']);

    $tilausrivi = array(
      "tuoteno"             => $tuote["tuoteno"],
      "varattu"             => $quantity,
      "kohdevarasto_tunnus" => $kohde["tunnus"],
    );

    // halutaan lisätä tuoteperheen lapsetkin siirtolistalle
    $perhekielto = "";

    $lisatyt_rivit1 = luo_varastosiirtorivi($varastosiirto, $tilausrivi, $lahde["tunnus"], "G", $perhekielto);

    echo "Lisätään tuote {$tuote["tuoteno"]} $quantity {$tuote["yksikko"]} siirtolistalle {$varastosiirto["tunnus"]}.<br>";
  }

  aseta_kukarow_kesken(0);

}

if (!$php_cli) {
  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='aja'>";
  echo "<br><table>
        <tr><th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='".t("Käsittele")."'></td>
      </tr>
      </table>
      </form>";

  require "inc/footer.inc";
}
