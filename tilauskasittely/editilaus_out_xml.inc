<?php

$ostotilaus = hae_lasku($laskurow['tunnus']);
$ostotilaus['tilausrivit'] = hae_laskun_tilausrivit($laskurow['tunnus'], array('O'), generoi_sorttauskentta($yhtiorow["ostotilauksen_jarjestys"]), $yhtiorow['ostotilauksen_jarjestys_suunta']);

if (empty($ostotilaus['tilausrivit'])) {
  echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font><br/>";
}
else {
  $xml_editilaus = $pupe_root_polku."/dataout/xml-order-$laskurow[tunnus].xml";

  $xml = new DOMDocument();
  $order = $xml->createElement('Order');

  $order_type = $xml->createElement('orderType');
  $order_type->nodeValue = 8;
  $order->appendChild($order_type);

  $shipping = $xml->createElement('shipping');
  $shipping->nodeValue = 610;
  $order->appendChild($shipping);

  $sales_order = $xml->createElement('salesOrder');
  $sales_order->nodeValue = $ostotilaus['tunnus'];
  $order->appendChild($sales_order);

  $address_for_shipment = $xml->createElement('addressForShipment');
  $address_for_shipment->nodeValue = "802005050 100002184";
  $order->appendChild($address_for_shipment);

  $positions = $xml->createElement('Positions');

  foreach ($ostotilaus['tilausrivit'] as $tilausrivi) {
    $article = $xml->createElement('article');

    $articleno = $xml->createElement('articleNo');
    $articleno->nodeValue = $tilausrivi['tuoteno'];

    $article_quantity = $xml->createElement('articleQuantity');
    $article_quantity->nodeValue = $tilausrivi['tilkpl'];

    $article->appendChild($articleno);
    $article->appendChild($article_quantity);

    $positions->appendChild($article);
  }

  $order->appendChild($positions);

  $xml->appendChild($order);

  $xml->save($xml_editilaus);

  echo "<table>";
  echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
  echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t('Osto')."_{$laskurow["tunnus"]}.xml'>";
  echo "<input type='hidden' name='filenimi' value='$xml_editilaus'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";
