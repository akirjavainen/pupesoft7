<?php

function mycf_toimita_tilaus($params) {
  global $mycf_url, $mycf_webhooks_key;

  // jos MyCashflow API ei käytössä
  if (empty($mycf_url)
      or empty($mycf_webhooks_key)) {
    return false;
  }

  // parametreinä pupesoft tunnukset array sekä seurantakoodit string
  $pupesoft_tunnukset = $params["pupesoft_tunnukset"];
  $tracking_code = $params["tracking_code"];

  // haetaan tilausten woo tilausnumerot
  $mycf_tilausnumerot = mycf_hae_tilausnumerot($pupesoft_tunnukset);

  foreach ($mycf_tilausnumerot as $order_number) {

    // Webhooks osoite
    $url = "{$mycf_url}/webhooks/process";

    // Lisätään julkinen kommentti
    $data = array("action" => "add_comment",
                  "message" => "Tilauksesi on toimitettu. Seurantakoodi: $tracking_code.",
                  "order" => $order_number,
                  "public_message" => 1,
                  "key" => $mycf_webhooks_key);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);

    // Päivitetään toimitetuksi
    $data = array("action" => "deliver",
                  "order" => $order_number,
                  "tracking_code" => $tracking_code,
                  "send_email" => 1,
                  "key" => $mycf_webhooks_key);

    // Webhooks-call
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);
  }
}

function mycf_hae_tilausnumerot($pupesoft_tunnukset) {
  global $kukarow, $yhtiorow;

  $response = array();

  // jos meillä ei ole yhtään tilausnumeroita
  if (empty($pupesoft_tunnukset) or !is_array($pupesoft_tunnukset)) {
    return $response;
  }

  // tehdään tunnuksista stringi
  $tunnukset = implode(",", $pupesoft_tunnukset);

  pupesoft_log("mycf_toimita_tilaus", "Päivitetään tilaukset $tunnukset toimitetuiksi.");

  // tehdään request tilaus kerrallaan
  // ainoastaan jos moduli on magento ja asiakkaan tilausnumero löytyy
  $query = "SELECT asiakkaan_tilausnumero
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus in ({$tunnukset})
            AND ohjelma_moduli = 'MAGENTO'
            AND asiakkaan_tilausnumero  != ''";
  $result = pupe_query($query);

  while ($row = mysqli_fetch_assoc($result)) {
    $response[] = $row['asiakkaan_tilausnumero'];
  }

  return $response;
}
