// MUOKKAUS: Uusi tiedosto - asiakkaan nimen samankaltaisuusvaroitus.
//
// Ei koskaan estä lomakkeen tallennusta. Kun käyttäjä poistuu "Nimi"
// -kentästä asiakaslomakkeella, kysytään palvelimelta löytyykö
// tietokannasta jo samankaltaisia asiakkaita, ja näytetään ne varoituksena
// kentän alapuolella. Kentän tunnistus perustuu id:hen "asiakas_nimi_kentta"
// (ks. yllapito.php, kentän piirto), joten tämä skripti ei tee mitään millään
// muulla sivulla (esim. asiakashaku/lista -näkymä).

(function() {

  function kaynnista() {
    var kentta = document.getElementById("asiakas_nimi_kentta");

    if (!kentta) {
      return;
    }

    var varoitusDiv = document.getElementById("asiakas_nimi_varoitus");

    if (!varoitusDiv) {
      varoitusDiv = document.createElement("div");
      varoitusDiv.id = "asiakas_nimi_varoitus";
      kentta.parentNode.appendChild(varoitusDiv);
    }

    var ajastin = null;

    kentta.addEventListener("blur", function() {
      var nimi = kentta.value;

      if (ajastin) {
        clearTimeout(ajastin);
      }

      if (nimi.replace(/^\s+|\s+$/g, "").length < 3) {
        varoitusDiv.innerHTML = "";
        return;
      }

      // Pieni viive, jotta esim. "Tallenna"-painikkeen klikkaus (joka
      // myös vie fokuksen pois kentästä) ehtii käynnistyä ensin.
      ajastin = setTimeout(function() {
        ajaxPost(
          "mainform",
          "?haku=asiakas_nimi_samankaltaiset&ohje=off&no_head=yes&nimi_ehdotus=" + encodeURIComponent(nimi),
          "asiakas_nimi_varoitus",
          false,
          false
        );
      }, 200);
    });
  }

  if (document.readyState == "loading") {
    document.addEventListener("DOMContentLoaded", kaynnista);
  }
  else {
    kaynnista();
  }

})();
