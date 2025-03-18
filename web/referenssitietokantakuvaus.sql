-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: referenssi
-- ------------------------------------------------------
-- Server version	5.1.73-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abc_aputaulu`
--

DROP TABLE IF EXISTS `abc_aputaulu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abc_aputaulu` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(2) NOT NULL DEFAULT '',
  `luokka` varchar(4) NOT NULL DEFAULT '',
  `luokka_osasto` varchar(4) NOT NULL DEFAULT '',
  `luokka_try` varchar(4) NOT NULL DEFAULT '',
  `luokka_trygroup` varchar(4) NOT NULL DEFAULT '',
  `luokka_tuotemerkki` varchar(4) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `nimitys` varchar(50) NOT NULL DEFAULT '',
  `try` varchar(50) NOT NULL DEFAULT '',
  `tuotemerkki` varchar(30) NOT NULL DEFAULT '',
  `osasto` varchar(50) NOT NULL DEFAULT '',
  `myyjanro` int(11) NOT NULL DEFAULT '0',
  `ostajanro` int(11) NOT NULL DEFAULT '0',
  `malli` varchar(100) NOT NULL DEFAULT '',
  `mallitarkenne` varchar(100) NOT NULL DEFAULT '',
  `status` varchar(10) NOT NULL DEFAULT '',
  `saapumispvm` date NOT NULL DEFAULT '0000-00-00',
  `saldo` int(11) NOT NULL DEFAULT '0',
  `tulopvm` date NOT NULL DEFAULT '0000-00-00',
  `rivia` int(11) NOT NULL DEFAULT '0',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kerrat` int(11) NOT NULL DEFAULT '0',
  `kate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `katepros` decimal(12,2) NOT NULL DEFAULT '0.00',
  `puutekpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `puuterivia` int(11) NOT NULL DEFAULT '0',
  `osto_rivia` int(11) NOT NULL DEFAULT '0',
  `osto_kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `osto_summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `osto_kerrat` int(11) NOT NULL DEFAULT '0',
  `vararvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ostoeranarvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ostoerankpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `palvelutaso` decimal(12,2) NOT NULL DEFAULT '0.00',
  `myyntieranarvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `myyntierankpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `varaston_kiertonop` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kustannus_yht` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kustannus_osto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kustannus` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tuote_luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tyyppi_luokka` (`yhtio`,`tyyppi`,`luokka`,`summa`),
  KEY `yhtio_tyyppi_osasto_try` (`yhtio`,`tyyppi`,`osasto`,`try`),
  KEY `yhtio_tyyppi_tuoteno` (`yhtio`,`tyyppi`,`tuoteno`),
  KEY `yhtio_tyyppi_try` (`yhtio`,`tyyppi`,`try`),
  KEY `yhtio_tyyppi_tuotemerkki` (`yhtio`,`tyyppi`,`tuotemerkki`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=412 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `abc_parametrit`
--

DROP TABLE IF EXISTS `abc_parametrit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abc_parametrit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(2) NOT NULL DEFAULT '',
  `luokka` varchar(4) NOT NULL DEFAULT '',
  `osuusprosentti` int(2) NOT NULL DEFAULT '0',
  `kiertonopeus_tavoite` decimal(5,2) NOT NULL DEFAULT '0.00',
  `palvelutaso_tavoite` decimal(5,2) NOT NULL DEFAULT '0.00',
  `varmuusvarasto_pv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `toimittajan_toimitusaika_pv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kulujen_taso` varchar(20) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=283 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asiakas`
--

DROP TABLE IF EXISTS `asiakas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asiakas` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `laji` char(1) NOT NULL DEFAULT '',
  `tila` varchar(150) NOT NULL DEFAULT '',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `nimitark` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(55) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(35) NOT NULL DEFAULT '',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `kunta` varchar(150) NOT NULL DEFAULT '',
  `laani` varchar(150) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `kansalaisuus` varchar(100) NOT NULL DEFAULT '',
  `tyonantaja` varchar(100) NOT NULL DEFAULT '',
  `ammatti` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `lasku_email` varchar(255) NOT NULL DEFAULT '',
  `talhal_email` varchar(255) NOT NULL DEFAULT '',
  `puhelin` varchar(100) NOT NULL DEFAULT '',
  `gsm` varchar(100) NOT NULL DEFAULT '',
  `tyopuhelin` varchar(100) NOT NULL DEFAULT '',
  `fax` varchar(100) NOT NULL DEFAULT '',
  `toim_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `toim_nimi` varchar(60) NOT NULL DEFAULT '',
  `toim_nimitark` varchar(60) NOT NULL DEFAULT '',
  `toim_osoite` varchar(55) NOT NULL DEFAULT '',
  `toim_postino` varchar(15) NOT NULL DEFAULT '',
  `toim_postitp` varchar(35) NOT NULL DEFAULT '',
  `toim_maa` char(2) NOT NULL DEFAULT '',
  `kolm_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `kolm_nimi` varchar(60) NOT NULL DEFAULT '',
  `kolm_nimitark` varchar(60) NOT NULL DEFAULT '',
  `kolm_osoite` varchar(55) NOT NULL DEFAULT '',
  `kolm_postino` varchar(15) NOT NULL DEFAULT '',
  `kolm_postitp` varchar(35) NOT NULL DEFAULT '',
  `kolm_maa` char(2) NOT NULL DEFAULT '',
  `laskutus_nimi` varchar(60) NOT NULL DEFAULT '',
  `laskutus_nimitark` varchar(60) NOT NULL DEFAULT '',
  `laskutus_osoite` varchar(60) NOT NULL DEFAULT '',
  `laskutus_postino` varchar(60) NOT NULL DEFAULT '',
  `laskutus_postitp` varchar(60) NOT NULL DEFAULT '',
  `laskutus_maa` varchar(60) NOT NULL DEFAULT '',
  `maksukehotuksen_osoitetiedot` char(1) NOT NULL DEFAULT '',
  `konserni` varchar(60) NOT NULL DEFAULT '',
  `asiakasnro` varchar(20) NOT NULL DEFAULT '',
  `piiri` varchar(150) NOT NULL DEFAULT '',
  `ryhma` varchar(150) NOT NULL DEFAULT '',
  `osasto` varchar(50) NOT NULL DEFAULT '',
  `verkkotunnus` varchar(76) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `chn` char(3) NOT NULL DEFAULT '',
  `konserniyhtio` char(1) NOT NULL DEFAULT '',
  `fakta` text,
  `myynti_kommentti1` text,
  `sisviesti2` text,
  `sisviesti1` text,
  `tilaus_viesti` varchar(70) NOT NULL DEFAULT '',
  `kuljetusohje` text,
  `selaus` varchar(55) NOT NULL DEFAULT '',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `maksuehto` int(11) NOT NULL DEFAULT '0',
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `toimitusaikaikkuna` int(11) NOT NULL DEFAULT '0',
  `rahtivapaa` char(1) NOT NULL DEFAULT '',
  `rahtivapaa_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kuljetusvakuutus_tyyppi` char(1) NOT NULL DEFAULT '',
  `toimitusehto` varchar(60) NOT NULL DEFAULT '',
  `tilausvahvistus` varchar(15) NOT NULL DEFAULT '',
  `tilausvahvistus_jttoimituksista` char(1) NOT NULL DEFAULT '',
  `jt_toimitusaika_email_vahvistus` char(1) NOT NULL DEFAULT '',
  `toimitusvahvistus` varchar(50) NOT NULL DEFAULT '',
  `kerayspoikkeama` int(1) NOT NULL DEFAULT '0',
  `keraysvahvistus_lahetys` char(1) NOT NULL DEFAULT '',
  `keraysvahvistus_email` varchar(255) NOT NULL DEFAULT '',
  `kerayserat` char(1) NOT NULL DEFAULT '',
  `lahetetyyppi` varchar(150) NOT NULL DEFAULT '',
  `lahetteen_jarjestys` char(1) NOT NULL DEFAULT '',
  `lahetteen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `koontilahete_kollitiedot` char(1) NOT NULL DEFAULT '',
  `asiakasviivakoodi` char(1) NOT NULL DEFAULT '',
  `laskutyyppi` int(2) NOT NULL DEFAULT '0',
  `laskutusvkopv` int(1) NOT NULL DEFAULT '0',
  `maksusopimus_toimitus` char(1) NOT NULL DEFAULT '',
  `laskun_jarjestys` char(1) NOT NULL DEFAULT '',
  `laskun_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `extranet_tilaus_varaa_saldoa` varchar(3) NOT NULL DEFAULT '',
  `vienti` char(1) NOT NULL DEFAULT '',
  `vientitietojen_autosyotto` char(1) NOT NULL DEFAULT '',
  `ketjutus` char(1) NOT NULL DEFAULT '',
  `koontilaskut_yhdistetaan` char(1) NOT NULL DEFAULT '',
  `luokka` varchar(50) NOT NULL DEFAULT '',
  `jtkielto` char(1) NOT NULL DEFAULT '',
  `jtrivit` int(1) NOT NULL DEFAULT '0',
  `myyjanro` int(11) NOT NULL DEFAULT '0',
  `erikoisale` decimal(5,2) NOT NULL DEFAULT '0.00',
  `myyntikielto` char(1) NOT NULL DEFAULT '',
  `myynninseuranta` char(1) NOT NULL DEFAULT '',
  `luottoraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `luottovakuutettu` char(1) NOT NULL DEFAULT '',
  `kuluprosentti` decimal(8,3) NOT NULL DEFAULT '0.000',
  `tuntihinta` decimal(7,2) NOT NULL DEFAULT '0.00',
  `tuntikerroin` decimal(7,2) NOT NULL DEFAULT '0.00',
  `hintakerroin` decimal(7,2) NOT NULL DEFAULT '0.00',
  `pientarvikelisa` decimal(7,2) NOT NULL DEFAULT '0.00',
  `laskunsummapyoristys` char(1) NOT NULL DEFAULT '',
  `laskutuslisa` char(1) NOT NULL DEFAULT '',
  `panttitili` char(1) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `tilino_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_ei_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_kaanteinen` varchar(6) NOT NULL DEFAULT '',
  `tilino_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_osto_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_triang` varchar(6) NOT NULL DEFAULT '',
  `kustannuspaikka` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `flag_1` char(2) NOT NULL DEFAULT '',
  `flag_2` char(2) NOT NULL DEFAULT '',
  `flag_3` char(2) NOT NULL DEFAULT '',
  `flag_4` char(2) NOT NULL DEFAULT '',
  `maa_maara` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `sisamaan_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `kontti` int(1) NOT NULL DEFAULT '0',
  `aktiivinen_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `aktiivinen_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `kauppatapahtuman_luonne` int(2) NOT NULL DEFAULT '0',
  `kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `poistumistoimipaikka_koodi` varchar(8) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `asno_index` (`yhtio`,`asiakasnro`),
  KEY `ytunnus_index` (`yhtio`,`ytunnus`),
  KEY `ovttunnus_index` (`yhtio`,`ovttunnus`),
  KEY `yhtio_osasto_ryhma` (`yhtio`,`osasto`,`ryhma`),
  KEY `toim_ovttunnus_index` (`yhtio`,`toim_ovttunnus`),
  FULLTEXT KEY `asiakasnimi` (`nimi`),
  FULLTEXT KEY `asiakastoim_nimi` (`toim_nimi`),
  FULLTEXT KEY `asiakasnimitark` (`nimitark`),
  FULLTEXT KEY `asiakastoim_nimitark` (`toim_nimitark`)
) ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asiakasalennus`
--

DROP TABLE IF EXISTS `asiakasalennus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asiakasalennus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `ryhma` varchar(15) NOT NULL DEFAULT '',
  `asiakas` int(11) NOT NULL DEFAULT '0',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `asiakas_ryhma` varchar(150) NOT NULL DEFAULT '',
  `asiakas_segmentti` bigint(20) unsigned NOT NULL DEFAULT '0',
  `piiri` varchar(150) NOT NULL DEFAULT '',
  `campaign_id` int(11) DEFAULT NULL,
  `alennus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `alennuslaji` int(1) NOT NULL DEFAULT '1',
  `minkpl` int(11) NOT NULL DEFAULT '0',
  `monikerta` char(1) NOT NULL DEFAULT '',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_ytunnus_ryhma` (`yhtio`,`ytunnus`,`ryhma`),
  KEY `yhtio_ytunnus_tuoteno` (`yhtio`,`ytunnus`,`tuoteno`),
  KEY `yhtio_asiakasryhma_tuoteno` (`yhtio`,`asiakas_ryhma`,`tuoteno`),
  KEY `yhtio_asiakasryhma_ryhma` (`yhtio`,`asiakas_ryhma`,`ryhma`),
  KEY `yhtio_asiakas_ryhma` (`yhtio`,`asiakas`,`ryhma`),
  KEY `yhtio_asiakas_tuoteno` (`yhtio`,`asiakas`,`tuoteno`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_piiri_tuoteno` (`yhtio`,`piiri`,`tuoteno`),
  KEY `yhtio_piiri_ryhma` (`yhtio`,`piiri`,`ryhma`),
  KEY `yhtio_asiakas_segmentti_tuoteno` (`yhtio`,`asiakas_segmentti`,`tuoteno`),
  KEY `yhtio_asiakas_segmentti_ryhma` (`yhtio`,`asiakas_segmentti`,`ryhma`)
) ENGINE=MyISAM AUTO_INCREMENT=102 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asiakashinta`
--

DROP TABLE IF EXISTS `asiakashinta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asiakashinta` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `ryhma` varchar(15) NOT NULL DEFAULT '',
  `asiakas` int(11) NOT NULL DEFAULT '0',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `asiakas_ryhma` varchar(150) NOT NULL DEFAULT '',
  `asiakas_segmentti` bigint(20) unsigned NOT NULL DEFAULT '0',
  `piiri` varchar(150) NOT NULL DEFAULT '',
  `campaign_id` int(11) DEFAULT NULL,
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `myyntikate` int(11) NOT NULL DEFAULT '0',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `minkpl` int(11) NOT NULL DEFAULT '0',
  `maxkpl` int(11) NOT NULL DEFAULT '0',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `laji` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_ytunnus_tuoteno` (`yhtio`,`ytunnus`,`tuoteno`),
  KEY `yhtio_ytunnus_ryhma` (`yhtio`,`ytunnus`,`ryhma`),
  KEY `yhtio_asiakasryhma_tuoteno` (`yhtio`,`asiakas_ryhma`,`tuoteno`),
  KEY `yhtio_asiakasryhma_ryhma` (`yhtio`,`asiakas_ryhma`,`ryhma`),
  KEY `yhtio_asiakas_ryhma` (`yhtio`,`asiakas`,`ryhma`),
  KEY `yhtio_asiakas_tuoteno` (`yhtio`,`asiakas`,`tuoteno`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_piiri_tuoteno` (`yhtio`,`piiri`,`tuoteno`),
  KEY `yhtio_piiri_ryhma` (`yhtio`,`piiri`,`ryhma`),
  KEY `yhtio_asiakas_segmentti_tuoteno` (`yhtio`,`asiakas_segmentti`,`tuoteno`),
  KEY `yhtio_asiakas_segmentti_ryhma` (`yhtio`,`asiakas_segmentti`,`ryhma`)
) ENGINE=MyISAM AUTO_INCREMENT=106 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asiakaskommentti`
--

DROP TABLE IF EXISTS `asiakaskommentti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asiakaskommentti` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kommentti` text,
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_ytunnus_tuoteno` (`yhtio`,`ytunnus`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asiakkaan_avainsanat`
--

DROP TABLE IF EXISTS `asiakkaan_avainsanat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asiakkaan_avainsanat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `kieli` varchar(2) NOT NULL DEFAULT '',
  `laji` varchar(150) NOT NULL DEFAULT '',
  `avainsana` text,
  `tarkenne` text,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_liitostunnus` (`yhtio`,`liitostunnus`),
  KEY `yhtio_laji` (`yhtio`,`laji`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asn_sanomat`
--

DROP TABLE IF EXISTS `asn_sanomat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asn_sanomat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `laji` varchar(3) NOT NULL DEFAULT '',
  `toimittajanumero` varchar(20) NOT NULL DEFAULT '',
  `asn_numero` varchar(20) NOT NULL DEFAULT '',
  `sscc_koodi` varchar(20) NOT NULL DEFAULT '',
  `saapumispvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vastaanottaja` text,
  `tilausnumero` int(11) NOT NULL DEFAULT '0',
  `tilausrivi` text,
  `paketintunniste` varchar(30) NOT NULL DEFAULT '',
  `paketinnumero` varchar(5) NOT NULL DEFAULT '',
  `lahetyslistannro` varchar(30) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `toim_tuoteno` varchar(60) NOT NULL DEFAULT '',
  `toim_tuoteno2` varchar(60) NOT NULL DEFAULT '',
  `kappalemaara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `keikkarivinhinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `lisakulu` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `kulu` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `pakkauskulu` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `rahtikulu` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `ale1` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale2` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale3` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lasku_ale1` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lasku_ale2` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lasku_ale3` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tilausrivinpositio` varchar(10) NOT NULL DEFAULT '',
  `status` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_laji_toimittajanumero_asn_numero` (`yhtio`,`laji`,`toimittajanumero`,`asn_numero`),
  KEY `yhtio_status_toimtuoteno_toimnro` (`yhtio`,`status`,`tuoteno`,`toim_tuoteno`,`toimittajanumero`),
  KEY `yhtio_status_tuoteno` (`yhtio`,`status`,`tuoteno`),
  FULLTEXT KEY `fulltext_tilausrivi` (`tilausrivi`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `avainsana`
--

DROP TABLE IF EXISTS `avainsana`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avainsana` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `perhe` int(11) NOT NULL DEFAULT '0',
  `kieli` char(2) NOT NULL DEFAULT '',
  `laji` varchar(15) NOT NULL DEFAULT '',
  `nakyvyys` char(1) NOT NULL DEFAULT '',
  `selite` varchar(150) NOT NULL DEFAULT '',
  `selitetark` text,
  `selitetark_2` text,
  `selitetark_3` text,
  `selitetark_4` text,
  `selitetark_5` text,
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_laji_selite` (`yhtio`,`laji`,`selite`),
  KEY `yhtio_laji_selitetark` (`yhtio`,`laji`,`selitetark`(100)),
  KEY `yhtio_laji_perhe_kieli` (`yhtio`,`laji`,`perhe`,`kieli`),
  KEY `yhtio_laji_selitetark3` (`yhtio`,`laji`,`selitetark_3`(100))
) ENGINE=MyISAM AUTO_INCREMENT=15056 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti`
--

DROP TABLE IF EXISTS `budjetti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `taso` varchar(20) NOT NULL DEFAULT '',
  `tili` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_taso_kausi` (`yhtio`,`taso`,`kausi`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_asiakas`
--

DROP TABLE IF EXISTS `budjetti_asiakas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_asiakas` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `asiakkaan_tunnus` int(11) NOT NULL DEFAULT '0',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `asbu` (`yhtio`,`kausi`,`asiakkaan_tunnus`,`osasto`,`try`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_asiakasmyyja`
--

DROP TABLE IF EXISTS `budjetti_asiakasmyyja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_asiakasmyyja` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `asiakasmyyjan_tunnus` int(11) NOT NULL DEFAULT '0',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL,
  `muutospvm` datetime NOT NULL,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `budjetti_asiakasmyyja` (`yhtio`,`kausi`,`asiakasmyyjan_tunnus`,`osasto`,`try`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_maa`
--

DROP TABLE IF EXISTS `budjetti_maa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_maa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `maa_id` int(11) NOT NULL DEFAULT '0',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mabu` (`yhtio`,`maa_id`,`kausi`,`osasto`,`try`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_myyja`
--

DROP TABLE IF EXISTS `budjetti_myyja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_myyja` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `myyjan_tunnus` int(11) NOT NULL DEFAULT '0',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `budjetti_myyja` (`yhtio`,`kausi`,`myyjan_tunnus`,`osasto`,`try`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_toimittaja`
--

DROP TABLE IF EXISTS `budjetti_toimittaja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_toimittaja` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `toimittajan_tunnus` int(11) NOT NULL DEFAULT '0',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `tobu` (`yhtio`,`kausi`,`toimittajan_tunnus`,`osasto`,`try`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budjetti_tuote`
--

DROP TABLE IF EXISTS `budjetti_tuote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budjetti_tuote` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kausi` varchar(6) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `osasto` varchar(150) NOT NULL DEFAULT '',
  `try` varchar(150) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indeksi` decimal(5,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `tubu` (`yhtio`,`kausi`,`tuoteno`,`osasto`(50),`try`(50)),
  KEY `yhtio_tuote_kausi` (`yhtio`,`tuoteno`,`kausi`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `company_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_campaigns_on_company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `changelog`
--

DROP TABLE IF EXISTS `changelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `changelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `table` varchar(150) NOT NULL DEFAULT '',
  `key` int(11) NOT NULL DEFAULT '0',
  `field` varchar(150) NOT NULL DEFAULT '',
  `old_value_str` varchar(255) NOT NULL DEFAULT '',
  `new_value_str` varchar(255) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `yhtio_table_key_field_luontiaika` (`yhtio`,`table`,`key`,`field`,`luontiaika`),
  KEY `yhtio_table_field_luontiaika` (`yhtio`,`table`,`field`,`luontiaika`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers_users`
--

DROP TABLE IF EXISTS `customers_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers_users` (
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  KEY `index_customers_users_on_user_id` (`user_id`),
  KEY `index_customers_users_on_customer_id` (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `directdebit`
--

DROP TABLE IF EXISTS `directdebit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `directdebit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `rahalaitos` varchar(20) NOT NULL DEFAULT '',
  `nimitys` varchar(50) NOT NULL DEFAULT '',
  `palvelutunnus` varchar(35) NOT NULL DEFAULT '',
  `suoraveloitusmandaatti` varchar(50) NOT NULL DEFAULT '',
  `teksti_fi` text,
  `teksti_en` text,
  `teksti_se` text,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `directdebit_asiakas`
--

DROP TABLE IF EXISTS `directdebit_asiakas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `directdebit_asiakas` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `directdebit_id` int(11) DEFAULT NULL,
  `valtuutus_id` varchar(35) NOT NULL DEFAULT '',
  `valtuutus_pvm` date NOT NULL DEFAULT '0000-00-00',
  `maksajan_iban` varchar(35) NOT NULL DEFAULT '',
  `maksajan_swift` varchar(11) NOT NULL DEFAULT '',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `downloads`
--

DROP TABLE IF EXISTS `downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `report_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_downloads_on_user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dynaaminen_puu`
--

DROP TABLE IF EXISTS `dynaaminen_puu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dynaaminen_puu` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(120) NOT NULL DEFAULT '',
  `nimi_en` varchar(120) NOT NULL DEFAULT '',
  `koodi` int(11) NOT NULL DEFAULT '0',
  `toimittajan_koodi` int(11) NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `laji` varchar(30) NOT NULL DEFAULT '',
  `children_count` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(11) DEFAULT NULL,
  `syvyys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_laji_koodi` (`yhtio`,`laji`,`koodi`),
  KEY `yhtio_laji_lft` (`yhtio`,`laji`,`lft`),
  KEY `yhtio_laji_rgt` (`yhtio`,`laji`,`rgt`),
  KEY `index_dynaaminen_puu_on_parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dynaaminen_puu_avainsanat`
--

DROP TABLE IF EXISTS `dynaaminen_puu_avainsanat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dynaaminen_puu_avainsanat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `kieli` char(2) NOT NULL DEFAULT '',
  `laji` varchar(150) NOT NULL DEFAULT '',
  `avainsana` text,
  `tarkenne` text,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_liitostunnus` (`yhtio`,`liitostunnus`),
  KEY `yhtio_laji` (`yhtio`,`laji`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `etaisyydet`
--

DROP TABLE IF EXISTS `etaisyydet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `etaisyydet` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `varasto_postino` varchar(15) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(35) NOT NULL DEFAULT '',
  `km` int(5) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extranet_kayttajan_lisatiedot`
--

DROP TABLE IF EXISTS `extranet_kayttajan_lisatiedot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extranet_kayttajan_lisatiedot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `laji` varchar(20) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `selite` varchar(50) NOT NULL DEFAULT '',
  `selitetark` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_liitostunnus_laji_selite` (`yhtio`,`liitostunnus`,`laji`,`selite`),
  KEY `yhtio_laji_selite` (`yhtio`,`laji`,`selite`)
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `factoring`
--

DROP TABLE IF EXISTS `factoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factoring` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `factoringyhtio` varchar(10) NOT NULL DEFAULT '',
  `nimitys` varchar(55) NOT NULL DEFAULT '',
  `pankkinimi1` varchar(80) NOT NULL DEFAULT '',
  `pankkitili1` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban1` varchar(80) NOT NULL DEFAULT '',
  `pankkiswift1` varchar(80) NOT NULL DEFAULT '',
  `pankkinimi2` varchar(80) NOT NULL DEFAULT '',
  `pankkitili2` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban2` varchar(80) NOT NULL DEFAULT '',
  `pankkiswift2` varchar(80) NOT NULL DEFAULT '',
  `pankki_tili` varchar(35) NOT NULL DEFAULT '',
  `teksti_fi` text,
  `teksti_en` text,
  `teksti_se` text,
  `teksti_ee` text,
  `sopimusnumero` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `viitetyyppi` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `data` longblob,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_files_on_download_id` (`download_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixed_assets_commodities`
--

DROP TABLE IF EXISTS `fixed_assets_commodities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixed_assets_commodities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `profit_account_id` int(11) DEFAULT NULL,
  `sales_account_id` int(11) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `activated_at` date DEFAULT NULL,
  `deactivated_at` date DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `planned_depreciation_type` char(1) DEFAULT NULL,
  `planned_depreciation_amount` decimal(16,6) DEFAULT NULL,
  `btl_depreciation_type` char(1) DEFAULT NULL,
  `btl_depreciation_amount` decimal(16,6) DEFAULT NULL,
  `amount` decimal(16,6) DEFAULT NULL,
  `amount_sold` decimal(16,6) DEFAULT NULL,
  `previous_btl_depreciations` decimal(16,6) DEFAULT '0.000000',
  `transferred_procurement_amount` decimal(16,6) DEFAULT '0.000000',
  `depreciation_remainder_handling` char(1) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `modified_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_fixed_assets_commodities_on_company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixed_assets_commodity_rows`
--

DROP TABLE IF EXISTS `fixed_assets_commodity_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixed_assets_commodity_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commodity_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `transacted_at` date DEFAULT NULL,
  `amount` decimal(16,6) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amended` tinyint(1) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` varchar(255) DEFAULT NULL,
  `modified_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_fixed_assets_commodity_rows_on_commodity_id` (`commodity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `git_paivitykset`
--

DROP TABLE IF EXISTS `git_paivitykset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `git_paivitykset` (
  `hash_pupesoft` varchar(50) NOT NULL DEFAULT '',
  `hash_pupenext` varchar(50) NOT NULL DEFAULT '',
  `repository` varchar(20) NOT NULL DEFAULT 'pupesoft',
  `ip` varchar(15) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=430 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `git_pulkkarit`
--

DROP TABLE IF EXISTS `git_pulkkarit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `git_pulkkarit` (
  `id` int(11) NOT NULL,
  `repository` varchar(20) NOT NULL DEFAULT 'pupesoft',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `merged` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `feature` int(1) NOT NULL DEFAULT '0',
  `pull_request` text,
  `files` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hinnasto`
--

DROP TABLE IF EXISTS `hinnasto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hinnasto` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `minkpl` int(11) NOT NULL DEFAULT '0',
  `maxkpl` int(11) NOT NULL DEFAULT '0',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `laji` char(1) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `selite` varchar(100) NOT NULL DEFAULT '',
  `yhtion_toimipaikka_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `index_hinnasto_on_yhtion_toimipaikka_id` (`yhtion_toimipaikka_id`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hyvaksyttavat_dokumentit`
--

DROP TABLE IF EXISTS `hyvaksyttavat_dokumentit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hyvaksyttavat_dokumentit` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(150) NOT NULL DEFAULT '',
  `kuvaus` tinytext,
  `kommentit` tinytext,
  `tiedostotyyppi` varchar(150) NOT NULL DEFAULT '',
  `tila` varchar(2) NOT NULL DEFAULT '0',
  `hyvak1` varchar(50) NOT NULL DEFAULT '',
  `h1time` datetime NOT NULL,
  `hyvak2` varchar(50) NOT NULL DEFAULT '',
  `h2time` datetime NOT NULL,
  `hyvak3` varchar(50) NOT NULL DEFAULT '',
  `h3time` datetime NOT NULL,
  `hyvak4` varchar(50) NOT NULL DEFAULT '',
  `h4time` datetime NOT NULL,
  `hyvak5` varchar(50) NOT NULL DEFAULT '',
  `h5time` datetime NOT NULL,
  `hyvaksyja_nyt` varchar(50) NOT NULL DEFAULT '',
  `hyvaksynnanmuutos` varchar(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tila` (`yhtio`,`tila`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hyvaksyttavat_dokumenttityypit`
--

DROP TABLE IF EXISTS `hyvaksyttavat_dokumenttityypit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hyvaksyttavat_dokumenttityypit` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` varchar(150) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hyvaksyttavat_dokumenttityypit_kayttajat`
--

DROP TABLE IF EXISTS `hyvaksyttavat_dokumenttityypit_kayttajat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hyvaksyttavat_dokumenttityypit_kayttajat` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `doku_tyyppi_tunnus` int(11) NOT NULL DEFAULT '0',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hyvityssaannot`
--

DROP TABLE IF EXISTS `hyvityssaannot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hyvityssaannot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `rokotusselite` varchar(100) NOT NULL DEFAULT '',
  `tuote_kentta` varchar(30) NOT NULL DEFAULT '',
  `tuote_arvo` varchar(100) NOT NULL DEFAULT '',
  `asiakas_kentta` varchar(40) NOT NULL DEFAULT '',
  `asiakas_arvo` varchar(100) NOT NULL DEFAULT '',
  `asiakas_segmentti` int(11) NOT NULL DEFAULT '0',
  `aika_ostosta` int(11) NOT NULL DEFAULT '0',
  `rokotusprosentti` decimal(5,2) NOT NULL DEFAULT '0.00',
  `palautuskielto` char(1) NOT NULL DEFAULT '',
  `prioriteetti` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tuote_kentta_tuote_arvo` (`yhtio`,`tuote_kentta`,`tuote_arvo`),
  KEY `yhtio_aika_ostosta` (`yhtio`,`aika_ostosta`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `incoming_mails`
--

DROP TABLE IF EXISTS `incoming_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incoming_mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raw_source` longtext,
  `mail_server_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `status_message` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_incoming_mails_on_mail_server_id` (`mail_server_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventointilista`
--

DROP TABLE IF EXISTS `inventointilista`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventointilista` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `naytamaara` char(1) NOT NULL DEFAULT '',
  `vapaa_teksti` text NOT NULL,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime DEFAULT NULL,
  `muutospvm` datetime DEFAULT NULL,
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventointilistarivi`
--

DROP TABLE IF EXISTS `inventointilistarivi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventointilistarivi` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tila` char(1) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `aika` datetime DEFAULT NULL,
  `rivinro` int(11) NOT NULL DEFAULT '0',
  `hyllyssa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laskettu` decimal(12,2) DEFAULT NULL,
  `tuotepaikkatunnus` int(11) NOT NULL DEFAULT '0',
  `tapahtumatunnus` int(11) NOT NULL DEFAULT '0',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime DEFAULT NULL,
  `muutospvm` datetime DEFAULT NULL,
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tuotepaikkatunnus` (`yhtio`,`tuotepaikkatunnus`),
  KEY `index_inventointilistarivi_on_yhtio_and_otunnus_and_tuoteno` (`yhtio`,`otunnus`,`tuoteno`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kalenteri`
--

DROP TABLE IF EXISTS `kalenteri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kalenteri` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `konserni` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `myyntipaallikko` varchar(10) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `asiakas` varchar(15) NOT NULL DEFAULT '',
  `henkilo` bigint(20) NOT NULL DEFAULT '0',
  `tapa` varchar(25) NOT NULL DEFAULT '',
  `kuittaus` varchar(50) NOT NULL DEFAULT '',
  `pvmalku` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pvmloppu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aikaalku` time NOT NULL DEFAULT '00:00:00',
  `aikaloppu` time NOT NULL DEFAULT '00:00:00',
  `tyyppi` varchar(50) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `kokopaiva` char(1) NOT NULL DEFAULT '',
  `kentta01` text,
  `kentta02` text,
  `kentta03` text,
  `kentta04` text,
  `kentta05` text,
  `kentta06` text,
  `kentta07` text,
  `kentta08` text,
  `kentta09` text,
  `kentta10` text,
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `perheid` int(11) NOT NULL DEFAULT '0',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tyyppi_tapa` (`yhtio`,`tyyppi`,`tapa`,`pvmalku`),
  KEY `yhtio_tyyppi_pvmalku` (`yhtio`,`tyyppi`,`pvmalku`),
  KEY `yhtio_kuka_tyyppi_pvmalku` (`yhtio`,`kuka`,`tyyppi`,`pvmalku`),
  KEY `yhtio_liitostunnus` (`yhtio`,`liitostunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kampanja_ehdot`
--

DROP TABLE IF EXISTS `kampanja_ehdot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kampanja_ehdot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kampanja` int(11) NOT NULL DEFAULT '0',
  `isatunnus` int(11) NOT NULL DEFAULT '0',
  `kohde` varchar(20) NOT NULL DEFAULT '',
  `rajoitin` varchar(20) NOT NULL DEFAULT '',
  `arvo` varchar(20) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kampanja_palkinnot`
--

DROP TABLE IF EXISTS `kampanja_palkinnot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kampanja_palkinnot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kampanja` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `kpl` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kampanjat`
--

DROP TABLE IF EXISTS `kampanjat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kampanjat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(255) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `karhu_lasku`
--

DROP TABLE IF EXISTS `karhu_lasku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `karhu_lasku` (
  `ktunnus` int(11) NOT NULL DEFAULT '0',
  `ltunnus` int(11) NOT NULL DEFAULT '0',
  KEY `ktunnus` (`ktunnus`),
  KEY `ltunnus` (`ltunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `karhukierros`
--

DROP TABLE IF EXISTS `karhukierros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `karhukierros` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `pvm` date NOT NULL DEFAULT '0000-00-00',
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kassalipas`
--

DROP TABLE IF EXISTS `kassalipas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kassalipas` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(150) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `email` varchar(100) NOT NULL DEFAULT '',
  `kassa` varchar(6) NOT NULL DEFAULT '',
  `pankkikortti` varchar(6) NOT NULL DEFAULT '',
  `luottokortti` varchar(6) NOT NULL DEFAULT '',
  `kateistilitys` varchar(6) NOT NULL DEFAULT '',
  `kassaerotus` varchar(6) NOT NULL DEFAULT '',
  `kateisotto` varchar(6) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=102 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kerattavatrivit`
--

DROP TABLE IF EXISTS `kerattavatrivit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kerattavatrivit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tilausrivi_id` int(11) DEFAULT NULL,
  `hyllyalue` varchar(255) DEFAULT NULL,
  `hyllynro` varchar(255) DEFAULT NULL,
  `hyllyvali` varchar(255) DEFAULT NULL,
  `hyllytaso` varchar(255) DEFAULT NULL,
  `poikkeava_maara` decimal(10,0) DEFAULT NULL,
  `poikkeama_kasittely` varchar(255) DEFAULT NULL,
  `keratty` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilausrivi_id_index` (`tilausrivi_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kerayserat`
--

DROP TABLE IF EXISTS `kerayserat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kerayserat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nro` int(11) NOT NULL DEFAULT '0',
  `keraysvyohyke` int(11) NOT NULL DEFAULT '0',
  `tila` char(1) NOT NULL DEFAULT '',
  `sscc` int(11) NOT NULL DEFAULT '0',
  `sscc_ulkoinen` varchar(150) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `tilausrivi` int(11) NOT NULL DEFAULT '0',
  `pakkaus` int(11) NOT NULL DEFAULT '0',
  `pakkausnro` int(11) NOT NULL DEFAULT '0',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kpl_keratty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `keratty` varchar(50) NOT NULL DEFAULT '',
  `kerattyaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `ohjelma_moduli` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_nro` (`yhtio`,`nro`),
  KEY `yhtio_nro_tila_laatija` (`yhtio`,`nro`,`tila`,`laatija`),
  KEY `yhtio_tilausrivi` (`yhtio`,`tilausrivi`),
  KEY `yhtio_otunnus` (`yhtio`,`otunnus`),
  KEY `yhtio_sscculkoinen` (`yhtio`,`sscc_ulkoinen`),
  KEY `yhtio_sscc` (`yhtio`,`sscc`),
  KEY `yhtio_tila_laatija` (`yhtio`,`tila`,`laatija`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `keraysvyohyke`
--

DROP TABLE IF EXISTS `keraysvyohyke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keraysvyohyke` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `sallitut_alustat` varchar(150) NOT NULL DEFAULT '',
  `varasto` int(11) NOT NULL DEFAULT '0',
  `printteri0` varchar(20) NOT NULL DEFAULT '',
  `printteri1` varchar(20) NOT NULL DEFAULT '',
  `printteri3` varchar(20) NOT NULL DEFAULT '',
  `printteri8` varchar(20) NOT NULL DEFAULT '',
  `max_keraysera_pintaala` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_keraysera_rivit` int(11) NOT NULL DEFAULT '0',
  `max_keraysera_alustat` int(11) NOT NULL DEFAULT '0',
  `keraysjarjestys` char(1) NOT NULL DEFAULT '',
  `terminaalialue` varchar(150) NOT NULL DEFAULT '',
  `lahtojen_valinen_enimmaisaika` int(11) NOT NULL DEFAULT '0',
  `tilauksen_tyoaikavakio_min_per_tilaus` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kerailyrivin_tyoaikavakio_min_per_rivi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ulkoinen_jarjestelma` char(1) NOT NULL DEFAULT '',
  `yhdistelysaanto` varchar(5) NOT NULL DEFAULT '',
  `keraysnippujen_priorisointi` char(1) NOT NULL DEFAULT '',
  `aikaraja` decimal(5,2) NOT NULL DEFAULT '0.00',
  `mittaraja` decimal(10,2) NOT NULL DEFAULT '0.00',
  `painoraja` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kappaleraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio` (`yhtio`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kirjoittimet`
--

DROP TABLE IF EXISTS `kirjoittimet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kirjoittimet` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kirjoitin` varchar(100) NOT NULL DEFAULT '',
  `komento` varchar(150) NOT NULL DEFAULT '',
  `unifaun_nimi` varchar(150) NOT NULL DEFAULT '',
  `merkisto` int(1) NOT NULL DEFAULT '0',
  `mediatyyppi` varchar(100) NOT NULL DEFAULT '',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(30) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(10) NOT NULL DEFAULT '',
  `puhelin` varchar(20) NOT NULL DEFAULT '',
  `yhteyshenkilo` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kohde`
--

DROP TABLE IF EXISTS `kohde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kohde` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `asiakas` int(11) NOT NULL DEFAULT '0',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `nimitark` varchar(60) NOT NULL DEFAULT '',
  `tyyppi` varchar(150) NOT NULL DEFAULT '',
  `osoite` varchar(60) NOT NULL DEFAULT '',
  `osoitetark` varchar(60) NOT NULL DEFAULT '',
  `postitp` varchar(60) NOT NULL DEFAULT '',
  `postino` varchar(60) NOT NULL DEFAULT '',
  `paikkakunta` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `puhelin` varchar(20) NOT NULL DEFAULT '',
  `fax` varchar(20) NOT NULL DEFAULT '',
  `yhteyshlo` varchar(60) NOT NULL DEFAULT '',
  `vastuuhenkilo` varchar(50) NOT NULL DEFAULT '',
  `kayntiohje` varchar(150) NOT NULL DEFAULT '',
  `kommentti` text,
  `poistettu` tinyint(1) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_asiakas` (`yhtio`,`asiakas`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `korvaavat`
--

DROP TABLE IF EXISTS `korvaavat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `korvaavat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `jarjestys` bigint(20) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_id` (`yhtio`,`id`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `korvaavat_kiellot`
--

DROP TABLE IF EXISTS `korvaavat_kiellot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `korvaavat_kiellot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `osasto` char(2) NOT NULL DEFAULT '',
  `try` varchar(5) NOT NULL DEFAULT '',
  `laji` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kuka`
--

DROP TABLE IF EXISTS `kuka`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kuka` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `nimi` text,
  `salasana` varchar(50) NOT NULL DEFAULT '',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `profiilit` text,
  `piirit` text,
  `naytetaan_katteet_tilauksella` char(1) NOT NULL DEFAULT '',
  `naytetaan_asiakashinta` char(1) NOT NULL DEFAULT '',
  `naytetaan_tuotteet` char(1) NOT NULL DEFAULT '',
  `naytetaan_tilaukset` char(1) NOT NULL DEFAULT '',
  `jyvitys` char(1) NOT NULL DEFAULT '',
  `hyvaksyja` char(1) NOT NULL DEFAULT '',
  `hyvaksyja_maksimisumma` int(11) NOT NULL DEFAULT '0',
  `hierarkia` varchar(150) NOT NULL DEFAULT '',
  `extranet` char(1) NOT NULL DEFAULT '',
  `kayttoliittyma` char(1) NOT NULL DEFAULT '',
  `oletus_ohjelma` varchar(150) NOT NULL DEFAULT '',
  `maksuehto` int(11) NOT NULL DEFAULT '0',
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `eilahetetta` char(1) NOT NULL DEFAULT '',
  `oletus_asiakas` varchar(25) NOT NULL DEFAULT '',
  `oletus_asiakastiedot` varchar(25) NOT NULL DEFAULT '',
  `oletus_profiili` varchar(150) NOT NULL DEFAULT '',
  `kassamyyja` varchar(150) NOT NULL DEFAULT '',
  `kassalipas_otto` varchar(255) NOT NULL DEFAULT '',
  `kirjoitin` int(11) NOT NULL DEFAULT '0',
  `kuittitulostin` int(11) NOT NULL DEFAULT '0',
  `lahetetulostin` int(11) NOT NULL DEFAULT '0',
  `rahtikirjatulostin` int(11) NOT NULL DEFAULT '0',
  `varasto` varchar(150) NOT NULL DEFAULT '',
  `oletus_varasto` int(11) NOT NULL DEFAULT '0',
  `oletus_ostovarasto` int(11) NOT NULL DEFAULT '0',
  `oletus_pakkaamo` varchar(150) NOT NULL DEFAULT '',
  `fyysinen_sijainti` varchar(150) NOT NULL DEFAULT '',
  `keraysvyohyke` varchar(150) NOT NULL DEFAULT '',
  `max_keraysera_alustat` int(11) NOT NULL DEFAULT '0',
  `saatavat` int(1) NOT NULL DEFAULT '0',
  `hinnat` int(1) NOT NULL DEFAULT '0',
  `kulujen_laskeminen_hintoihin` char(1) NOT NULL DEFAULT '',
  `taso` int(1) NOT NULL DEFAULT '0',
  `kesken` int(11) NOT NULL DEFAULT '0',
  `lastlogin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `keraajanro` int(5) NOT NULL DEFAULT '0',
  `osasto` varchar(70) NOT NULL DEFAULT '',
  `myyja` int(11) NOT NULL DEFAULT '0',
  `oletustili` varchar(6) NOT NULL DEFAULT '',
  `myyjaryhma` int(5) NOT NULL DEFAULT '0',
  `tuuraaja` varchar(50) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `lomaoikeus` int(2) NOT NULL DEFAULT '0',
  `asema` varchar(150) NOT NULL DEFAULT '',
  `dynaaminen_kassamyynti` char(1) NOT NULL DEFAULT '',
  `maksupaate_kassamyynti` char(1) NOT NULL DEFAULT '',
  `maksupaate_id` varchar(60) NOT NULL DEFAULT '',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `eposti` varchar(50) NOT NULL DEFAULT '',
  `puhno` varchar(30) NOT NULL DEFAULT '',
  `tilaus_valmis` char(1) NOT NULL DEFAULT '',
  `mitatoi_tilauksia` char(1) NOT NULL DEFAULT '',
  `session` varchar(32) NOT NULL DEFAULT '',
  `api_key` varchar(100) NOT NULL DEFAULT '',
  `budjetti` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aktiivinen` int(1) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `kuka_index` (`yhtio`,`kuka`),
  KEY `session_index` (`session`),
  KEY `yhtio_kesken` (`yhtio`,`kesken`),
  KEY `yhtio_myyja` (`yhtio`,`myyja`),
  KEY `yhtio_aktiivinen_extranet` (`yhtio`,`aktiivinen`,`extranet`)
) ENGINE=MyISAM AUTO_INCREMENT=118 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kustannuspaikka`
--

DROP TABLE IF EXISTS `kustannuspaikka`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kustannuspaikka` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `isa_tarkenne` int(11) DEFAULT NULL,
  `koodi` varchar(35) NOT NULL DEFAULT '',
  `nimi` varchar(35) NOT NULL DEFAULT '',
  `kaytossa` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=112 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lahdot`
--

DROP TABLE IF EXISTS `lahdot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lahdot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `pvm` date NOT NULL DEFAULT '0000-00-00',
  `lahdon_viikonpvm` int(1) NOT NULL DEFAULT '0',
  `lahdon_kellonaika` time NOT NULL DEFAULT '00:00:00',
  `viimeinen_tilausaika` time NOT NULL DEFAULT '00:00:00',
  `kerailyn_aloitusaika` time NOT NULL DEFAULT '00:00:00',
  `terminaalialue` varchar(150) NOT NULL DEFAULT '',
  `asiakasluokka` varchar(50) NOT NULL DEFAULT '',
  `vakisin_kerays` char(1) NOT NULL DEFAULT '',
  `aktiivi` char(1) NOT NULL DEFAULT '',
  `ohjausmerkki` varchar(70) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `varasto` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_aktiivi_liitostunnus` (`yhtio`,`aktiivi`,`liitostunnus`),
  KEY `yhtio_aktiivi_pvm` (`yhtio`,`aktiivi`,`pvm`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laite`
--

DROP TABLE IF EXISTS `laite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laite` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `sarjanro` varchar(60) NOT NULL DEFAULT '',
  `ponnepullonro` varchar(60) NOT NULL DEFAULT '',
  `valm_pvm` date NOT NULL DEFAULT '0000-00-00',
  `oma_numero` varchar(20) NOT NULL DEFAULT '',
  `omistaja` varchar(60) NOT NULL DEFAULT '',
  `paikka` int(11) NOT NULL DEFAULT '0',
  `sijainti` varchar(60) NOT NULL DEFAULT '',
  `toimipiste` varchar(12) NOT NULL DEFAULT '',
  `ip_osoite` varchar(60) NOT NULL DEFAULT '',
  `mac_osoite` varchar(60) NOT NULL DEFAULT '',
  `lcm_info` varchar(60) NOT NULL DEFAULT '',
  `kommentti` text,
  `tila` char(1) NOT NULL DEFAULT '',
  `koodi` int(11) NOT NULL DEFAULT '0',
  `sla` int(4) NOT NULL DEFAULT '0',
  `sd_sla` varchar(60) NOT NULL DEFAULT '',
  `valmistajan_sopimusnumero` varchar(60) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valmistajan_sopimus_paattymispaiva` date NOT NULL DEFAULT '0000-00-00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_paikka` (`yhtio`,`paikka`),
  KEY `yhtio_koodi` (`yhtio`,`koodi`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laitteen_sopimukset`
--

DROP TABLE IF EXISTS `laitteen_sopimukset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laitteen_sopimukset` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `laitteen_tunnus` int(11) NOT NULL DEFAULT '0',
  `sopimusrivin_tunnus` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lasku`
--

DROP TABLE IF EXISTS `lasku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lasku` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `yhtio_nimi` varchar(60) NOT NULL DEFAULT '',
  `yhtio_osoite` varchar(55) NOT NULL DEFAULT '',
  `yhtio_postino` varchar(35) NOT NULL DEFAULT '',
  `yhtio_postitp` varchar(35) NOT NULL DEFAULT '',
  `yhtio_maa` varchar(35) NOT NULL DEFAULT '',
  `yhtio_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `yhtio_kotipaikka` varchar(25) NOT NULL DEFAULT '',
  `yhtio_toimipaikka` int(11) NOT NULL DEFAULT '0',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `nimitark` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(45) NOT NULL DEFAULT '',
  `osoitetark` varchar(45) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(45) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `puh` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `toim_nimi` varchar(60) NOT NULL DEFAULT '',
  `toim_nimitark` varchar(60) NOT NULL DEFAULT '',
  `toim_osoite` varchar(55) NOT NULL DEFAULT '',
  `toim_postino` varchar(35) NOT NULL DEFAULT '',
  `toim_postitp` varchar(35) NOT NULL DEFAULT '',
  `toim_maa` char(2) NOT NULL DEFAULT '',
  `toim_puh` varchar(50) NOT NULL DEFAULT '',
  `toim_email` varchar(100) NOT NULL DEFAULT '',
  `pankki_haltija` varchar(60) NOT NULL DEFAULT '',
  `tilinumero` varchar(14) NOT NULL DEFAULT '',
  `swift` varchar(11) NOT NULL DEFAULT '',
  `pankki1` varchar(35) NOT NULL DEFAULT '',
  `pankki2` varchar(35) NOT NULL DEFAULT '',
  `pankki3` varchar(35) NOT NULL DEFAULT '',
  `pankki4` varchar(35) NOT NULL DEFAULT '',
  `ultilno_maa` char(2) NOT NULL DEFAULT '',
  `ultilno` varchar(35) NOT NULL DEFAULT '',
  `clearing` varchar(35) NOT NULL DEFAULT '',
  `maksutyyppi` char(1) NOT NULL DEFAULT '',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lapvm` date NOT NULL DEFAULT '0000-00-00',
  `tapvm` date NOT NULL DEFAULT '0000-00-00',
  `kapvm` date NOT NULL DEFAULT '0000-00-00',
  `erpcm` date NOT NULL DEFAULT '0000-00-00',
  `suoraveloitus` char(1) NOT NULL DEFAULT '',
  `olmapvm` date NOT NULL DEFAULT '0000-00-00',
  `toimaika` date NOT NULL DEFAULT '0000-00-00',
  `toimvko` varchar(2) NOT NULL DEFAULT '',
  `kerayspvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `keraysvko` varchar(2) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `summa_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kasumma_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `hinta` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kate_korjattu` decimal(12,2) DEFAULT NULL,
  `arvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `arvo_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_maksettu` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_maksettu_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pyoristys` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pyoristys_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pyoristys_erot` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `pyoristys_erot_alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `luottoraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `erapaivan_ylityksen_summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `maksaja` varchar(50) NOT NULL DEFAULT '',
  `maksuaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lahetepvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lahetetyyppi` varchar(150) NOT NULL DEFAULT '',
  `laskutyyppi` int(2) NOT NULL DEFAULT '0',
  `laskuttaja` varchar(50) NOT NULL DEFAULT '',
  `laskutettu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvak1` varchar(50) NOT NULL DEFAULT '',
  `h1time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvak2` varchar(50) NOT NULL DEFAULT '',
  `h2time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvak3` varchar(50) NOT NULL DEFAULT '',
  `h3time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvak4` varchar(50) NOT NULL DEFAULT '',
  `h4time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvak5` varchar(50) NOT NULL DEFAULT '',
  `hyvaksyja_nyt` varchar(50) NOT NULL DEFAULT '',
  `h5time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hyvaksynnanmuutos` char(1) NOT NULL DEFAULT '',
  `prioriteettinro` int(11) NOT NULL DEFAULT '9',
  `vakisin_kerays` char(1) NOT NULL DEFAULT '',
  `viite` varchar(25) NOT NULL DEFAULT '',
  `laskunro` bigint(20) NOT NULL DEFAULT '0',
  `viesti` varchar(70) NOT NULL DEFAULT '',
  `sisviesti1` text,
  `sisviesti2` text,
  `sisviesti3` text,
  `comments` text,
  `ohjausmerkki` varchar(70) NOT NULL DEFAULT '',
  `tilausyhteyshenkilo` varchar(50) NOT NULL DEFAULT '',
  `asiakkaan_tilausnumero` varchar(50) NOT NULL DEFAULT '',
  `kohde` varchar(50) NOT NULL DEFAULT '',
  `myyja` int(11) NOT NULL DEFAULT '0',
  `allekirjoittaja` int(11) NOT NULL DEFAULT '0',
  `maksuehto` int(11) NOT NULL DEFAULT '0',
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `toimitusaikaikkuna` int(11) NOT NULL DEFAULT '0',
  `toimitustavan_lahto` int(11) NOT NULL DEFAULT '0',
  `toimitustavan_lahto_siirto` int(11) NOT NULL DEFAULT '0',
  `rahtivapaa` char(1) NOT NULL DEFAULT '',
  `rahtisopimus` varchar(12) NOT NULL DEFAULT '',
  `ebid` varchar(35) NOT NULL DEFAULT '',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `verkkotunnus` varchar(76) NOT NULL DEFAULT '',
  `ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `toim_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `chn` char(3) NOT NULL DEFAULT '',
  `mapvm` date NOT NULL DEFAULT '0000-00-00',
  `popvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vienti_kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `maksu_kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `maksu_tili` varchar(10) NOT NULL DEFAULT '',
  `alv_tili` varchar(6) NOT NULL DEFAULT '',
  `tila` char(1) NOT NULL DEFAULT '',
  `alatila` char(2) NOT NULL DEFAULT '',
  `huolitsija` varchar(30) NOT NULL DEFAULT '',
  `jakelu` varchar(30) NOT NULL DEFAULT '',
  `kuljetus` varchar(150) NOT NULL DEFAULT '',
  `maksuteksti` varchar(30) NOT NULL DEFAULT '',
  `valmistuksen_lisatiedot` text,
  `mainosteksti` text,
  `muutospvm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `vakuutus` varchar(30) NOT NULL DEFAULT '',
  `kassalipas` varchar(150) NOT NULL DEFAULT '',
  `ketjutus` char(1) NOT NULL DEFAULT '',
  `sisainen` char(1) NOT NULL DEFAULT '',
  `osatoimitus` char(1) NOT NULL DEFAULT '',
  `splittauskielto` char(1) NOT NULL DEFAULT '',
  `jtkielto` char(1) NOT NULL DEFAULT '',
  `tilaustyyppi` char(1) NOT NULL DEFAULT '',
  `eilahetetta` char(1) NOT NULL DEFAULT '',
  `tilausvahvistus` varchar(150) NOT NULL DEFAULT '',
  `laskutusvkopv` int(1) NOT NULL DEFAULT '0',
  `toimitusehto` varchar(60) NOT NULL DEFAULT '',
  `vienti` char(1) NOT NULL DEFAULT '',
  `kolmikantakauppa` char(1) NOT NULL DEFAULT '',
  `viitetxt` varchar(30) NOT NULL DEFAULT '',
  `ostotilauksen_kasittely` varchar(150) NOT NULL DEFAULT '',
  `erikoisale` decimal(5,2) NOT NULL DEFAULT '0.00',
  `erikoisale_saapuminen` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kerayslista` int(11) NOT NULL DEFAULT '0',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `viikorkopros` decimal(5,2) NOT NULL DEFAULT '0.00',
  `viikorkoeur` decimal(8,2) NOT NULL DEFAULT '0.00',
  `varasto` bigint(20) unsigned NOT NULL DEFAULT '0',
  `tulostusalue` varchar(15) NOT NULL DEFAULT '',
  `kirjoitin` varchar(100) NOT NULL DEFAULT '',
  `noutaja` varchar(50) NOT NULL DEFAULT '',
  `kohdistettu` char(1) NOT NULL DEFAULT '',
  `rahti_huolinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `rahti` decimal(9,2) NOT NULL DEFAULT '0.00',
  `rahti_etu` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `rahti_etu_alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `osto_rahti_alv` decimal(4,2) NOT NULL DEFAULT '0.00',
  `osto_kulu_alv` decimal(4,2) NOT NULL DEFAULT '0.00',
  `osto_rivi_kulu_alv` decimal(4,2) NOT NULL DEFAULT '0.00',
  `osto_rahti` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `osto_kulu` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `osto_rivi_kulu` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `maa_lahetys` char(2) NOT NULL DEFAULT '',
  `maa_maara` char(2) NOT NULL DEFAULT '',
  `maa_alkupera` char(2) NOT NULL DEFAULT '',
  `kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `kauppatapahtuman_luonne` int(2) NOT NULL DEFAULT '0',
  `bruttopaino` decimal(8,2) NOT NULL DEFAULT '0.00',
  `sisamaan_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `sisamaan_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `aktiivinen_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `kontti` int(1) NOT NULL DEFAULT '0',
  `valmistuksen_tila` char(2) NOT NULL DEFAULT '',
  `aktiivinen_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `poistumistoimipaikka` varchar(80) NOT NULL DEFAULT '',
  `poistumistoimipaikka_koodi` varchar(8) NOT NULL DEFAULT '',
  `aiotut_rajatoimipaikat` varchar(255) NOT NULL DEFAULT '',
  `maaratoimipaikka` varchar(255) NOT NULL DEFAULT '',
  `lisattava_era` decimal(8,2) NOT NULL DEFAULT '0.00',
  `vahennettava_era` decimal(8,2) NOT NULL DEFAULT '0.00',
  `tullausnumero` varchar(25) NOT NULL DEFAULT '',
  `vientipaperit_palautettu` char(1) NOT NULL DEFAULT '',
  `piiri` varchar(150) NOT NULL DEFAULT '',
  `tilaus_valmis_toiminto` varchar(100) NOT NULL DEFAULT '',
  `tarkista_ennen_laskutusta` varchar(1) NOT NULL DEFAULT '',
  `lahetetty_ulkoiseen_varastoon` datetime DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `siirtolistan_vastaanotto` int(11) NOT NULL DEFAULT '0',
  `varastosiirto_tunnus` int(11) NOT NULL DEFAULT '0',
  `pakkaamo` int(11) NOT NULL DEFAULT '0',
  `jaksotettu` int(11) NOT NULL DEFAULT '0',
  `factoringsiirtonumero` int(11) NOT NULL DEFAULT '0',
  `directdebitsiirtonumero` int(11) NOT NULL DEFAULT '0',
  `ohjelma_moduli` varchar(50) NOT NULL DEFAULT 'PUPESOFT',
  `label` int(11) NOT NULL DEFAULT '0',
  `tunnusnippu` int(11) NOT NULL DEFAULT '0',
  `vanhatunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tila_index` (`yhtio`,`tila`,`alatila`),
  KEY `alatila_index` (`yhtio`,`alatila`),
  KEY `lasno_index` (`yhtio`,`laskunro`),
  KEY `yhtio_tila_luontiaika` (`yhtio`,`tila`,`luontiaika`),
  KEY `yhtio_ytunnus` (`yhtio`,`ytunnus`),
  KEY `yhtio_hyvaksyjanyt` (`yhtio`,`hyvaksyja_nyt`),
  KEY `kohdistus_index` (`yhtio`,`tila`,`vienti`,`kohdistettu`),
  KEY `yhtio_tila_tapvm` (`yhtio`,`tila`,`tapvm`),
  KEY `yhtio_vanhatunnus` (`yhtio`,`vanhatunnus`),
  KEY `yhtio_tila_ytunnus_tapvm` (`yhtio`,`tila`,`ytunnus`,`tapvm`),
  KEY `yhtio_tila_mapvm` (`yhtio`,`tila`,`mapvm`),
  KEY `yhtio_tila_summa` (`yhtio`,`tila`,`summa`),
  KEY `yhtio_tila_laskunro` (`yhtio`,`tila`,`laskunro`),
  KEY `yhtio_liitostunnus` (`yhtio`,`liitostunnus`),
  KEY `yhtio_tila_kerayslista` (`yhtio`,`tila`,`kerayslista`),
  KEY `yhtio_tila_liitostunnus_tapvm` (`yhtio`,`tila`,`liitostunnus`,`tapvm`),
  KEY `yhtio_jaksotettu` (`yhtio`,`jaksotettu`),
  KEY `yhtio_tunnusnippu` (`yhtio`,`tunnusnippu`),
  KEY `yhtio_tila_summavaluutassa` (`yhtio`,`tila`,`summa_valuutassa`),
  KEY `yhtio_asiakkaan_tilausnumero` (`yhtio`,`asiakkaan_tilausnumero`),
  KEY `factoring` (`yhtio`,`tila`,`factoringsiirtonumero`),
  KEY `yhtio_tila_olmapvm` (`yhtio`,`tila`,`olmapvm`),
  KEY `yhtio_lahto` (`yhtio`,`toimitustavan_lahto`),
  KEY `tila_viite` (`yhtio`,`tila`,`viite`),
  KEY `index_lasku_on_yhtio_and_tila_and_erpcm` (`yhtio`,`tila`,`erpcm`),
  FULLTEXT KEY `asiakasnimi` (`nimi`),
  FULLTEXT KEY `asiakastoim_nimi` (`toim_nimi`)
) ENGINE=MyISAM AUTO_INCREMENT=150 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laskun_lisatiedot`
--

DROP TABLE IF EXISTS `laskun_lisatiedot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laskun_lisatiedot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `luontitapa` varchar(30) NOT NULL DEFAULT '',
  `rahlaskelma_rahoitettava_positio` int(11) NOT NULL DEFAULT '0',
  `rahlaskelma_jaannosvelka_vaihtokohteesta` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_perustamiskustannus` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_muutluottokustannukset` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_sopajankorko` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_maksuerienlkm` int(4) NOT NULL DEFAULT '0',
  `rahlaskelma_luottoaikakk` int(4) NOT NULL DEFAULT '0',
  `rahlaskelma_ekaerpcm` date NOT NULL DEFAULT '0000-00-00',
  `rahlaskelma_erankasittelymaksu` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_tilinavausmaksu` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_viitekorko` varchar(20) NOT NULL DEFAULT '',
  `rahlaskelma_marginaalikorko` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_lyhennystapa` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_poikkeava_era` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rahlaskelma_nfref` int(11) NOT NULL DEFAULT '0',
  `vakuutushak_vakuutusyhtio` varchar(50) NOT NULL DEFAULT '',
  `vakuutushak_alkamispaiva` date NOT NULL DEFAULT '0000-00-00',
  `vakuutushak_kaskolaji` varchar(50) NOT NULL DEFAULT '',
  `vakuutushak_maksuerat` int(3) NOT NULL DEFAULT '0',
  `vakuutushak_perusomavastuu` decimal(8,2) NOT NULL DEFAULT '0.00',
  `vakuutushak_runko_takila_purjeet` decimal(8,2) NOT NULL DEFAULT '0.00',
  `vakuutushak_moottori` decimal(8,2) NOT NULL DEFAULT '0.00',
  `vakuutushak_varusteet` decimal(8,2) NOT NULL DEFAULT '0.00',
  `vakuutushak_yhteensa` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rekisteilmo_rekisterinumero` varchar(20) NOT NULL DEFAULT '',
  `rekisteilmo_paakayttokunta` varchar(50) NOT NULL DEFAULT '',
  `rekisteilmo_kieli` varchar(50) NOT NULL DEFAULT '',
  `rekisteilmo_tyyppi` varchar(50) NOT NULL DEFAULT '',
  `rekisteilmo_omistajienlkm` int(3) NOT NULL DEFAULT '0',
  `rekisteilmo_omistajankotikunta` varchar(50) NOT NULL DEFAULT '',
  `rekisteilmo_lisatietoja` varchar(150) NOT NULL DEFAULT '',
  `rekisteilmo_laminointi` char(1) NOT NULL DEFAULT '',
  `rekisteilmo_suoramarkkinointi` char(1) NOT NULL DEFAULT '',
  `rekisteilmo_veneen_nimi` varchar(150) NOT NULL DEFAULT '',
  `rekisteilmo_omistaja` varchar(55) NOT NULL DEFAULT '',
  `kolm_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `kolm_nimi` varchar(60) NOT NULL DEFAULT '',
  `kolm_nimitark` varchar(60) NOT NULL DEFAULT '',
  `kolm_osoite` varchar(55) NOT NULL DEFAULT '',
  `kolm_postino` varchar(15) NOT NULL DEFAULT '',
  `kolm_postitp` varchar(35) NOT NULL DEFAULT '',
  `kolm_maa` varchar(35) NOT NULL DEFAULT '',
  `laskutus_nimi` varchar(60) NOT NULL DEFAULT '',
  `laskutus_nimitark` varchar(60) NOT NULL DEFAULT '',
  `laskutus_osoite` varchar(60) NOT NULL DEFAULT '',
  `laskutus_postino` varchar(60) NOT NULL DEFAULT '',
  `laskutus_postitp` varchar(60) NOT NULL DEFAULT '',
  `laskutus_maa` varchar(60) NOT NULL DEFAULT '',
  `toimitusehto2` varchar(60) NOT NULL DEFAULT '',
  `kasinsyotetty_viite` varchar(25) NOT NULL DEFAULT '',
  `asiakkaan_kohde` int(11) NOT NULL DEFAULT '0',
  `kantaasiakastunnus` varchar(255) NOT NULL DEFAULT '',
  `ulkoinen_tarkenne` varchar(30) NOT NULL DEFAULT '',
  `noutopisteen_tunnus` varchar(12) NOT NULL DEFAULT '',
  `saate` text,
  `yhteyshenkilo_kaupallinen` int(11) NOT NULL DEFAULT '0',
  `yhteyshenkilo_tekninen` int(11) NOT NULL DEFAULT '0',
  `rahlaskelma_hetu_tarkistus` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_tarkastaja` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_asiakirjamyontaja` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_asiakirjanro` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_kolm_tarkistus` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_kolm_tarkastaja` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_kolm_asiakirjanro` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_hetu_kolm_asiakirjamyontaja` varchar(150) NOT NULL DEFAULT '',
  `rahlaskelma_takuukirja` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_huoltokirja` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_kayttoohjeet` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_opastus` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_kuntotestitodistus` char(1) NOT NULL DEFAULT '',
  `rahlaskelma_kayttotarkoitus` char(1) NOT NULL DEFAULT '',
  `sopimus_kk` varchar(90) NOT NULL DEFAULT '',
  `sopimus_pp` varchar(90) NOT NULL DEFAULT '',
  `sopimus_alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `sopimus_loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `sopimus_lisatietoja` text,
  `sopimus_lisatietoja2` text,
  `sopimus_numero` varchar(50) DEFAULT '',
  `projektipaallikko` varchar(50) NOT NULL DEFAULT '',
  `seuranta` varchar(5) NOT NULL DEFAULT '',
  `tunnusnippu_tarjous` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `rivihintoja_ei_nayteta` char(1) NOT NULL DEFAULT '',
  `yllapito_kuukausihinnoittelu` char(1) DEFAULT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_otunnus` (`yhtio`,`otunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=132 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `liitetiedostot`
--

DROP TABLE IF EXISTS `liitetiedostot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liitetiedostot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitos` varchar(50) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `data` longblob,
  `selite` text,
  `kieli` varchar(2) NOT NULL DEFAULT '',
  `filename` varchar(50) NOT NULL DEFAULT '',
  `filesize` varchar(50) NOT NULL DEFAULT '',
  `filetype` varchar(50) NOT NULL DEFAULT '',
  `image_width` int(5) NOT NULL DEFAULT '0',
  `image_height` int(5) NOT NULL DEFAULT '0',
  `image_bits` int(5) NOT NULL DEFAULT '0',
  `image_channels` int(5) NOT NULL DEFAULT '0',
  `kayttotarkoitus` varchar(150) NOT NULL DEFAULT '',
  `external_id` varchar(25) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_liitos_liitostunnus` (`yhtio`,`liitos`,`liitostunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maat`
--

DROP TABLE IF EXISTS `maat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maat` (
  `koodi` char(2) NOT NULL DEFAULT '',
  `iso3` char(3) NOT NULL DEFAULT '',
  `nimi` varchar(80) NOT NULL DEFAULT '',
  `name` varchar(150) NOT NULL DEFAULT '',
  `eu` char(2) NOT NULL DEFAULT '',
  `ryhma_tunnus` varchar(4) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `koodi_ryhma` (`koodi`,`ryhma_tunnus`),
  KEY `koodi_nimi` (`koodi`,`nimi`)
) ENGINE=MyISAM AUTO_INCREMENT=8889 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mail_servers`
--

DROP TABLE IF EXISTS `mail_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `imap_server` varchar(255) DEFAULT NULL,
  `imap_username` varchar(255) DEFAULT NULL,
  `imap_password` varchar(255) DEFAULT NULL,
  `smtp_server` varchar(255) DEFAULT NULL,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `process_dir` varchar(255) DEFAULT NULL,
  `done_dir` varchar(255) DEFAULT NULL,
  `processing_type` varchar(255) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_mail_servers_on_company_id` (`company_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maksu`
--

DROP TABLE IF EXISTS `maksu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maksu` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `konserni` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `tapvm` date NOT NULL DEFAULT '0000-00-00',
  `tyyppi` char(2) NOT NULL DEFAULT '',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selite` varchar(50) NOT NULL DEFAULT '',
  `maksettu` char(1) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maksuehto`
--

DROP TABLE IF EXISTS `maksuehto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maksuehto` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `teksti` varchar(40) NOT NULL DEFAULT '',
  `rel_pvm` int(3) NOT NULL DEFAULT '0',
  `abs_pvm` date DEFAULT NULL,
  `kassa_relpvm` int(3) NOT NULL DEFAULT '0',
  `kassa_abspvm` date DEFAULT NULL,
  `kassa_alepros` decimal(5,2) NOT NULL DEFAULT '0.00',
  `jv` char(1) NOT NULL DEFAULT '',
  `kateinen` char(1) NOT NULL DEFAULT '',
  `factoring_id` int(11) DEFAULT NULL,
  `directdebit_id` int(11) DEFAULT NULL,
  `pankkiyhteystiedot` int(11) NOT NULL DEFAULT '0',
  `itsetulostus` char(1) NOT NULL DEFAULT '',
  `jaksotettu` char(1) NOT NULL DEFAULT '',
  `erapvmkasin` char(1) NOT NULL DEFAULT '',
  `sallitut_maat` varchar(50) NOT NULL DEFAULT '',
  `kaytossa` char(1) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=115 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maksupaatetapahtumat`
--

DROP TABLE IF EXISTS `maksupaatetapahtumat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maksupaatetapahtumat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `maksutapa` varchar(50) NOT NULL DEFAULT '',
  `summa_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `tila` char(1) NOT NULL DEFAULT '',
  `asiakkaan_kuitti` text,
  `kauppiaan_kuitti` text,
  `tilausnumero` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maksupositio`
--

DROP TABLE IF EXISTS `maksupositio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maksupositio` (
  `yhtio` varchar(6) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `positio` int(2) NOT NULL DEFAULT '0',
  `maksuehto` int(11) NOT NULL DEFAULT '0',
  `lisatiedot` varchar(150) NOT NULL DEFAULT '',
  `osuus` decimal(7,4) NOT NULL DEFAULT '0.0000',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kuvaus` varchar(150) NOT NULL DEFAULT '',
  `ohje` text,
  `erpcm` date NOT NULL DEFAULT '0000-00-00',
  `luotu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `luonut` varchar(50) NOT NULL DEFAULT '',
  `laskunro` int(11) NOT NULL DEFAULT '0',
  `uusiotunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messenger`
--

DROP TABLE IF EXISTS `messenger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messenger` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `vastaanottaja` varchar(50) NOT NULL DEFAULT '',
  `ryhma` varchar(50) NOT NULL DEFAULT '',
  `viesti` text,
  `status` char(1) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_kuka_status` (`yhtio`,`kuka`,`status`),
  KEY `yhtio_vastaanottaja_status` (`yhtio`,`vastaanottaja`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `muisti`
--

DROP TABLE IF EXISTS `muisti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `muisti` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `haku` varchar(20) NOT NULL DEFAULT '',
  `nimi` varchar(50) NOT NULL DEFAULT '',
  `kuvaus` text,
  `var` varchar(50) NOT NULL DEFAULT '',
  `value` text,
  `array` char(1) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `muokattu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muokannut` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `haut` (`yhtio`,`kuka`,`haku`,`nimi`,`var`)
) ENGINE=MyISAM AUTO_INCREMENT=143 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oikeu`
--

DROP TABLE IF EXISTS `oikeu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oikeu` (
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `user_id` int(11) DEFAULT NULL,
  `sovellus` varchar(50) NOT NULL DEFAULT '',
  `nimi` varchar(100) NOT NULL DEFAULT '',
  `alanimi` varchar(100) NOT NULL DEFAULT '',
  `paivitys` char(1) NOT NULL DEFAULT '',
  `lukittu` char(1) NOT NULL DEFAULT '',
  `nimitys` varchar(60) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `jarjestys2` int(11) NOT NULL DEFAULT '0',
  `profiili` varchar(100) NOT NULL DEFAULT '',
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `hidden` char(1) NOT NULL DEFAULT '',
  `usermanualurl` varchar(255) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `oikeudet_index` (`yhtio`,`kuka`,`sovellus`,`nimi`,`alanimi`),
  KEY `sovellus_index` (`yhtio`,`kuka`,`sovellus`),
  KEY `menut_index` (`yhtio`,`sovellus`,`nimi`,`alanimi`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=63754 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ostorivien_vahvistus`
--

DROP TABLE IF EXISTS `ostorivien_vahvistus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ostorivien_vahvistus` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tilausrivin_tunnus` int(11) NOT NULL DEFAULT '0',
  `vahvistettu` varchar(1) NOT NULL DEFAULT '0',
  `vahvistettuaika` datetime NOT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_rivitunnus` (`yhtio`,`tilausrivin_tunnus`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pakkaamo`
--

DROP TABLE IF EXISTS `pakkaamo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pakkaamo` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(150) NOT NULL DEFAULT '',
  `lokero` varchar(5) NOT NULL DEFAULT '',
  `prio` int(11) NOT NULL DEFAULT '0',
  `pakkaamon_prio` int(11) NOT NULL DEFAULT '0',
  `varasto` int(11) NOT NULL DEFAULT '0',
  `printteri0` varchar(20) NOT NULL DEFAULT '',
  `printteri1` varchar(20) NOT NULL DEFAULT '',
  `printteri2` varchar(20) NOT NULL DEFAULT '',
  `printteri3` varchar(20) NOT NULL DEFAULT '',
  `printteri4` varchar(20) NOT NULL DEFAULT '',
  `printteri6` varchar(20) NOT NULL DEFAULT '',
  `printteri7` varchar(20) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pakkaus`
--

DROP TABLE IF EXISTS `pakkaus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pakkaus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `pakkaus` varchar(50) NOT NULL DEFAULT '',
  `pakkauskuvaus` varchar(50) NOT NULL DEFAULT '',
  `pakkausveloitus_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `erikoispakkaus` char(1) NOT NULL DEFAULT '',
  `korkeus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `leveys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `syvyys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `paino` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `oma_paino` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `minimi_tilavuus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `minimi_paino` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `kayttoprosentti` tinyint(4) NOT NULL DEFAULT '100',
  `yksin_eraan` char(1) NOT NULL DEFAULT '',
  `puukotuskerroin` decimal(4,3) NOT NULL DEFAULT '0.000',
  `rahtivapaa_veloitus` char(1) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_pakkaus_pakkauskuvaus` (`yhtio`,`pakkaus`,`pakkauskuvaus`)
) ENGINE=MyISAM AUTO_INCREMENT=780 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pakkauskoodit`
--

DROP TABLE IF EXISTS `pakkauskoodit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pakkauskoodit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `pakkaus` int(11) NOT NULL DEFAULT '0',
  `rahdinkuljettaja` varchar(40) NOT NULL DEFAULT '',
  `koodi` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pankkiyhteys`
--

DROP TABLE IF EXISTS `pankkiyhteys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pankkiyhteys` (
  `yhtio` varchar(5) DEFAULT NULL,
  `pankki` varchar(60) DEFAULT NULL,
  `customer_id` varchar(60) DEFAULT NULL,
  `hae_saldo` tinyint(1) NOT NULL DEFAULT '0',
  `hae_factoring` tinyint(1) NOT NULL DEFAULT '0',
  `hae_laskut` tinyint(1) NOT NULL DEFAULT '0',
  `signing_certificate` text,
  `signing_certificate_valid_to` datetime DEFAULT NULL,
  `signing_private_key` text,
  `encryption_certificate` text,
  `encryption_certificate_valid_to` datetime DEFAULT NULL,
  `encryption_private_key` text,
  `bank_encryption_certificate` text,
  `bank_encryption_certificate_valid_to` datetime DEFAULT NULL,
  `bank_root_certificate` text,
  `bank_root_certificate_valid_to` datetime DEFAULT NULL,
  `ca_certificate` text,
  `ca_certificate_valid_to` datetime DEFAULT NULL,
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pankkiyhteystiedot`
--

DROP TABLE IF EXISTS `pankkiyhteystiedot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pankkiyhteystiedot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimitys` varchar(80) NOT NULL DEFAULT '',
  `pankkinimi1` varchar(80) NOT NULL DEFAULT '',
  `pankkitili1` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban1` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift1` varchar(60) NOT NULL DEFAULT '',
  `pankkinimi2` varchar(80) NOT NULL DEFAULT '',
  `pankkitili2` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban2` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift2` varchar(60) NOT NULL DEFAULT '',
  `pankkinimi3` varchar(80) NOT NULL DEFAULT '',
  `pankkitili3` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban3` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift3` varchar(60) NOT NULL DEFAULT '',
  `viite` varchar(2) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `panttitili`
--

DROP TABLE IF EXISTS `panttitili`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `panttitili` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `asiakas` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `status` char(1) NOT NULL DEFAULT '',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `erikoisale` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale1` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale2` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale3` decimal(5,2) NOT NULL DEFAULT '0.00',
  `myyntipvm` date NOT NULL DEFAULT '0000-00-00',
  `myyntitilausnro` int(11) NOT NULL DEFAULT '0',
  `kaytettypvm` date NOT NULL DEFAULT '0000-00-00',
  `kaytettytilausnro` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_tuoteno_asiakas_status` (`yhtio`,`tuoteno`,`asiakas`,`status`),
  KEY `yhtio_status_myyntipvm` (`yhtio`,`status`,`myyntipvm`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pending_updates`
--

DROP TABLE IF EXISTS `pending_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pending_updatable_id` int(11) DEFAULT NULL,
  `pending_updatable_type` varchar(255) DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `index_pending_updates_on_pending_updatable_id` (`pending_updatable_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `perusalennus`
--

DROP TABLE IF EXISTS `perusalennus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perusalennus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `ryhma` varchar(15) NOT NULL DEFAULT '',
  `selite` varchar(50) NOT NULL DEFAULT '',
  `alennus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_ryhma` (`yhtio`,`ryhma`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `puun_alkio`
--

DROP TABLE IF EXISTS `puun_alkio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `puun_alkio` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitos` varchar(60) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `laji` varchar(150) NOT NULL DEFAULT '',
  `kutsuja` varchar(150) NOT NULL DEFAULT '',
  `puun_tunnus` int(11) NOT NULL DEFAULT '0',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_laji_liitos_puuntunnus_kieli` (`yhtio`,`liitos`,`laji`,`puun_tunnus`,`kieli`),
  KEY `yhtio_laji_liitos` (`yhtio`,`laji`,`liitos`),
  KEY `yhtio_laji_puun_tunnus` (`yhtio`,`laji`,`puun_tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rahdinkuljettajat`
--

DROP TABLE IF EXISTS `rahdinkuljettajat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rahdinkuljettajat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `koodi` varchar(50) NOT NULL DEFAULT '',
  `nimi` varchar(50) NOT NULL DEFAULT '',
  `neutraali` char(1) NOT NULL DEFAULT '',
  `pakkauksen_sarman_minimimitta` decimal(5,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rahtikirjanumero`
--

DROP TABLE IF EXISTS `rahtikirjanumero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rahtikirjanumero` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `rahtikirjanro` varchar(150) NOT NULL DEFAULT '',
  `kayttaja` varchar(50) NOT NULL DEFAULT '',
  `kaytettyaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rahtikirjat`
--

DROP TABLE IF EXISTS `rahtikirjat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rahtikirjat` (
  `kilot` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `kollit` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `kuutiot` decimal(7,4) NOT NULL DEFAULT '0.0000',
  `lavametri` decimal(5,2) NOT NULL DEFAULT '0.00',
  `merahti` char(1) NOT NULL DEFAULT '',
  `pakkaus` varchar(50) NOT NULL DEFAULT '',
  `pakkauskuvaus` varchar(50) NOT NULL DEFAULT '',
  `pakkauskuvaustark` varchar(50) NOT NULL DEFAULT '',
  `poikkeava` int(1) NOT NULL DEFAULT '0',
  `rahtisopimus` varchar(12) NOT NULL DEFAULT '',
  `otsikkonro` int(11) NOT NULL DEFAULT '0',
  `pakkaustieto_tunnukset` text,
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `viitelah` varchar(30) NOT NULL DEFAULT '',
  `viitevas` varchar(30) NOT NULL DEFAULT '',
  `viesti` text,
  `tulostuspaikka` bigint(20) unsigned NOT NULL DEFAULT '0',
  `tulostustapa` char(1) NOT NULL DEFAULT '',
  `tulostettu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rahtikirjanro` text,
  `sscc_ulkoinen` varchar(150) NOT NULL DEFAULT '',
  `tyhjanrahtikirjan_otsikkotiedot` text,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `otsikko_index` (`yhtio`,`otsikkonro`),
  KEY `rahtikirjanro` (`yhtio`,`rahtikirjanro`(150))
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rahtimaksut`
--

DROP TABLE IF EXISTS `rahtimaksut`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rahtimaksut` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `kilotalku` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kilotloppu` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kmalku` int(5) NOT NULL DEFAULT '0',
  `kmloppu` int(5) NOT NULL DEFAULT '0',
  `rahtihinta` decimal(12,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rahtisopimukset`
--

DROP TABLE IF EXISTS `rahtisopimukset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rahtisopimukset` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `asiakas` int(11) NOT NULL DEFAULT '0',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `rahtisopimus` varchar(12) NOT NULL DEFAULT '',
  `selite` varchar(50) NOT NULL DEFAULT '',
  `muumaksaja` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sahkoisen_lahetteen_rivit`
--

DROP TABLE IF EXISTS `sahkoisen_lahetteen_rivit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sahkoisen_lahetteen_rivit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `tilausrivin_tunnus` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `myyntihinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `ale` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rekisterinumero` varchar(7) NOT NULL DEFAULT '',
  `status` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_otunnus_tilausrivin_tunnus` (`yhtio`,`otunnus`,`tilausrivin_tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saldovahvistukset`
--

DROP TABLE IF EXISTS `saldovahvistukset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saldovahvistukset` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `lahetys_pvm` date DEFAULT NULL,
  `saldovahvistus_viesti` varchar(150) NOT NULL DEFAULT '',
  `avoin_saldo_pvm` date DEFAULT NULL,
  `ryhmittely_tyyppi` varchar(100) DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saldovahvistusrivit`
--

DROP TABLE IF EXISTS `saldovahvistusrivit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saldovahvistusrivit` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `saldovahvistus_tunnus` int(11) NOT NULL DEFAULT '0',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `lasku_tunnus` int(11) NOT NULL DEFAULT '0',
  `laskunro` int(11) NOT NULL DEFAULT '0',
  `tapahtuma_pvm` date DEFAULT NULL,
  `era_pvm` date DEFAULT NULL,
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sanakirja`
--

DROP TABLE IF EXISTS `sanakirja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sanakirja` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `fi` text,
  `se` text,
  `no` text,
  `en` text,
  `de` text,
  `dk` text,
  `ru` text,
  `ee` text,
  `aikaleima` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kysytty` bigint(20) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `synkronoi` char(1) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `fi` (`fi`(50))
) ENGINE=MyISAM AUTO_INCREMENT=12983 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sarjanumeroseuranta`
--

DROP TABLE IF EXISTS `sarjanumeroseuranta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sarjanumeroseuranta` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `sarjanumero` varchar(150) NOT NULL DEFAULT '',
  `lisatieto` text,
  `ostorivitunnus` int(11) NOT NULL DEFAULT '0',
  `myyntirivitunnus` int(11) NOT NULL DEFAULT '0',
  `panttirivitunnus` int(11) DEFAULT NULL,
  `siirtorivitunnus` int(11) NOT NULL DEFAULT '0',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `varasto` int(11) DEFAULT NULL,
  `takuu_alku` date NOT NULL DEFAULT '0000-00-00',
  `takuu_loppu` date NOT NULL DEFAULT '0000-00-00',
  `parasta_ennen` date NOT NULL DEFAULT '0000-00-00',
  `perheid` int(11) NOT NULL DEFAULT '0',
  `kaytetty` char(1) NOT NULL DEFAULT '',
  `era_kpl` decimal(8,2) NOT NULL DEFAULT '0.00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `inventointitunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_sarjanumero` (`yhtio`,`sarjanumero`),
  KEY `perheid` (`yhtio`,`perheid`),
  KEY `yhtio_myyntirivi` (`yhtio`,`tuoteno`,`myyntirivitunnus`),
  KEY `yhtio_ostorivi` (`yhtio`,`tuoteno`,`ostorivitunnus`),
  KEY `yhtio_siirtorivitunnus` (`yhtio`,`tuoteno`,`siirtorivitunnus`),
  KEY `yhtio_tuoteno_sarjanumero` (`yhtio`,`tuoteno`,`sarjanumero`),
  FULLTEXT KEY `yhtio_lisatieto` (`yhtio`,`lisatieto`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sarjanumeroseuranta_arvomuutos`
--

DROP TABLE IF EXISTS `sarjanumeroseuranta_arvomuutos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sarjanumeroseuranta_arvomuutos` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `sarjanumerotunnus` bigint(20) unsigned NOT NULL DEFAULT '0',
  `arvomuutos` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `selite` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `version` varchar(255) NOT NULL,
  UNIQUE KEY `unique_schema_migrations` (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suorituksen_kohdistus`
--

DROP TABLE IF EXISTS `suorituksen_kohdistus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suorituksen_kohdistus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `suoritustunnus` int(11) DEFAULT NULL,
  `laskutunnus` int(11) DEFAULT NULL,
  `kaatosumma` decimal(12,2) DEFAULT NULL,
  `kohdistuspvm` date DEFAULT NULL,
  `kirjauspvm` date DEFAULT NULL,
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `laskutunnus_index` (`yhtio`,`laskutunnus`),
  KEY `suoritustunnus_index` (`yhtio`,`suoritustunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suoritus`
--

DROP TABLE IF EXISTS `suoritus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suoritus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tilino` varchar(35) NOT NULL DEFAULT '',
  `tilino_maksaja` varchar(35) NOT NULL DEFAULT '',
  `nimi_maksaja` varchar(12) NOT NULL DEFAULT '',
  `viite` varchar(25) NOT NULL DEFAULT '',
  `viesti` text,
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `maksupvm` date NOT NULL DEFAULT '0000-00-00',
  `kirjpvm` date NOT NULL DEFAULT '0000-00-00',
  `kohdpvm` date NOT NULL DEFAULT '0000-00-00',
  `asiakas_tunnus` int(11) NOT NULL DEFAULT '0',
  `ltunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_viite` (`yhtio`,`viite`),
  KEY `yhtio_asiakastunnus` (`yhtio`,`asiakas_tunnus`),
  KEY `tositerivit_index` (`ltunnus`),
  KEY `yhtio_kohdpvm` (`yhtio`,`kohdpvm`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suorituskykyloki`
--

DROP TABLE IF EXISTS `suorituskykyloki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suorituskykyloki` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `skripti` varchar(100) NOT NULL DEFAULT '',
  `suoritusalku` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `suoritusloppu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `suoritusaika` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `request` longtext,
  `iposoite` varchar(15) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_luontiaika` (`yhtio`,`luontiaika`),
  KEY `yhtio_skripti_luontiaika` (`yhtio`,`skripti`,`luontiaika`)
) ENGINE=MyISAM AUTO_INCREMENT=430 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplier_product_informations`
--

DROP TABLE IF EXISTS `supplier_product_informations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_product_informations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(100) DEFAULT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `manufacturer_ean` varchar(13) DEFAULT NULL,
  `manufacturer_name` varchar(100) DEFAULT NULL,
  `manufacturer_id` int(11) DEFAULT NULL,
  `manufacturer_part_number` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_ean` varchar(13) DEFAULT NULL,
  `supplier_part_number` varchar(100) DEFAULT NULL,
  `product_status` varchar(100) DEFAULT NULL,
  `short_description` varchar(250) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `category_text1` varchar(100) DEFAULT NULL,
  `category_text2` varchar(100) DEFAULT NULL,
  `category_text3` varchar(100) DEFAULT NULL,
  `category_text4` varchar(100) DEFAULT NULL,
  `category_idn` int(11) DEFAULT NULL,
  `net_price` decimal(16,6) DEFAULT NULL,
  `net_retail_price` decimal(16,6) DEFAULT NULL,
  `available_quantity` int(11) DEFAULT NULL,
  `available_next_date` date DEFAULT NULL,
  `available_next_quantity` int(11) DEFAULT NULL,
  `warranty_months` int(11) DEFAULT NULL,
  `gross_mass` decimal(8,4) DEFAULT NULL,
  `end_of_life` tinyint(1) NOT NULL DEFAULT '0',
  `returnable` tinyint(1) NOT NULL DEFAULT '0',
  `cancelable` tinyint(1) NOT NULL DEFAULT '0',
  `warranty_text` varchar(100) DEFAULT NULL,
  `packaging_unit` varchar(100) DEFAULT NULL,
  `vat_rate` decimal(4,2) DEFAULT NULL,
  `bid_price_id` int(11) DEFAULT NULL,
  `url_to_product` varchar(150) DEFAULT NULL,
  `p_product_id` int(11) DEFAULT NULL,
  `p_price_update` int(11) DEFAULT NULL,
  `p_qty_update` int(11) DEFAULT NULL,
  `p_added_date` datetime DEFAULT NULL,
  `p_last_update_date` datetime DEFAULT NULL,
  `p_nakyvyys` varchar(100) DEFAULT NULL,
  `p_tree_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suuntalavat`
--

DROP TABLE IF EXISTS `suuntalavat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suuntalavat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tila` char(1) NOT NULL DEFAULT '',
  `sscc` varchar(150) NOT NULL DEFAULT '',
  `keikkatunnus` varchar(150) NOT NULL DEFAULT '',
  `alkuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllyvali` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllytaso` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllyvali` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllytaso` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` int(11) NOT NULL DEFAULT '0',
  `keraysvyohyke` int(11) NOT NULL DEFAULT '0',
  `usea_keraysvyohyke` char(1) NOT NULL DEFAULT '',
  `kaytettavyys` char(1) NOT NULL DEFAULT '',
  `kasittelytapa` char(1) NOT NULL DEFAULT '',
  `terminaalialue` varchar(150) NOT NULL DEFAULT '',
  `korkeus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `paino` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tila_keraysvyohyke_keikkatunnus` (`yhtio`,`tila`,`keraysvyohyke`,`keikkatunnus`),
  KEY `yhtio_tila_keraysvyohyke_kaytettavyys` (`yhtio`,`tila`,`keraysvyohyke`,`kaytettavyys`),
  KEY `usea_keraysvyohyke` (`yhtio`,`tila`,`usea_keraysvyohyke`,`kaytettavyys`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suuntalavat_saapuminen`
--

DROP TABLE IF EXISTS `suuntalavat_saapuminen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suuntalavat_saapuminen` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `suuntalava` int(11) NOT NULL DEFAULT '0',
  `saapuminen` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `suuntalavat_saapuminen` (`yhtio`,`suuntalava`,`saapuminen`),
  KEY `saapuminen` (`yhtio`,`saapuminen`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `synclog`
--

DROP TABLE IF EXISTS `synclog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `synclog` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `taulu` varchar(20) NOT NULL DEFAULT '',
  `tauluntunnus` int(11) NOT NULL DEFAULT '0',
  `tapa` varchar(10) NOT NULL DEFAULT '',
  `viesti` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tallennetut_parametrit`
--

DROP TABLE IF EXISTS `tallennetut_parametrit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tallennetut_parametrit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `sovellus` varchar(100) NOT NULL DEFAULT '',
  `data` longtext,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `muisti` (`yhtio`,`kuka`,`sovellus`,`nimitys`)
) ENGINE=MyISAM AUTO_INCREMENT=112 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tapahtuma`
--

DROP TABLE IF EXISTS `tapahtuma`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tapahtuma` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `laji` varchar(15) NOT NULL DEFAULT '',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `kplhinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `selite` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `laadittu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rivitunnus` int(11) NOT NULL DEFAULT '0',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `varasto` int(11) DEFAULT NULL,
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tuote_laadittu` (`yhtio`,`tuoteno`,`laadittu`),
  KEY `yhtio_laji_rivitunnus` (`yhtio`,`laji`,`rivitunnus`),
  KEY `yhtio_laji_tuoteno` (`yhtio`,`laji`,`tuoteno`),
  KEY `yhtio_laadittu_hyllyalue_hyllynro` (`yhtio`,`laadittu`,`hyllyalue`,`hyllynro`),
  KEY `yhtio_laji_laadittu` (`yhtio`,`laji`,`laadittu`)
) ENGINE=MyISAM AUTO_INCREMENT=223 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taric_veroperusteet`
--

DROP TABLE IF EXISTS `taric_veroperusteet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taric_veroperusteet` (
  `laji` varchar(8) NOT NULL DEFAULT '',
  `nimike` varchar(10) NOT NULL DEFAULT '',
  `lisakoodin_tyyppi` char(1) NOT NULL DEFAULT '',
  `lisakoodi` int(3) NOT NULL DEFAULT '0',
  `voim_alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `voim_loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `toimenpide_id` int(8) NOT NULL DEFAULT '0',
  `toimenpidetyyppi` int(3) NOT NULL DEFAULT '0',
  `maa_ryhma` varchar(4) NOT NULL DEFAULT '',
  `maara` decimal(10,3) NOT NULL DEFAULT '0.000',
  `rahayksikko` char(3) NOT NULL DEFAULT '',
  `paljousyksikko` char(3) NOT NULL DEFAULT '',
  `paljousyksikko_muunnin` char(1) NOT NULL DEFAULT '',
  `duty_expr_id` char(2) NOT NULL DEFAULT '',
  `kiintionumero` int(6) NOT NULL DEFAULT '0',
  `alaviitteen_tyyppi` char(2) NOT NULL DEFAULT '',
  `alaviitenumero` int(3) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `fyysinen_avain_index` (`laji`,`toimenpide_id`),
  KEY `nimike_index` (`laji`,`nimike`,`maa_ryhma`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taso`
--

DROP TABLE IF EXISTS `taso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taso` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `summattava_taso` varchar(150) NOT NULL DEFAULT '',
  `taso` varchar(20) NOT NULL DEFAULT '',
  `nimi` varchar(100) NOT NULL DEFAULT '',
  `oletusarvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kerroin` decimal(12,2) NOT NULL DEFAULT '0.00',
  `jakaja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kumulatiivinen` char(1) NOT NULL DEFAULT '',
  `kayttotarkoitus` char(1) NOT NULL DEFAULT '',
  `poisto_vastatili` varchar(6) NOT NULL DEFAULT '',
  `poistoero_tili` varchar(6) NOT NULL DEFAULT '',
  `poistoero_vastatili` varchar(6) NOT NULL DEFAULT '',
  `planned_depreciation_type` char(1) NOT NULL DEFAULT '',
  `planned_depreciation_amount` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `btl_depreciation_type` char(1) NOT NULL DEFAULT '',
  `btl_depreciation_amount` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_tyyppi_taso_index` (`yhtio`,`tyyppi`,`taso`)
) ENGINE=MyISAM AUTO_INCREMENT=3057 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tilausrivi`
--

DROP TABLE IF EXISTS `tilausrivi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilausrivi` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `toimaika` date NOT NULL DEFAULT '0000-00-00',
  `kerayspvm` date NOT NULL DEFAULT '0000-00-00',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `try` int(6) NOT NULL DEFAULT '0',
  `osasto` int(6) unsigned NOT NULL DEFAULT '0',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `kpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kpl2` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tilkpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `yksikko` char(3) NOT NULL DEFAULT '',
  `varattu` decimal(12,2) NOT NULL DEFAULT '0.00',
  `jt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `hinta_valuutassa` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `hinta_alkuperainen` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rivihinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `rivihinta_valuutassa` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `erikoisale` decimal(5,2) NOT NULL DEFAULT '0.00',
  `erikoisale_saapuminen` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale1` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale2` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ale3` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kate` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `kate_korjattu` decimal(16,6) DEFAULT NULL,
  `valmistus_painoarvo` decimal(9,8) unsigned DEFAULT NULL,
  `kommentti` text,
  `ale_peruste` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `laadittu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `keratty` varchar(50) NOT NULL DEFAULT '',
  `kerattyaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `toimitettu` varchar(50) NOT NULL DEFAULT '',
  `toimitettuaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laskutettu` varchar(50) NOT NULL DEFAULT '',
  `laskutettuaika` date NOT NULL DEFAULT '0000-00-00',
  `var` char(1) NOT NULL DEFAULT '',
  `var2` varchar(5) NOT NULL DEFAULT '',
  `netto` char(1) NOT NULL DEFAULT '',
  `perheid` int(11) unsigned NOT NULL DEFAULT '0',
  `perheid2` int(11) NOT NULL DEFAULT '0',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `suuntalava` int(11) NOT NULL DEFAULT '0',
  `campaign_id` int(11) DEFAULT NULL,
  `varastoon` tinyint(1) NOT NULL DEFAULT '1',
  `vahvistettu_maara` decimal(12,2) DEFAULT NULL,
  `vahvistettu_kommentti` text,
  `tilaajanrivinro` int(11) unsigned NOT NULL DEFAULT '0',
  `jaksotettu` int(11) NOT NULL DEFAULT '0',
  `varasto` int(11) DEFAULT NULL,
  `uusiotunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_otunnus` (`yhtio`,`otunnus`),
  KEY `uusiotunnus_index` (`yhtio`,`uusiotunnus`),
  KEY `yhtio_laadittu` (`yhtio`,`laadittu`),
  KEY `yhtio_tyyppi_osasto_try_laskutettuaika` (`yhtio`,`tyyppi`,`osasto`,`try`,`laskutettuaika`),
  KEY `yhtio_tyyppi_osasto_try_laadittu` (`yhtio`,`tyyppi`,`osasto`,`try`,`laadittu`),
  KEY `yhtio_tyyppi_tuoteno_laadittu` (`yhtio`,`tyyppi`,`tuoteno`,`laadittu`),
  KEY `yhtio_tyyppi_tuoteno_varattu` (`yhtio`,`tyyppi`,`tuoteno`,`varattu`),
  KEY `yhtio_tyyppi_laskutettuaika` (`yhtio`,`tyyppi`,`laskutettuaika`),
  KEY `yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus` (`yhtio`,`tyyppi`,`var`,`keratty`,`kerattyaika`,`uusiotunnus`),
  KEY `yhtio_tyyppi_tuoteno_laskutettuaika` (`yhtio`,`tyyppi`,`tuoteno`,`laskutettuaika`),
  KEY `yhtio_tyyppi_kerattyaika` (`yhtio`,`tyyppi`,`kerattyaika`),
  KEY `yhtio_perheid2` (`yhtio`,`perheid2`),
  KEY `suuntalava_index` (`yhtio`,`suuntalava`),
  KEY `yhtio_tyyppi_toimitettuaika` (`yhtio`,`tyyppi`,`toimitettuaika`),
  KEY `yhtio_tyyppi_tuoteno_kerayspvm` (`yhtio`,`tyyppi`,`tuoteno`,`kerayspvm`)
) ENGINE=MyISAM AUTO_INCREMENT=151 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tilausrivin_lisatiedot`
--

DROP TABLE IF EXISTS `tilausrivin_lisatiedot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilausrivin_lisatiedot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tilausrivitunnus` int(11) NOT NULL DEFAULT '0',
  `tiliointirivitunnus` int(11) NOT NULL DEFAULT '0',
  `tilausrivilinkki` int(11) NOT NULL DEFAULT '0',
  `toimittajan_tunnus` int(11) NOT NULL DEFAULT '0',
  `kulun_kohdemaan_alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kulun_kohdemaa` char(2) NOT NULL DEFAULT '',
  `hankintakulut` text,
  `asiakkaan_positio` int(11) NOT NULL DEFAULT '0',
  `positio` varchar(20) NOT NULL DEFAULT '',
  `palautus_varasto` int(11) NOT NULL DEFAULT '0',
  `pituus` varchar(100) NOT NULL DEFAULT '',
  `leveys` varchar(100) NOT NULL DEFAULT '',
  `pidin` varchar(100) NOT NULL DEFAULT '',
  `viiste` varchar(100) NOT NULL DEFAULT '',
  `porauskuvio` varchar(100) NOT NULL DEFAULT '',
  `niitti` varchar(100) NOT NULL DEFAULT '',
  `autoid` int(11) NOT NULL DEFAULT '0',
  `rekisterinumero` varchar(7) NOT NULL DEFAULT '',
  `ei_nayteta` char(1) NOT NULL DEFAULT '',
  `alunperin_puute` int(11) NOT NULL DEFAULT '0',
  `osto_vai_hyvitys` char(1) NOT NULL DEFAULT '',
  `sistyomaarays_sarjatunnus` varchar(255) NOT NULL DEFAULT '',
  `suoraan_laskutukseen` char(1) NOT NULL DEFAULT '',
  `erikoistoimitus_myynti` tinyint(1) NOT NULL DEFAULT '0',
  `vanha_otunnus` int(11) NOT NULL DEFAULT '0',
  `omalle_tilaukselle` char(1) NOT NULL DEFAULT '',
  `ohita_kerays` char(1) NOT NULL DEFAULT '',
  `jt_manual` char(1) NOT NULL DEFAULT '',
  `poikkeava_tulliprosentti` decimal(5,2) DEFAULT NULL,
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `sopimus_alkaa` date NOT NULL DEFAULT '0000-00-00',
  `sopimus_loppuu` date NOT NULL DEFAULT '0000-00-00',
  `sopimuksen_lisatieto1` text,
  `sopimuksen_lisatieto2` text,
  `suoratoimitettuaika` date NOT NULL DEFAULT '0000-00-00',
  `toimitusaika_paivitetty` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kohde_hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `kohde_hyllynro` varchar(5) NOT NULL DEFAULT '',
  `kohde_hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `kohde_hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `korvamerkinta` varchar(100) NOT NULL DEFAULT '',
  `tullinimike` int(8) NOT NULL DEFAULT '0',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `tilausrivitunnus` (`yhtio`,`tilausrivitunnus`),
  KEY `tilausrivilinkki` (`yhtio`,`tilausrivilinkki`),
  KEY `yhtio_vanhaotunnus` (`yhtio`,`vanha_otunnus`),
  KEY `yhtio_asiakkaan_positio` (`yhtio`,`asiakkaan_positio`),
  KEY `kohde_hyllypaikka` (`yhtio`,`kohde_hyllyalue`,`kohde_hyllynro`,`kohde_hyllyvali`,`kohde_hyllytaso`)
) ENGINE=MyISAM AUTO_INCREMENT=151 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tili`
--

DROP TABLE IF EXISTS `tili`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tili` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `sisainen_taso` varchar(20) NOT NULL DEFAULT '',
  `ulkoinen_taso` varchar(20) NOT NULL DEFAULT '',
  `alv_taso` varchar(20) NOT NULL DEFAULT '',
  `evl_taso` varchar(20) NOT NULL DEFAULT '',
  `tulosseuranta_taso` varchar(20) NOT NULL DEFAULT '',
  `nimi` varchar(100) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `toimijaliitos` char(1) NOT NULL DEFAULT '',
  `tiliointi_tarkistus` int(2) NOT NULL DEFAULT '0',
  `manuaali_esto` char(1) NOT NULL DEFAULT '',
  `oletus_alv` decimal(5,2) DEFAULT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tili_index` (`yhtio`,`tilino`),
  FULLTEXT KEY `nimi` (`nimi`)
) ENGINE=MyISAM AUTO_INCREMENT=4907 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tilikaudet`
--

DROP TABLE IF EXISTS `tilikaudet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilikaudet` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tilikausi_alku` date NOT NULL DEFAULT '0000-00-00',
  `tilikausi_loppu` date NOT NULL DEFAULT '0000-00-00',
  `avaava_tase` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiliointi`
--

DROP TABLE IF EXISTS `tiliointi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tiliointi` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `laadittu` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ltunnus` int(11) NOT NULL DEFAULT '0',
  `liitos` char(1) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `tapvm` date NOT NULL DEFAULT '0000-00-00',
  `summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `summa_valuutassa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valkoodi` varchar(3) NOT NULL DEFAULT '',
  `selite` text,
  `vero` decimal(4,2) NOT NULL DEFAULT '0.00',
  `lukko` char(1) NOT NULL DEFAULT '',
  `korjattu` varchar(50) NOT NULL DEFAULT '',
  `korjausaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tosite` int(8) NOT NULL DEFAULT '0',
  `commodity_id` int(11) DEFAULT NULL,
  `aputunnus` int(11) NOT NULL DEFAULT '0',
  `tapahtumatunnus` int(11) NOT NULL DEFAULT '0',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tositerivit_index` (`ltunnus`),
  KEY `aputunnus_index` (`yhtio`,`aputunnus`),
  KEY `tosite_index` (`yhtio`,`tosite`),
  KEY `yhtio_tilino_tapvm` (`yhtio`,`tilino`,`tapvm`),
  KEY `yhtio_tapvm_tilino` (`yhtio`,`tapvm`,`tilino`),
  KEY `yhtio_tapahtumatunnus` (`yhtio`,`tapahtumatunnus`),
  KEY `commodity_id` (`commodity_id`)
) ENGINE=MyISAM AUTO_INCREMENT=142 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiliointisaanto`
--

DROP TABLE IF EXISTS `tiliointisaanto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tiliointisaanto` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `ttunnus` int(11) NOT NULL DEFAULT '0',
  `mintuote` varchar(25) NOT NULL DEFAULT '',
  `maxtuote` varchar(25) NOT NULL DEFAULT '',
  `kuvaus` text,
  `kuvaus2` text,
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `alv` varchar(1) NOT NULL DEFAULT '',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `hyvak1` varchar(50) NOT NULL DEFAULT '',
  `hyvak2` varchar(50) NOT NULL DEFAULT '',
  `hyvak3` varchar(50) NOT NULL DEFAULT '',
  `hyvak4` varchar(50) NOT NULL DEFAULT '',
  `hyvak5` varchar(50) NOT NULL DEFAULT '',
  `vienti` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiliotedata`
--

DROP TABLE IF EXISTS `tiliotedata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tiliotedata` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `aineisto` int(11) NOT NULL DEFAULT '0',
  `tilino` varchar(35) NOT NULL DEFAULT '',
  `alku` date NOT NULL DEFAULT '0000-00-00',
  `loppu` date NOT NULL DEFAULT '0000-00-00',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `kasitelty` date NOT NULL DEFAULT '0000-00-00',
  `kuitattu` varchar(50) NOT NULL DEFAULT '',
  `kuitattuaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tiliointitunnus` bigint(20) unsigned NOT NULL DEFAULT '0',
  `tieto` text,
  `perheid` int(11) NOT NULL DEFAULT '0',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `perheid_index` (`yhtio`,`perheid`),
  KEY `aineisto_index` (`aineisto`),
  KEY `yhtio_tiliointitunnus` (`yhtio`,`tiliointitunnus`),
  KEY `yhtio_tilino_tyyppi_tieto` (`yhtio`,`tilino`,`tyyppi`,`tieto`(150)),
  KEY `yhtio_alku` (`yhtio`,`alku`),
  KEY `yhtio_tilino_alku` (`yhtio`,`tilino`,`alku`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiliotesaanto`
--

DROP TABLE IF EXISTS `tiliotesaanto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tiliotesaanto` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `pankkitili` varchar(35) NOT NULL DEFAULT '',
  `koodi` char(3) NOT NULL DEFAULT '',
  `koodiselite` varchar(35) NOT NULL DEFAULT '',
  `nimitieto` varchar(35) NOT NULL DEFAULT '',
  `selite` varchar(100) NOT NULL DEFAULT '',
  `erittely` char(1) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `tilino2` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kustp2` int(11) NOT NULL DEFAULT '0',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimi`
--

DROP TABLE IF EXISTS `toimi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimi` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `nimitark` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(45) NOT NULL DEFAULT '',
  `osoitetark` varchar(45) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(45) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `puhelin` varchar(130) NOT NULL DEFAULT '',
  `fax` varchar(30) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `toimittajaryhma` varchar(150) NOT NULL DEFAULT '',
  `kuljetus` varchar(150) NOT NULL DEFAULT '',
  `toimitusehto` varchar(35) NOT NULL DEFAULT '',
  `huolitsija` varchar(35) NOT NULL DEFAULT '',
  `maksuteksti` varchar(35) NOT NULL DEFAULT '',
  `jakelu` varchar(35) NOT NULL DEFAULT '',
  `yhteyshenkilo` varchar(35) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `fakta` text,
  `vapaa_teksti` text NOT NULL,
  `pankki_haltija` varchar(60) NOT NULL DEFAULT '',
  `tilinumero` varchar(14) NOT NULL DEFAULT '',
  `pankki1` varchar(35) NOT NULL DEFAULT '',
  `pankki2` varchar(35) NOT NULL DEFAULT '',
  `pankki3` varchar(35) NOT NULL DEFAULT '',
  `pankki4` varchar(35) NOT NULL DEFAULT '',
  `ohjeitapankille` text,
  `ultilno_maa` char(2) NOT NULL DEFAULT '',
  `ultilno` varchar(35) NOT NULL DEFAULT '',
  `swift` varchar(11) NOT NULL DEFAULT '',
  `clearing` varchar(35) NOT NULL DEFAULT '',
  `oletus_valkoodi` char(3) NOT NULL DEFAULT '',
  `oletus_erapvm` decimal(3,0) NOT NULL DEFAULT '0',
  `oletus_toimaika` int(3) NOT NULL DEFAULT '0',
  `oletus_tilausvali` int(3) NOT NULL DEFAULT '0',
  `oletus_suoraveloitus` char(1) NOT NULL DEFAULT '',
  `oletus_suoravel_pankki` int(11) NOT NULL DEFAULT '0',
  `oletus_kapvm` decimal(3,0) NOT NULL DEFAULT '0',
  `oletus_kapro` decimal(5,3) NOT NULL DEFAULT '0.000',
  `oletus_hyvak1` varchar(50) NOT NULL DEFAULT '',
  `oletus_hyvak2` varchar(50) NOT NULL DEFAULT '',
  `oletus_hyvak3` varchar(50) NOT NULL DEFAULT '',
  `oletus_hyvak4` varchar(50) NOT NULL DEFAULT '',
  `oletus_hyvak5` varchar(50) NOT NULL DEFAULT '',
  `oletus_hyvaksynnanmuutos` char(1) NOT NULL DEFAULT '',
  `oletus_vienti` char(1) NOT NULL DEFAULT '',
  `maa_lahetys` char(2) NOT NULL DEFAULT '',
  `kauppatapahtuman_luonne` int(2) NOT NULL DEFAULT '0',
  `kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `oletus_kulupros` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kulujen_laskeminen_hintoihin` char(1) NOT NULL DEFAULT '',
  `edi_palvelin` varchar(50) NOT NULL DEFAULT '',
  `edi_kayttaja` varchar(50) NOT NULL DEFAULT '',
  `edi_salasana` varchar(50) NOT NULL DEFAULT '',
  `edi_kuvaus` varchar(50) NOT NULL DEFAULT '',
  `edi_polku` varchar(50) NOT NULL DEFAULT '',
  `asn_sanomat` char(1) NOT NULL DEFAULT '',
  `konserniyhtio` char(1) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `kustannuspaikka` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `tilino_alv` varchar(6) NOT NULL DEFAULT '',
  `verovelvollinen` char(2) NOT NULL DEFAULT '',
  `selaus` varchar(8) NOT NULL DEFAULT '',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `toimittajanro` varchar(20) NOT NULL DEFAULT '',
  `maksukielto` char(1) NOT NULL DEFAULT '',
  `laskun_erittely` char(1) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `tyyppi_tieto` varchar(100) NOT NULL DEFAULT '',
  `pikaperustus` char(1) NOT NULL DEFAULT '',
  `hintojenpaivityssykli` int(2) NOT NULL DEFAULT '0',
  `suoratoimitus` char(1) NOT NULL DEFAULT '',
  `tehdas_saldo_tarkistus` char(1) NOT NULL DEFAULT '',
  `sahkoinen_automaattituloutus` char(1) NOT NULL DEFAULT '',
  `ostotilauksen_kasittely` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `ytunnus_index` (`yhtio`,`ytunnus`),
  KEY `toimittajanro_index` (`yhtio`,`toimittajanro`)
) ENGINE=MyISAM AUTO_INCREMENT=111 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimittajaalennus`
--

DROP TABLE IF EXISTS `toimittajaalennus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimittajaalennus` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `ryhma` varchar(15) NOT NULL DEFAULT '',
  `toimittaja` int(11) NOT NULL DEFAULT '0',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `alennus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `alennuslaji` int(1) NOT NULL DEFAULT '1',
  `minkpl` int(11) NOT NULL DEFAULT '0',
  `monikerta` char(1) NOT NULL DEFAULT '',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_ytunnus_ryhma` (`yhtio`,`ytunnus`,`ryhma`),
  KEY `yhtio_ytunnus_tuoteno` (`yhtio`,`ytunnus`,`tuoteno`),
  KEY `yhtio_toimittaja_ryhma` (`yhtio`,`toimittaja`,`ryhma`),
  KEY `yhtio_toimittaja_tuoteno` (`yhtio`,`toimittaja`,`tuoteno`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimittajahinta`
--

DROP TABLE IF EXISTS `toimittajahinta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimittajahinta` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `ryhma` varchar(15) NOT NULL DEFAULT '',
  `toimittaja` int(11) NOT NULL DEFAULT '0',
  `ytunnus` varchar(15) NOT NULL DEFAULT '',
  `hinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `minkpl` int(11) NOT NULL DEFAULT '0',
  `maxkpl` int(11) NOT NULL DEFAULT '0',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `loppupvm` date NOT NULL DEFAULT '0000-00-00',
  `laji` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_ytunnus_ryhma` (`yhtio`,`ytunnus`,`ryhma`),
  KEY `yhtio_ytunnus_tuoteno` (`yhtio`,`ytunnus`,`tuoteno`),
  KEY `yhtio_toimittaja_ryhma` (`yhtio`,`toimittaja`,`ryhma`),
  KEY `yhtio_toimittaja_tuoteno` (`yhtio`,`toimittaja`,`tuoteno`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimitustapa`
--

DROP TABLE IF EXISTS `toimitustapa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimitustapa` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `selite` varchar(50) NOT NULL DEFAULT '',
  `lahdon_selite` varchar(150) NOT NULL DEFAULT '',
  `virallinen_selite` varchar(150) NOT NULL DEFAULT '',
  `tulostustapa` char(1) NOT NULL DEFAULT '',
  `rahtikirja` varchar(50) NOT NULL DEFAULT '',
  `logy_rahtikirjanumerot` char(1) NOT NULL DEFAULT '',
  `osoitelappu` varchar(50) NOT NULL DEFAULT '',
  `rahdinkuljettaja` varchar(40) NOT NULL DEFAULT '',
  `smarten_partycode` varchar(100) NOT NULL DEFAULT '',
  `smarten_serviceid` varchar(100) NOT NULL DEFAULT '',
  `rahti_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `sopimusnro` varchar(50) NOT NULL DEFAULT '',
  `rahtikirjakopio_email` varchar(150) NOT NULL DEFAULT '',
  `jvkulu` decimal(5,2) NOT NULL DEFAULT '0.00',
  `jvkielto` char(1) NOT NULL DEFAULT '',
  `vak_kielto` varchar(50) NOT NULL DEFAULT '',
  `vaihtoehtoinen_vak_toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `erikoispakkaus_kielto` char(1) NOT NULL DEFAULT '',
  `nouto` char(1) NOT NULL DEFAULT '',
  `lauantai` char(1) NOT NULL DEFAULT '',
  `erilliskasiteltavakulu` decimal(5,2) NOT NULL DEFAULT '0.00',
  `merahti` char(1) NOT NULL DEFAULT '',
  `kuljetusvakuutus_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `kuljetusvakuutus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kuljetusvakuutus_tyyppi` char(1) NOT NULL DEFAULT '',
  `extranet` char(1) NOT NULL DEFAULT '',
  `ei_pakkaamoa` char(1) NOT NULL DEFAULT '',
  `erittely` char(1) NOT NULL DEFAULT '',
  `uudet_pakkaustiedot` char(1) NOT NULL DEFAULT '',
  `lajittelupiste` varchar(150) NOT NULL DEFAULT '',
  `kuluprosentti` decimal(8,3) NOT NULL DEFAULT '0.000',
  `toim_ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `toim_nimi` varchar(60) NOT NULL DEFAULT '',
  `toim_nimitark` varchar(60) NOT NULL DEFAULT '',
  `toim_osoite` varchar(55) NOT NULL DEFAULT '',
  `toim_postino` varchar(15) NOT NULL DEFAULT '',
  `toim_postitp` varchar(35) NOT NULL DEFAULT '',
  `toim_maa` varchar(35) NOT NULL DEFAULT '',
  `maa_maara` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `sisamaan_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `kontti` int(1) NOT NULL DEFAULT '0',
  `aktiivinen_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `aktiivinen_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `kauppatapahtuman_luonne` int(2) NOT NULL DEFAULT '0',
  `kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `poistumistoimipaikka_koodi` varchar(8) NOT NULL DEFAULT '',
  `ulkomaanlisa` decimal(6,2) NOT NULL DEFAULT '0.00',
  `sallitut_maat` varchar(50) NOT NULL DEFAULT '',
  `lisakulu` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lisakulu_summa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `jarjestys` int(5) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_selite` (`yhtio`,`selite`),
  KEY `selite_index` (`yhtio`,`selite`)
) ENGINE=MyISAM AUTO_INCREMENT=111 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimitustavan_avainsanat`
--

DROP TABLE IF EXISTS `toimitustavan_avainsanat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimitustavan_avainsanat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `kieli` varchar(2) NOT NULL DEFAULT '0',
  `laji` varchar(150) NOT NULL DEFAULT '',
  `selite` tinytext,
  `selitetark` tinytext,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_liitostunnus_kieli_laji` (`yhtio`,`liitostunnus`,`kieli`,`laji`),
  KEY `yhtio__kieli_laji_liitostunnus` (`yhtio`,`kieli`,`laji`,`liitostunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimitustavan_lahdot`
--

DROP TABLE IF EXISTS `toimitustavan_lahdot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimitustavan_lahdot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `lahdon_viikonpvm` int(1) NOT NULL DEFAULT '0',
  `lahdon_kellonaika` time NOT NULL DEFAULT '00:00:00',
  `viimeinen_tilausaika` time NOT NULL DEFAULT '00:00:00',
  `kerailyn_aloitusaika` time NOT NULL DEFAULT '00:00:00',
  `terminaalialue` varchar(150) NOT NULL DEFAULT '',
  `asiakasluokka` varchar(50) NOT NULL DEFAULT '',
  `varasto` int(11) NOT NULL DEFAULT '0',
  `aktiivi` char(1) NOT NULL DEFAULT '',
  `ohjausmerkki` varchar(70) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `alkupvm` date NOT NULL DEFAULT '0000-00-00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `toimitustavat_toimipaikat`
--

DROP TABLE IF EXISTS `toimitustavat_toimipaikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toimitustavat_toimipaikat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toimitustapa_tunnus` int(11) NOT NULL,
  `toimipaikka_tunnus` int(11) NOT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transports`
--

DROP TABLE IF EXISTS `transports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transportable_id` int(11) DEFAULT NULL,
  `transportable_type` varchar(255) DEFAULT NULL,
  `transport_name` varchar(60) NOT NULL DEFAULT '',
  `hostname` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `encoding` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_transports_on_transportable_id` (`transportable_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tullinimike`
--

DROP TABLE IF EXISTS `tullinimike`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tullinimike` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `cnkey` varchar(20) NOT NULL DEFAULT '',
  `cn` varchar(8) NOT NULL DEFAULT '',
  `dashes` varchar(10) NOT NULL DEFAULT '',
  `dm` text,
  `su` varchar(20) NOT NULL DEFAULT '',
  `su_vientiilmo` char(3) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `tullinimike_cn` (`cn`)
) ENGINE=MyISAM AUTO_INCREMENT=536433 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuote`
--

DROP TABLE IF EXISTS `tuote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuote` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `osasto` int(6) unsigned NOT NULL DEFAULT '0',
  `try` int(6) NOT NULL DEFAULT '0',
  `tuotemerkki` varchar(30) NOT NULL DEFAULT '',
  `malli` varchar(100) NOT NULL DEFAULT '',
  `mallitarkenne` varchar(100) NOT NULL DEFAULT '',
  `kuvaus` text,
  `lyhytkuvaus` text,
  `mainosteksti` text,
  `aleryhma` varchar(15) NOT NULL DEFAULT '',
  `muuta` varchar(250) NOT NULL DEFAULT '',
  `tilausrivi_kommentti` text,
  `kerayskommentti` text,
  `purkukommentti` text,
  `ostokommentti` text,
  `myyntihinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `myyntihinta_maara` int(11) NOT NULL DEFAULT '0',
  `kehahin` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `vihahin` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `vihapvm` date NOT NULL DEFAULT '0000-00-00',
  `myyntikate` int(11) NOT NULL DEFAULT '0',
  `myymalakate` int(11) NOT NULL DEFAULT '0',
  `nettokate` int(11) NOT NULL DEFAULT '0',
  `yksikko` varchar(10) NOT NULL DEFAULT '',
  `ei_saldoa` char(1) NOT NULL DEFAULT '',
  `kommentoitava` char(1) NOT NULL DEFAULT '',
  `tuotetyyppi` char(1) NOT NULL DEFAULT '',
  `myynninseuranta` char(1) NOT NULL DEFAULT '',
  `hinnastoon` char(1) NOT NULL DEFAULT '',
  `sarjanumeroseuranta` char(1) NOT NULL DEFAULT '',
  `automaattinen_sarjanumerointi` tinyint(4) NOT NULL DEFAULT '0',
  `pullopanttitarratulostus_kerayksessa` varchar(12) NOT NULL DEFAULT '',
  `suoratoimitus` char(1) NOT NULL DEFAULT '',
  `status` varchar(10) NOT NULL DEFAULT '',
  `yksin_kerailyalustalle` char(1) NOT NULL DEFAULT '',
  `keraysvyohyke` int(11) NOT NULL DEFAULT '0',
  `panttitili` char(1) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `tilino_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_ei_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_kaanteinen` varchar(6) NOT NULL DEFAULT '',
  `tilino_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_osto_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_triang` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `eankoodi` varchar(20) NOT NULL DEFAULT '',
  `epakurantti25pvm` date NOT NULL DEFAULT '0000-00-00',
  `epakurantti50pvm` date NOT NULL DEFAULT '0000-00-00',
  `epakurantti75pvm` date NOT NULL DEFAULT '0000-00-00',
  `epakurantti100pvm` date NOT NULL DEFAULT '0000-00-00',
  `myymalahinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `nettohinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `halytysraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `varmuus_varasto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tilausmaara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ostoehdotus` char(1) NOT NULL DEFAULT '',
  `tahtituote` varchar(15) NOT NULL DEFAULT '',
  `tarrakerroin` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tarrakpl` decimal(4,0) NOT NULL DEFAULT '0',
  `myynti_era` decimal(12,2) NOT NULL DEFAULT '0.00',
  `minimi_era` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valmistuslinja` varchar(150) NOT NULL DEFAULT '',
  `valmistusaika_sekunneissa` int(11) NOT NULL DEFAULT '0',
  `tullikohtelu` varchar(4) NOT NULL DEFAULT '',
  `tullinimike1` varchar(8) NOT NULL DEFAULT '',
  `tullinimike2` varchar(4) NOT NULL DEFAULT '',
  `toinenpaljous_muunnoskerroin` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vienti` text NOT NULL,
  `tuotekorkeus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `tuoteleveys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `tuotesyvyys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `tuotemassa` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `tuotekuva` varchar(50) NOT NULL DEFAULT '',
  `nakyvyys` varchar(100) NOT NULL DEFAULT '',
  `kuluprosentti` decimal(8,3) NOT NULL DEFAULT '0.000',
  `vakkoodi` varchar(100) NOT NULL DEFAULT '',
  `vakmaara` varchar(50) NOT NULL DEFAULT '',
  `leimahduspiste` varchar(50) NOT NULL DEFAULT '',
  `meria_saastuttava` varchar(50) NOT NULL DEFAULT '',
  `vak_imdg_koodi` int(11) NOT NULL DEFAULT '0',
  `kuljetusohje` text,
  `pakkausmateriaali` varchar(50) NOT NULL DEFAULT '',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `myyjanro` int(11) NOT NULL DEFAULT '0',
  `ostajanro` int(11) NOT NULL DEFAULT '0',
  `tuotepaallikko` int(11) NOT NULL DEFAULT '0',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `tuoteno_index` (`yhtio`,`tuoteno`),
  KEY `toituono_index` (`yhtio`),
  KEY `osasto_try_index` (`yhtio`,`osasto`,`try`),
  KEY `yhtio_try_index` (`yhtio`,`try`),
  KEY `yhtio_eankoodi_index` (`yhtio`,`eankoodi`),
  KEY `yhtio_myyjanro` (`yhtio`,`myyjanro`),
  KEY `yhtio_tullinimike` (`yhtio`,`tullinimike1`,`tullinimike2`),
  KEY `yhtio_tuotemerkki` (`yhtio`,`tuotemerkki`),
  KEY `yhtio_aleryhma` (`yhtio`,`aleryhma`),
  KEY `yhtio_status_osasto_try` (`yhtio`,`status`,`osasto`,`try`),
  KEY `yhtio_tuotetyyppi_status` (`yhtio`,`tuotetyyppi`,`status`),
  FULLTEXT KEY `tuoteno` (`tuoteno`),
  FULLTEXT KEY `nimitys` (`nimitys`)
) ENGINE=MyISAM AUTO_INCREMENT=711251 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuote_muutokset`
--

DROP TABLE IF EXISTS `tuote_muutokset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuote_muutokset` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `alkup_tuoteno` varchar(60) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kuka` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=14168 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotepaikat`
--

DROP TABLE IF EXISTS `tuotepaikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotepaikat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `hyllypaikka` varchar(20) NOT NULL DEFAULT '',
  `saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_varattu` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldoaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `myytavissa_static` decimal(12,2) NOT NULL DEFAULT '0.00',
  `inventointiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `inventointipoikkeama` decimal(5,2) NOT NULL DEFAULT '0.00',
  `halytysraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tilausmaara` decimal(12,2) NOT NULL DEFAULT '0.00',
  `oletus` char(1) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `prio` int(11) NOT NULL DEFAULT '0',
  `poistettava` char(1) NOT NULL DEFAULT '',
  `varasto` int(11) DEFAULT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_tuoteno_paikka` (`yhtio`,`tuoteno`,`hyllyalue`,`hyllynro`,`hyllyvali`,`hyllytaso`),
  KEY `tuote_index` (`yhtio`,`tuoteno`),
  KEY `saldo_index` (`yhtio`,`saldoaika`,`saldo`),
  KEY `yhtio_hyllypaikka` (`yhtio`,`hyllypaikka`)
) ENGINE=MyISAM AUTO_INCREMENT=2541135 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuoteperhe`
--

DROP TABLE IF EXISTS `tuoteperhe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuoteperhe` (
  `tunnus` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isatuoteno` varchar(60) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `kerroin` decimal(15,9) NOT NULL DEFAULT '1.000000000',
  `hintakerroin` decimal(15,9) NOT NULL DEFAULT '1.000000000',
  `alekerroin` decimal(15,9) NOT NULL DEFAULT '1.000000000',
  `fakta` text,
  `fakta2` text,
  `piirustusnumero` varchar(60) DEFAULT '',
  `osanumero` varchar(60) DEFAULT '',
  `positiokentta` varchar(60) DEFAULT '',
  `omasivu` char(1) NOT NULL DEFAULT '',
  `ei_nayteta` char(1) NOT NULL DEFAULT '',
  `hintatyyppi` varchar(1) NOT NULL DEFAULT '',
  `ohita_kerays` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tyyppi_isatuoteno` (`yhtio`,`tyyppi`,`isatuoteno`),
  KEY `yhtio_tyyppi_tuoteno` (`yhtio`,`tyyppi`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=82243 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotteen_alv`
--

DROP TABLE IF EXISTS `tuotteen_alv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotteen_alv` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `alv` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `tilino_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_ei_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_kaanteinen` varchar(6) NOT NULL DEFAULT '',
  `tilino_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_osto_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_triang` varchar(6) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_maa_tuoteno` (`yhtio`,`maa`,`tuoteno`)
) ENGINE=MyISAM AUTO_INCREMENT=234703 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotteen_avainsanat`
--

DROP TABLE IF EXISTS `tuotteen_avainsanat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotteen_avainsanat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `laji` varchar(150) NOT NULL DEFAULT '',
  `selite` text,
  `selitetark` text,
  `status` char(1) NOT NULL DEFAULT '',
  `nakyvyys` char(1) NOT NULL DEFAULT '',
  `jarjestys` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_kieli_laji_tuoteno` (`yhtio`,`kieli`,`laji`,`tuoteno`),
  KEY `yhtio_kieli_tuoteno` (`yhtio`,`kieli`,`tuoteno`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_kieli_laji_selite` (`yhtio`,`kieli`,`laji`,`selite`(150))
) ENGINE=MyISAM AUTO_INCREMENT=637262 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotteen_toimittajat`
--

DROP TABLE IF EXISTS `tuotteen_toimittajat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotteen_toimittajat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `osto_era` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pakkauskoko` decimal(12,2) NOT NULL DEFAULT '0.00',
  `toimitusaika` int(3) NOT NULL DEFAULT '0',
  `tilausvali` int(3) NOT NULL DEFAULT '0',
  `tehdas_saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tehdas_saldo_toimaika` int(3) NOT NULL DEFAULT '0',
  `tehdas_saldo_paivitetty` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ostohinta` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `alennus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `valuutta` char(3) NOT NULL DEFAULT '',
  `osto_alv` decimal(5,2) NOT NULL DEFAULT '-1.00',
  `toim_tuoteno` varchar(30) NOT NULL DEFAULT '',
  `toim_nimitys` varchar(100) NOT NULL DEFAULT '',
  `toim_yksikko` varchar(10) NOT NULL DEFAULT '',
  `tuotekerroin` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `viivakoodi` varchar(150) NOT NULL DEFAULT '',
  `alkuperamaa` char(2) NOT NULL DEFAULT '',
  `jarjestys` int(10) unsigned NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_tuoteno_liitostunnus` (`yhtio`,`tuoteno`,`liitostunnus`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`),
  KEY `yhtio_toimtuoteno` (`yhtio`,`toim_tuoteno`),
  KEY `yhtio_viivakoodi` (`yhtio`,`viivakoodi`)
) ENGINE=MyISAM AUTO_INCREMENT=633182 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotteen_toimittajat_pakkauskoot`
--

DROP TABLE IF EXISTS `tuotteen_toimittajat_pakkauskoot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotteen_toimittajat_pakkauskoot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toim_tuoteno_tunnus` int(11) NOT NULL DEFAULT '0',
  `pakkauskoko` varchar(30) NOT NULL DEFAULT '',
  `yksikko` varchar(30) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime DEFAULT NULL,
  `muutospvm` datetime DEFAULT NULL,
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_toimtuotenotunnus_pakkauskoko` (`yhtio`,`toim_tuoteno_tunnus`,`pakkauskoko`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tuotteen_toimittajat_tuotenumerot`
--

DROP TABLE IF EXISTS `tuotteen_toimittajat_tuotenumerot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tuotteen_toimittajat_tuotenumerot` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toim_tuoteno_tunnus` int(11) NOT NULL DEFAULT '0',
  `tuoteno` varchar(30) NOT NULL DEFAULT '',
  `viivakoodi` varchar(150) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `tuotteen_toimittajat_tuotenumerot` (`yhtio`,`toim_tuoteno_tunnus`,`tuoteno`,`viivakoodi`),
  KEY `tuotteen_toimittajat_tuoteno` (`yhtio`,`tuoteno`),
  KEY `tuotteen_toimittajat_viivakoodi` (`yhtio`,`viivakoodi`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tyomaarayksen_tapahtumat`
--

DROP TABLE IF EXISTS `tyomaarayksen_tapahtumat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tyomaarayksen_tapahtumat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyomaarays_tunnus` int(11) NOT NULL DEFAULT '0',
  `tyojono_selite` varchar(60) NOT NULL DEFAULT '',
  `tyostatus_selite` varchar(60) NOT NULL DEFAULT '',
  `vastuuhenkilo` varchar(60) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `kommentti` varchar(60) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tyomaarays`
--

DROP TABLE IF EXISTS `tyomaarays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tyomaarays` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kotipuh` varchar(55) NOT NULL DEFAULT '',
  `tyopuh` varchar(55) NOT NULL DEFAULT '',
  `myyjaliike` varchar(55) NOT NULL DEFAULT '',
  `ostopvm` date NOT NULL DEFAULT '0000-00-00',
  `rekno` varchar(55) NOT NULL DEFAULT '',
  `mittarilukema` varchar(55) NOT NULL DEFAULT '',
  `merkki` varchar(55) NOT NULL DEFAULT '',
  `mallivari` varchar(55) NOT NULL DEFAULT '',
  `valmnro` text,
  `koodi` varchar(60) NOT NULL DEFAULT '',
  `tullikoodi` varchar(8) NOT NULL DEFAULT '',
  `tulliarvo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `maa_lahetys` varchar(2) NOT NULL DEFAULT '',
  `maa_maara` varchar(2) NOT NULL DEFAULT '',
  `maa_alkupera` varchar(2) NOT NULL DEFAULT '',
  `kuljetusmuoto` tinyint(4) NOT NULL DEFAULT '0',
  `kauppatapahtuman_luonne` smallint(6) NOT NULL DEFAULT '0',
  `bruttopaino` decimal(8,2) NOT NULL DEFAULT '0.00',
  `sla` int(4) NOT NULL DEFAULT '0',
  `valmistajan_sopimusnumero` varchar(60) NOT NULL DEFAULT '',
  `tuotu` date NOT NULL DEFAULT '0000-00-00',
  `luvattu` date NOT NULL DEFAULT '0000-00-00',
  `viite` varchar(30) NOT NULL DEFAULT '',
  `komm1` text,
  `komm2` text,
  `viite2` varchar(15) NOT NULL DEFAULT '',
  `tilno` int(6) NOT NULL DEFAULT '0',
  `suorittaja` varchar(50) NOT NULL DEFAULT '',
  `vastuuhenkilo` varchar(50) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vikakoodi` varchar(50) NOT NULL DEFAULT '',
  `tyoaika` varchar(50) NOT NULL DEFAULT '',
  `tyokoodi` varchar(50) NOT NULL DEFAULT '',
  `tehdas` varchar(80) NOT NULL DEFAULT '',
  `takuunumero` int(11) NOT NULL DEFAULT '0',
  `jalleenmyyja` varchar(80) NOT NULL DEFAULT '',
  `tyojono` varchar(100) NOT NULL DEFAULT '',
  `tyostatus` varchar(100) NOT NULL DEFAULT '',
  `prioriteetti` varchar(150) NOT NULL DEFAULT '',
  `hyvaksy` varchar(55) NOT NULL DEFAULT '',
  `otunnus` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`otunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uutinen_asiakassegmentti`
--

DROP TABLE IF EXISTS `uutinen_asiakassegmentti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uutinen_asiakassegmentti` (
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `uutistunnus` int(11) NOT NULL DEFAULT '0',
  `segmenttitunnus` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vaihtoehtoiset_verkkolaskutunnukset`
--

DROP TABLE IF EXISTS `vaihtoehtoiset_verkkolaskutunnukset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaihtoehtoiset_verkkolaskutunnukset` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toimi_tunnus` int(11) NOT NULL DEFAULT '0',
  `kohde_sarake` varchar(50) NOT NULL DEFAULT '',
  `arvo` varchar(25) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vak`
--

DROP TABLE IF EXISTS `vak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vak` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `yk_nro` varchar(5) NOT NULL DEFAULT '',
  `nimi_ja_kuvaus` text,
  `name_and_description` text,
  `luokka` varchar(5) NOT NULL DEFAULT '',
  `luokituskoodi` varchar(15) NOT NULL DEFAULT '',
  `pakkausryhma` varchar(150) NOT NULL DEFAULT '',
  `lipukkeet` text,
  `erityismaaraykset` text,
  `rajoitetut_maarat_ja_poikkeusmaarat_1` varchar(25) NOT NULL DEFAULT '',
  `rajoitetut_maarat_ja_poikkeusmaarat_2` varchar(25) NOT NULL DEFAULT '',
  `pakkaukset_pakkaustavat` text,
  `pakkaukset_erityispakkaus_maara` text,
  `pakkaukset_yhteenpakkaamismaara` text,
  `un_sailiot_ja_irtotavarakontit_soveltamisehdot` text,
  `un_sailiot_ja_irtotavarakontit_erityismaaraykset` text,
  `vak_adr_sailiot_sailiokoodit` text,
  `vak_adr_sailiot_erityismaaraykset` text,
  `ajoneuvo_sailiokuljetuksissa` varchar(15) NOT NULL DEFAULT '',
  `kuljetus_kategoria` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kollit` text,
  `kuljetukseen_liittyvat_erityismaaraykset_irtotavara` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kuorm_purk_ja_kasittely` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kuljetustapahtuma` varchar(55) NOT NULL DEFAULT '',
  `vaaran_tunnusnro` varchar(5) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=2873 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vak_imdg`
--

DROP TABLE IF EXISTS `vak_imdg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vak_imdg` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `yk_nro` varchar(5) NOT NULL DEFAULT '',
  `nimi_ja_kuvaus` text,
  `name_and_description` text,
  `luokka` varchar(5) NOT NULL DEFAULT '',
  `luokituskoodi` varchar(15) NOT NULL DEFAULT '',
  `pakkausryhma` varchar(150) NOT NULL DEFAULT '',
  `lipukkeet` text,
  `erityismaaraykset` text,
  `rajoitetut_maarat_ja_poikkeusmaarat_1` varchar(25) NOT NULL DEFAULT '',
  `rajoitetut_maarat_ja_poikkeusmaarat_2` varchar(25) NOT NULL DEFAULT '',
  `pakkaukset_pakkaustavat` text,
  `pakkaukset_erityispakkaus_maara` text,
  `pakkaukset_yhteenpakkaamismaara` text,
  `un_sailiot_ja_irtotavarakontit_soveltamisehdot` text,
  `un_sailiot_ja_irtotavarakontit_erityismaaraykset` text,
  `vak_imdg_sailiot_sailiokoodit` text,
  `vak_imdg_sailiot_erityismaaraykset` text,
  `ajoneuvo_sailiokuljetuksissa` varchar(15) NOT NULL DEFAULT '',
  `kuljetus_kategoria` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kollit` text,
  `kuljetukseen_liittyvat_erityismaaraykset_irtotavara` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kuorm_purk_ja_kasittely` text,
  `kuljetukseen_liittyvat_erityismaaraykset_kuljetustapahtuma` varchar(55) NOT NULL DEFAULT '',
  `vaaran_tunnusnro` varchar(5) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuu`
--

DROP TABLE IF EXISTS `valuu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuu` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `nimi` char(3) NOT NULL DEFAULT '',
  `jarjestys` decimal(2,0) NOT NULL DEFAULT '0',
  `kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `intrastat_kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `hinnastokurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_nimi` (`yhtio`,`nimi`)
) ENGINE=MyISAM AUTO_INCREMENT=109 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuu_historia`
--

DROP TABLE IF EXISTS `valuu_historia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuu_historia` (
  `kotivaluutta` char(3) NOT NULL DEFAULT '',
  `valuutta` char(3) NOT NULL DEFAULT '',
  `kurssipvm` date NOT NULL DEFAULT '0000-00-00',
  `kurssi` decimal(15,9) NOT NULL DEFAULT '0.000000000',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `kotivaluutta_valkoodi_kurssipvm` (`kotivaluutta`,`valuutta`,`kurssipvm`)
) ENGINE=MyISAM AUTO_INCREMENT=98690 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `varaston_hyllypaikat`
--

DROP TABLE IF EXISTS `varaston_hyllypaikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `varaston_hyllypaikat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `hyllyalue` varchar(5) NOT NULL DEFAULT '',
  `hyllynro` varchar(5) NOT NULL DEFAULT '',
  `hyllyvali` varchar(5) NOT NULL DEFAULT '',
  `hyllytaso` varchar(5) NOT NULL DEFAULT '',
  `varasto` int(11) NOT NULL DEFAULT '0',
  `reservipaikka` char(1) NOT NULL DEFAULT 'E',
  `indeksi` int(11) NOT NULL DEFAULT '0',
  `korkeus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `leveys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `syvyys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `minimi_korkeus` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `minimi_leveys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `minimi_syvyys` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `maksimitaakka` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `varmistuskoodi` varchar(150) NOT NULL DEFAULT '',
  `kiertovaatimus` int(11) NOT NULL DEFAULT '0',
  `keraysvyohyke` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_paikka` (`yhtio`,`hyllyalue`,`hyllynro`,`hyllyvali`,`hyllytaso`),
  KEY `yhtio_varasto_mitat` (`yhtio`,`varasto`,`korkeus`,`leveys`,`syvyys`,`maksimitaakka`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `varaston_tulostimet`
--

DROP TABLE IF EXISTS `varaston_tulostimet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `varaston_tulostimet` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `varasto` bigint(20) unsigned NOT NULL DEFAULT '0',
  `nimi` varchar(15) NOT NULL DEFAULT '',
  `pakkaamo` varchar(150) NOT NULL DEFAULT '',
  `prioriteetti` int(3) NOT NULL DEFAULT '0',
  `alkuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `printteri0` varchar(20) NOT NULL DEFAULT '',
  `printteri1` varchar(20) NOT NULL DEFAULT '',
  `printteri2` varchar(20) NOT NULL DEFAULT '',
  `printteri3` varchar(20) NOT NULL DEFAULT '',
  `printteri4` varchar(20) NOT NULL DEFAULT '',
  `printteri6` varchar(20) NOT NULL DEFAULT '',
  `printteri7` varchar(20) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `varastopaikat`
--

DROP TABLE IF EXISTS `varastopaikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `varastopaikat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `alkuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllyalue` varchar(5) NOT NULL DEFAULT '',
  `loppuhyllynro` varchar(5) NOT NULL DEFAULT '',
  `printteri0` varchar(20) NOT NULL DEFAULT '',
  `printteri1` varchar(20) NOT NULL DEFAULT '',
  `printteri2` varchar(20) NOT NULL DEFAULT '',
  `printteri3` varchar(20) NOT NULL DEFAULT '',
  `printteri4` varchar(20) NOT NULL DEFAULT '',
  `printteri5` varchar(20) NOT NULL DEFAULT '',
  `printteri6` varchar(20) NOT NULL DEFAULT '',
  `printteri7` varchar(20) NOT NULL DEFAULT '',
  `printteri9` varchar(20) NOT NULL DEFAULT '',
  `printteri10` varchar(20) NOT NULL DEFAULT '',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `nouto` int(1) NOT NULL DEFAULT '0',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `nimitark` varchar(55) NOT NULL DEFAULT '',
  `osoite` varchar(55) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(35) NOT NULL DEFAULT '',
  `maa` varchar(35) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `puhelin` varchar(100) NOT NULL DEFAULT '',
  `maa_maara` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `sisamaan_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `sisamaan_kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `kontti` int(1) NOT NULL DEFAULT '0',
  `aktiivinen_kuljetus` varchar(30) NOT NULL DEFAULT '',
  `aktiivinen_kuljetus_kansallisuus` char(2) NOT NULL DEFAULT '',
  `erikoistoimitus_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `erikoistoimitus_toimitustapa` varchar(50) NOT NULL DEFAULT '',
  `kauppatapahtuman_luonne` int(2) NOT NULL DEFAULT '0',
  `kuljetusmuoto` int(1) NOT NULL DEFAULT '0',
  `poistumistoimipaikka_koodi` varchar(8) NOT NULL DEFAULT '',
  `ulkoinen_jarjestelma` char(1) NOT NULL DEFAULT '',
  `pikahakuarvo` int(11) DEFAULT '0',
  `sallitut_maat` varchar(50) NOT NULL DEFAULT '',
  `isa_varasto` int(11) NOT NULL DEFAULT '0',
  `prioriteetti` int(11) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_maa` (`yhtio`,`maa`),
  KEY `yhtio_alku` (`yhtio`,`alkuhyllyalue`,`alkuhyllynro`),
  KEY `yhtio_loppu` (`yhtio`,`loppuhyllyalue`,`loppuhyllynro`)
) ENGINE=MyISAM AUTO_INCREMENT=141 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vastaavat`
--

DROP TABLE IF EXISTS `vastaavat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vastaavat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `jarjestys` bigint(20) NOT NULL DEFAULT '0',
  `tuoteno` varchar(60) NOT NULL DEFAULT '',
  `id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `vaihtoehtoinen` char(1) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_id` (`yhtio`,`id`),
  KEY `yhtio_tuoteno` (`yhtio`,`tuoteno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yhteyshenkilo`
--

DROP TABLE IF EXISTS `yhteyshenkilo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yhteyshenkilo` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `tyyppi` char(1) NOT NULL DEFAULT '',
  `liitostunnus` int(11) NOT NULL DEFAULT '0',
  `nimi` varchar(50) NOT NULL DEFAULT '',
  `titteli` varchar(50) NOT NULL DEFAULT '',
  `rooli` varchar(100) NOT NULL DEFAULT '',
  `suoramarkkinointi` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `puh` varchar(50) NOT NULL DEFAULT '',
  `gsm` varchar(50) NOT NULL DEFAULT '',
  `fax` varchar(50) NOT NULL DEFAULT '',
  `www` varchar(50) NOT NULL DEFAULT '',
  `nimitarkenne` varchar(100) DEFAULT NULL,
  `osoite` varchar(100) DEFAULT NULL,
  `postino` varchar(5) DEFAULT NULL,
  `postitp` varchar(50) DEFAULT NULL,
  `maa` varchar(50) DEFAULT NULL,
  `fakta` text,
  `ulkoinen_asiakasnumero` varchar(50) NOT NULL DEFAULT '',
  `tilausyhteyshenkilo` char(1) NOT NULL DEFAULT '',
  `oletusyhteyshenkilo` char(1) NOT NULL DEFAULT '',
  `aktivointikuittaus` varchar(1) NOT NULL DEFAULT '',
  `verkkokauppa_salasana` varchar(255) DEFAULT NULL,
  `verkkokauppa_nakyvyys` varchar(255) DEFAULT NULL,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_tyyppi_liitostunnus_rooli` (`yhtio`,`tyyppi`,`liitostunnus`,`rooli`)
) ENGINE=MyISAM AUTO_INCREMENT=6629 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yhtio`
--

DROP TABLE IF EXISTS `yhtio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yhtio` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `jarjestys` int(3) NOT NULL DEFAULT '0',
  `konserni` varchar(5) NOT NULL DEFAULT '',
  `ytunnus` varchar(30) NOT NULL DEFAULT '',
  `ovttunnus` varchar(25) NOT NULL DEFAULT '',
  `kotipaikka` varchar(25) NOT NULL DEFAULT '',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(30) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(30) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `laskutus_nimi` varchar(60) NOT NULL DEFAULT '',
  `laskutus_osoite` varchar(30) NOT NULL DEFAULT '',
  `laskutus_postino` varchar(15) NOT NULL DEFAULT '',
  `laskutus_postitp` varchar(30) NOT NULL DEFAULT '',
  `laskutus_maa` varchar(2) NOT NULL DEFAULT '',
  `kieli` char(2) NOT NULL DEFAULT '',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `fax` varchar(25) NOT NULL DEFAULT '',
  `puhelin` varchar(25) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `www` varchar(100) NOT NULL DEFAULT '',
  `ean` int(11) NOT NULL DEFAULT '0',
  `pankkinimi1` varchar(80) NOT NULL DEFAULT '',
  `pankkitili1` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban1` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift1` varchar(60) NOT NULL DEFAULT '',
  `pankkinimi2` varchar(80) NOT NULL DEFAULT '',
  `pankkitili2` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban2` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift2` varchar(60) NOT NULL DEFAULT '',
  `pankkinimi3` varchar(80) NOT NULL DEFAULT '',
  `pankkitili3` varchar(80) NOT NULL DEFAULT '',
  `pankkiiban3` varchar(60) NOT NULL DEFAULT '',
  `pankkiswift3` varchar(60) NOT NULL DEFAULT '',
  `kassa` varchar(6) NOT NULL DEFAULT '',
  `pankkikortti` varchar(6) NOT NULL DEFAULT '',
  `luottokortti` varchar(6) NOT NULL DEFAULT '',
  `kassaerotus` varchar(6) NOT NULL DEFAULT '',
  `kateistilitys` varchar(6) NOT NULL DEFAULT '',
  `myynti` varchar(6) NOT NULL DEFAULT '',
  `myynti_eu` varchar(6) NOT NULL DEFAULT '',
  `myynti_ei_eu` varchar(6) NOT NULL DEFAULT '',
  `myynti_kaanteinen` varchar(6) NOT NULL DEFAULT '',
  `tilino_triang` varchar(6) NOT NULL DEFAULT '',
  `myynti_marginaali` varchar(6) NOT NULL DEFAULT '',
  `osto_marginaali` varchar(6) NOT NULL DEFAULT '',
  `osto_rahti` varchar(6) NOT NULL DEFAULT '',
  `osto_kulu` varchar(6) NOT NULL DEFAULT '',
  `osto_rivi_kulu` varchar(6) NOT NULL DEFAULT '',
  `myyntisaamiset` varchar(6) NOT NULL DEFAULT '',
  `luottotappiot` varchar(6) NOT NULL DEFAULT '',
  `factoringsaamiset` varchar(6) NOT NULL DEFAULT '',
  `konsernimyyntisaamiset` varchar(6) NOT NULL DEFAULT '',
  `ostovelat` varchar(6) NOT NULL DEFAULT '',
  `konserniostovelat` varchar(6) NOT NULL DEFAULT '',
  `valuuttaero` varchar(6) NOT NULL DEFAULT '',
  `myynninvaluuttaero` varchar(6) NOT NULL DEFAULT '',
  `kassaale` varchar(6) NOT NULL DEFAULT '',
  `myynninkassaale` varchar(6) NOT NULL DEFAULT '',
  `muutkulut` varchar(6) NOT NULL DEFAULT '',
  `pyoristys` varchar(6) NOT NULL DEFAULT '',
  `varasto` varchar(6) NOT NULL DEFAULT '',
  `raaka_ainevarasto` varchar(6) NOT NULL DEFAULT '',
  `ennakkolaskun_asiakasvarasto` varchar(6) NOT NULL DEFAULT '',
  `varastonmuutos` varchar(6) NOT NULL DEFAULT '',
  `raaka_ainevarastonmuutos` varchar(6) NOT NULL DEFAULT '',
  `varastonmuutos_valmistuksesta` varchar(6) NOT NULL DEFAULT '',
  `varastonmuutos_epakurantti` varchar(6) NOT NULL DEFAULT '',
  `varastonmuutos_inventointi` varchar(6) NOT NULL DEFAULT '',
  `varastonmuutos_rahti` varchar(6) NOT NULL DEFAULT '',
  `matkalla_olevat` varchar(6) NOT NULL DEFAULT '',
  `alv` varchar(6) NOT NULL DEFAULT '',
  `siirtosaamiset` varchar(6) NOT NULL DEFAULT '',
  `siirtovelka` varchar(6) NOT NULL DEFAULT '',
  `konsernisaamiset` varchar(6) NOT NULL DEFAULT '',
  `konsernivelat` varchar(6) NOT NULL DEFAULT '',
  `selvittelytili` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_kotimaa_kulu` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_kotimaa_rahti` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_kotimaa_vaihto_omaisuus` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_kotimaa_raaka_aine` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_eu_kulu` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_eu_rahti` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_eu_vaihto_omaisuus` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_eu_raaka_aine` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_ei_eu_kulu` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_ei_eu_rahti` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_ei_eu_vaihto_omaisuus` varchar(6) NOT NULL DEFAULT '',
  `ostolasku_ei_eu_raaka_aine` varchar(6) NOT NULL DEFAULT '',
  `myynti_kustp` int(11) NOT NULL DEFAULT '0',
  `myynti_kohde` int(11) NOT NULL DEFAULT '0',
  `myynti_projekti` int(11) NOT NULL DEFAULT '0',
  `tilikauden_tulos` int(11) NOT NULL DEFAULT '0',
  `tilikausi_alku` date NOT NULL DEFAULT '0000-00-00',
  `tilikausi_loppu` date NOT NULL DEFAULT '0000-00-00',
  `ostoreskontrakausi_alku` date NOT NULL DEFAULT '0000-00-00',
  `ostoreskontrakausi_loppu` date NOT NULL DEFAULT '0000-00-00',
  `myyntireskontrakausi_alku` date NOT NULL DEFAULT '0000-00-00',
  `myyntireskontrakausi_loppu` date NOT NULL DEFAULT '0000-00-00',
  `tullin_asiaknro` varchar(6) NOT NULL DEFAULT '',
  `tullin_lupanro` varchar(10) NOT NULL DEFAULT '',
  `tullikamari` int(2) NOT NULL DEFAULT '0',
  `tullipaate` char(3) NOT NULL DEFAULT '',
  `tulli_vahennettava_era` decimal(8,2) NOT NULL DEFAULT '0.00',
  `tulli_lisattava_era` decimal(8,2) NOT NULL DEFAULT '0.00',
  `kotitullauslupa` varchar(15) NOT NULL DEFAULT '',
  `tilastotullikamari` int(2) NOT NULL DEFAULT '0',
  `intrastat_sarjanro` char(3) NOT NULL DEFAULT '',
  `int_koodi` varchar(5) NOT NULL DEFAULT '',
  `viivastyskorko` decimal(5,2) NOT NULL DEFAULT '0.00',
  `karhuerapvm` int(2) NOT NULL DEFAULT '0',
  `kuluprosentti` decimal(8,3) NOT NULL DEFAULT '0.000',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yhtion_parametrit`
--

DROP TABLE IF EXISTS `yhtion_parametrit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yhtion_parametrit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `admin_email` varchar(100) NOT NULL DEFAULT '',
  `alert_email` varchar(100) NOT NULL DEFAULT '',
  `talhal_email` varchar(100) NOT NULL DEFAULT '',
  `sahkopostilasku_cc_email` varchar(100) NOT NULL DEFAULT '',
  `maksukehotus_cc_email` varchar(100) NOT NULL DEFAULT '',
  `varauskalenteri_email` text,
  `tuotekopio_email` varchar(100) NOT NULL DEFAULT '',
  `jt_email` varchar(100) NOT NULL DEFAULT '',
  `edi_email` varchar(100) NOT NULL DEFAULT '',
  `extranet_kerayspoikkeama_email` varchar(100) NOT NULL DEFAULT '',
  `siirtolista_email` varchar(100) NOT NULL DEFAULT '',
  `changelog_email` varchar(100) NOT NULL DEFAULT '',
  `hyvaksyttavia_tilauksia_email` varchar(100) NOT NULL DEFAULT '',
  `ostotilaus_email` varchar(100) NOT NULL DEFAULT '',
  `hyvaksyttavat_extranet_email` varchar(255) NOT NULL DEFAULT '',
  `alert_varasto_kayttajat` varchar(100) NOT NULL DEFAULT '',
  `verkkolasku_lah` varchar(10) NOT NULL DEFAULT '',
  `finvoice_versio` varchar(1) NOT NULL DEFAULT '',
  `verkkolasku_vienti` char(1) NOT NULL DEFAULT '',
  `finvoice_senderpartyid` varchar(100) NOT NULL DEFAULT '',
  `finvoice_senderintermediator` varchar(100) NOT NULL DEFAULT '',
  `verkkotunnus_vas` varchar(100) NOT NULL DEFAULT '',
  `verkkotunnus_lah` varchar(100) NOT NULL DEFAULT '',
  `verkkosala_vas` varchar(100) NOT NULL DEFAULT '',
  `verkkosala_lah` varchar(100) NOT NULL DEFAULT '',
  `apix_tunnus` varchar(100) NOT NULL DEFAULT '',
  `apix_avain` varchar(100) NOT NULL DEFAULT '',
  `apix_edi_tunnus` varchar(100) NOT NULL DEFAULT '',
  `apix_edi_avain` varchar(100) NOT NULL DEFAULT '',
  `maventa_api_avain` varchar(100) NOT NULL DEFAULT '',
  `maventa_yrityksen_uuid` varchar(100) NOT NULL DEFAULT '',
  `maventa_ohjelmisto_api_avain` varchar(100) NOT NULL DEFAULT '',
  `maventa_aikaleima` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lasku_tulostin` bigint(20) NOT NULL DEFAULT '0',
  `pankkitiedostot` char(1) NOT NULL DEFAULT '',
  `tiliotteen_selvittelyt` char(1) NOT NULL DEFAULT '',
  `kuvapankki_polku` varchar(255) NOT NULL DEFAULT '',
  `skannatut_laskut_polku` varchar(255) NOT NULL DEFAULT '',
  `postittaja_email` varchar(200) NOT NULL DEFAULT '',
  `kayttoliittyma` char(1) NOT NULL DEFAULT '',
  `css` text,
  `css_classic` text,
  `css_extranet` text,
  `css_verkkokauppa` text,
  `web_seuranta` text,
  `lahetteen_tulostustapa` char(1) NOT NULL DEFAULT '',
  `kulujen_laskeminen_hintoihin` char(1) NOT NULL DEFAULT '',
  `myyntitilin_tulostustapa` char(1) NOT NULL DEFAULT '',
  `valmistuksen_tulostustapa` char(1) NOT NULL DEFAULT '',
  `siirtolistan_tulostustapa` char(1) NOT NULL DEFAULT '',
  `lahetteen_jarjestys` char(1) NOT NULL DEFAULT '',
  `lahetteen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `lahetteen_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `laskun_jarjestys` char(1) NOT NULL DEFAULT '',
  `laskun_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `laskun_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `tilauksen_jarjestys` char(1) NOT NULL DEFAULT '',
  `tilauksen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `kerayslistan_jarjestys` char(1) NOT NULL DEFAULT '',
  `kerayslistan_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `kerayslistan_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `valmistus_kerayslistan_jarjestys` char(1) NOT NULL DEFAULT '',
  `valmistus_kerayslistan_jarjestys_suunta` char(4) NOT NULL DEFAULT '',
  `valmistus_kerayslistan_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `valmistuksessa_kaytetaan_tilakoodeja` char(1) NOT NULL DEFAULT '',
  `ostotilauksen_jarjestys` char(1) NOT NULL DEFAULT '',
  `ostotilauksen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `tarjouksen_jarjestys` char(1) NOT NULL DEFAULT '',
  `tarjouksen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `tarjouksen_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `tyomaarayksen_jarjestys` char(1) NOT NULL DEFAULT '',
  `tyomaarayksen_jarjestys_suunta` varchar(4) NOT NULL DEFAULT '',
  `tyomaarayksen_palvelutjatuottet` char(1) NOT NULL DEFAULT '',
  `tuotteiden_jarjestys_raportoinnissa` char(1) NOT NULL DEFAULT '',
  `pakkaamolokerot` char(1) NOT NULL DEFAULT '',
  `konsernivarasto` char(1) NOT NULL DEFAULT '',
  `lahete_allekirjoitus` char(1) NOT NULL DEFAULT '',
  `lomakkeiden_allekirjoitus` char(1) NOT NULL DEFAULT '',
  `lahete_tyyppi_tulostus` char(1) NOT NULL DEFAULT '',
  `laskutyyppi` int(2) NOT NULL DEFAULT '0',
  `laskun_monistus_kommenttikentta` char(1) NOT NULL DEFAULT '',
  `viivakoodi_laskulle` char(1) NOT NULL DEFAULT '',
  `koontilaskut_yhdistetaan` char(1) NOT NULL DEFAULT '',
  `koontilaskut_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `koontilahete_kollitiedot` char(1) NOT NULL DEFAULT '',
  `tilausvahvistustyyppi` varchar(150) NOT NULL DEFAULT '',
  `tilausvahvistus_tilausnumero` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `tilausvahvistus_tyyppi_tulostus` char(1) NOT NULL DEFAULT '',
  `tilausvahvistus_lahetys` int(1) NOT NULL DEFAULT '0',
  `tilausvahvistus_tallenna` char(1) NOT NULL DEFAULT '',
  `tilausvahvistus_sisaisista` char(1) NOT NULL DEFAULT '',
  `naytayhteensamaarat` char(1) NOT NULL DEFAULT '',
  `tarjoustyyppi` int(1) NOT NULL DEFAULT '0',
  `siirtolistatyyppi` char(1) NOT NULL DEFAULT '',
  `varastosiirto_tilausvahvistus` char(1) NOT NULL DEFAULT '',
  `varastosiirto_kohdepaikka` char(1) NOT NULL DEFAULT '',
  `ostotilaustyyppi` char(1) NOT NULL DEFAULT '',
  `ostotilaukseen_toimittajan_toimaika` char(1) NOT NULL DEFAULT '',
  `ostotilaus_saman_tuotteen_lisays` char(1) NOT NULL DEFAULT '',
  `ostotilauksen_tuloste` char(1) NOT NULL DEFAULT '',
  `ostolaskujen_paivays` char(1) NOT NULL DEFAULT '',
  `ostolaskujen_oletusvaluutta` char(1) NOT NULL DEFAULT '',
  `ostolaskujen_oletusiban` varchar(1) NOT NULL DEFAULT '',
  `ostolaskujen_kurssipaiva` int(1) NOT NULL DEFAULT '0',
  `myyntilaskujen_kurssipaiva` tinyint(4) NOT NULL DEFAULT '0',
  `laskutus_tulevaisuuteen` varchar(1) NOT NULL DEFAULT '',
  `kateiskuitin_paivays` varchar(1) NOT NULL DEFAULT '',
  `ostolaskun_kulutilit` char(1) NOT NULL DEFAULT '',
  `ostolaskun_kulutilit_kayttaytyminen` char(1) NOT NULL DEFAULT '',
  `tarkenteiden_tarkistus_hyvaksynnassa` char(1) NOT NULL DEFAULT '',
  `tyomaaraystyyppi` char(1) NOT NULL DEFAULT '',
  `tyomaarays_tulostus_lisarivit` char(1) NOT NULL DEFAULT '',
  `tyomaarays_asennuskalenteri_muistutus` char(1) NOT NULL DEFAULT '',
  `viivakoodi_purkulistaan` char(1) NOT NULL DEFAULT '',
  `purkulistan_asettelu` char(1) NOT NULL DEFAULT '',
  `kerayslista_kerayspaikka` varchar(1) NOT NULL DEFAULT '',
  `laskutuskielto` int(1) NOT NULL DEFAULT '0',
  `saldovirhe_esto_laskutus` char(1) NOT NULL DEFAULT '',
  `kehahinvirhe_esto_laskutus` char(1) NOT NULL DEFAULT '',
  `rahti_hinnoittelu` char(1) NOT NULL DEFAULT '',
  `rahti_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `jalkivaatimus_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `kasittelykulu_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `maksuehto_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `ennakkomaksu_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `alennus_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `laskutuslisa_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `erilliskasiteltava_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `lisakulu_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `laskutuslisa` decimal(5,2) NOT NULL DEFAULT '0.00',
  `laskutuslisa_tyyppi` char(1) NOT NULL DEFAULT '',
  `kuljetusvakuutus_tuotenumero` varchar(60) NOT NULL DEFAULT '',
  `kuljetusvakuutus` decimal(5,2) NOT NULL DEFAULT '0.00',
  `kuljetusvakuutus_tyyppi` char(1) NOT NULL DEFAULT '',
  `kuljetusvakuutus_koonti` char(1) NOT NULL DEFAULT '',
  `tuotteen_oletuspaikka` varchar(250) NOT NULL DEFAULT '',
  `alv_kasittely` char(1) NOT NULL DEFAULT '',
  `alv_kasittely_hintamuunnos` char(1) NOT NULL DEFAULT '',
  `alv_velvollinen` varchar(1) NOT NULL DEFAULT '',
  `asiakashinta_netto` char(2) NOT NULL DEFAULT '',
  `puute_jt_oletus` char(1) NOT NULL DEFAULT '',
  `puute_jt_kerataanko` char(1) NOT NULL DEFAULT '',
  `myynti_jt_huom` varchar(1) NOT NULL DEFAULT '',
  `kerataanko_jos_vain_puute_jt` char(1) NOT NULL DEFAULT '',
  `jt_automatiikka` char(1) NOT NULL DEFAULT '',
  `jt_automatiikka_mitatoi_tilaus` char(1) NOT NULL DEFAULT '',
  `saapumisen_jt_kasittely` char(1) NOT NULL DEFAULT '',
  `jt_toimitus_varastorajaus` char(1) NOT NULL DEFAULT '',
  `jt_rahti` char(1) NOT NULL DEFAULT '',
  `jt_rivien_kasittely` char(1) NOT NULL DEFAULT '',
  `jt_toimitusaika_email_vahvistus` char(1) NOT NULL DEFAULT '',
  `vahvistusviesti_asiakkaalle` char(1) NOT NULL DEFAULT '',
  `jt_manual` char(1) NOT NULL DEFAULT '',
  `jt_asiakkaan_tilausnumero` char(1) NOT NULL DEFAULT '',
  `jt_siirtolistojen_yhdistaminen` char(1) NOT NULL DEFAULT '',
  `jt_email_tilauksessa` varchar(1) NOT NULL DEFAULT '',
  `kerayslistojen_yhdistaminen` char(1) NOT NULL DEFAULT '',
  `karayksesta_rahtikirjasyottoon` char(1) NOT NULL DEFAULT '',
  `rahtikirjojen_esisyotto` char(1) NOT NULL DEFAULT '',
  `saldottomat_rahtikirjansyottoon` varchar(1) NOT NULL DEFAULT '',
  `rahtikirjan_kollit_ja_lajit` char(1) NOT NULL DEFAULT '',
  `laskunsummapyoristys` char(1) NOT NULL DEFAULT '',
  `hintapyoristys` int(2) NOT NULL DEFAULT '2',
  `hintapyoristys_loppunollat` char(1) NOT NULL DEFAULT '',
  `viitteen_kasinsyotto` char(1) NOT NULL DEFAULT '',
  `suoratoim_ulkomaan_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `erikoisvarastomyynti_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `erikoisvarastomyynti_alarajasumma_rivi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `rahtivapaa_alarajasumma` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tehdas_saldo_alaraja` decimal(12,2) NOT NULL DEFAULT '0.00',
  `logo` varchar(100) NOT NULL DEFAULT '',
  `lasku_logo` varchar(100) NOT NULL DEFAULT '',
  `lasku_logo_positio` varchar(7) NOT NULL DEFAULT '',
  `lasku_logo_koko` int(3) NOT NULL DEFAULT '0',
  `naytetaan_katteet_tilauksella` char(1) NOT NULL DEFAULT '',
  `tuotekommentti_tilausriville` varchar(1) NOT NULL DEFAULT '',
  `tilauksen_yhteyshenkilot` char(1) NOT NULL DEFAULT '',
  `tarjouksen_voi_versioida` char(1) NOT NULL DEFAULT '',
  `nimityksen_muutos_tilauksella` char(1) NOT NULL DEFAULT '',
  `automaattinen_tuotehaku` char(1) NOT NULL DEFAULT '',
  `tuotekysely` char(1) NOT NULL DEFAULT '',
  `jyvita_alennus` char(1) NOT NULL DEFAULT '',
  `salli_jyvitys_myynnissa` char(1) NOT NULL DEFAULT '',
  `salli_jyvitys_tarjouksella` char(1) NOT NULL DEFAULT '',
  `naytetaan_tilausvahvistusnappi` char(1) NOT NULL DEFAULT '',
  `rivinumero_syotto` char(1) NOT NULL DEFAULT '',
  `tee_osto_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `tee_automaattinen_osto_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `tee_automaattinen_siirto_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `tee_valmistus_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `tee_siirtolista_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `kirjanpidollinen_varastosiirto_myyntitilaukselta` char(1) NOT NULL DEFAULT '',
  `automaattinen_jt_toimitus` char(1) NOT NULL DEFAULT '',
  `automaattinen_jt_toimitus_valmistus` char(1) NOT NULL DEFAULT '',
  `automaattinen_jt_toimitus_siirtolista` char(1) NOT NULL DEFAULT '',
  `siirtolistat_vastaanotetaan_per_lahto` char(1) NOT NULL DEFAULT '',
  `dynaaminen_kassamyynti` char(1) NOT NULL DEFAULT '',
  `maksupaate_kassamyynti` char(1) NOT NULL DEFAULT '',
  `pikatilaus_focus` char(1) NOT NULL DEFAULT '',
  `kerayspoikkeama_kasittely` char(1) NOT NULL DEFAULT '',
  `kerayspoikkeamaviestin_lahetys` int(1) NOT NULL DEFAULT '0',
  `kerayspoikkeama_email` char(1) NOT NULL DEFAULT '',
  `keraysvahvistus_lahetys` char(1) NOT NULL DEFAULT '',
  `kerays_riveittain` varchar(255) NOT NULL DEFAULT '',
  `oletus_toimitusehto` varchar(30) NOT NULL DEFAULT '',
  `oletus_toimitusehto2` varchar(30) NOT NULL DEFAULT '',
  `sad_lomake_tyyppi` char(1) NOT NULL DEFAULT '',
  `tarjouksen_voimaika` int(11) NOT NULL DEFAULT '14',
  `tarjouksen_tuotepaikat` char(1) NOT NULL DEFAULT '',
  `tarjouksen_alv_kasittely` char(1) NOT NULL DEFAULT '',
  `splittauskielto` char(1) NOT NULL DEFAULT '',
  `rekursiiviset_reseptit` char(1) NOT NULL DEFAULT '',
  `rekursiiviset_tuoteperheet` char(1) NOT NULL DEFAULT '',
  `valmistusten_yhdistaminen` char(1) NOT NULL DEFAULT '',
  `rahtikirjan_kopiomaara` int(2) NOT NULL DEFAULT '0',
  `saldotarkistus_tulostusjonossa` varchar(2) NOT NULL DEFAULT '',
  `kerataanko_saldottomat` char(1) NOT NULL DEFAULT '',
  `kerataanko_valmistukset` char(1) NOT NULL DEFAULT '',
  `kerataanko_tyomaaraykset` varchar(1) NOT NULL DEFAULT '',
  `saldo_kasittely` char(1) NOT NULL DEFAULT '',
  `ytunnus_tarkistukset` char(1) NOT NULL DEFAULT '',
  `vienti_erittelyn_tulostus` char(1) NOT NULL DEFAULT '',
  `vientierittelyn_painot` varchar(1) NOT NULL DEFAULT '',
  `vientikiellon_ohitus` char(1) NOT NULL DEFAULT '',
  `vientitietojen_autosyotto` varchar(1) NOT NULL DEFAULT '',
  `oletus_lahetekpl` int(11) NOT NULL DEFAULT '0',
  `oletus_lahetekpl_siirtolista` int(2) NOT NULL DEFAULT '1',
  `oletus_laskukpl_toimitatilaus` int(2) NOT NULL DEFAULT '1',
  `oletus_oslappkpl` int(11) NOT NULL DEFAULT '0',
  `oletus_rahtikirja_lahetekpl` int(11) NOT NULL DEFAULT '0',
  `oletus_rahtikirja_oslappkpl` int(11) NOT NULL DEFAULT '0',
  `oslapp_rakir_logo` char(1) NOT NULL DEFAULT '',
  `osoitelappu_lisatiedot` char(1) NOT NULL DEFAULT '',
  `rahti_ja_kasittelykulut_kasin` char(1) NOT NULL DEFAULT '',
  `synkronoi` text,
  `myyntitilaus_osatoimitus` char(1) NOT NULL DEFAULT '',
  `myyntitilaus_ytunnus_syotto` char(1) NOT NULL DEFAULT '',
  `myyntitilaus_asiakasmemo` char(1) NOT NULL DEFAULT '',
  `myyntitilaus_saatavat` char(1) NOT NULL DEFAULT '',
  `myyntitilaus_tarjoukseksi` char(1) NOT NULL DEFAULT '',
  `myyntitilauksen_liitteet` char(1) NOT NULL DEFAULT '',
  `myyntitilauksen_toimipaikka` char(1) NOT NULL DEFAULT '',
  `asiakasmyyja_tilaukselle` varchar(1) NOT NULL DEFAULT '',
  `varastopaikan_lippu` char(1) NOT NULL DEFAULT '',
  `varastopaikkojen_maarittely` char(1) NOT NULL DEFAULT '',
  `varastontunniste` char(1) NOT NULL DEFAULT '',
  `pakollinen_varasto` char(1) NOT NULL DEFAULT '',
  `ulkoinen_jarjestelma` varchar(1) NOT NULL DEFAULT '',
  `ulkoinen_jarjestelma_lukko` varchar(1) DEFAULT '',
  `suuntalavat` char(1) NOT NULL DEFAULT '',
  `kerayserat` char(1) NOT NULL DEFAULT '',
  `varaako_jt_saldoa` char(1) NOT NULL DEFAULT '',
  `korvaavan_hinta_ylaraja` varchar(3) NOT NULL DEFAULT '',
  `korvaavat_hyvaksynta` char(1) NOT NULL DEFAULT '',
  `korvaavuusketjun_jarjestys` char(1) NOT NULL DEFAULT '',
  `korvaavuusketjun_puutekasittely` char(1) NOT NULL DEFAULT '',
  `vastaavuusketjun_jarjestys` char(1) NOT NULL DEFAULT '',
  `monikayttajakalenteri` char(1) NOT NULL DEFAULT '',
  `automaattinen_asiakasnumerointi` char(1) NOT NULL DEFAULT '',
  `asiakasnumeroinnin_aloituskohta` int(11) NOT NULL DEFAULT '0',
  `asiakkaan_tarkenne` char(1) NOT NULL DEFAULT '',
  `haejaselaa_konsernisaldot` char(1) NOT NULL DEFAULT '',
  `viikkosuunnitelma` char(1) NOT NULL DEFAULT '',
  `kalenterimerkinnat` char(1) NOT NULL DEFAULT '',
  `kalenteri_aikavali` varchar(2) NOT NULL DEFAULT '',
  `tuntikirjausten_erittely` char(1) NOT NULL DEFAULT '',
  `variaatiomyynti` char(1) NOT NULL DEFAULT '',
  `nayta_variaatiot` char(1) NOT NULL DEFAULT '',
  `myyntiera_pyoristys` char(1) NOT NULL DEFAULT '',
  `minimimaara_pyoristys` varchar(1) DEFAULT '',
  `ostoera_pyoristys` char(1) NOT NULL DEFAULT '',
  `reklamaation_kasittely` char(1) NOT NULL DEFAULT '',
  `reklamaation_kasittely_tuoteperhe` char(1) NOT NULL DEFAULT '',
  `reklamaation_hinnoittelu` char(1) NOT NULL DEFAULT '',
  `reklamaation_vastaanottovarasto` int(11) NOT NULL DEFAULT '0',
  `myytitilauksen_kululaskut` char(1) NOT NULL DEFAULT '',
  `huomautetaanko_poistuvasta` char(1) NOT NULL DEFAULT '',
  `palvelutuotteiden_kehahinnat` char(1) NOT NULL DEFAULT '',
  `kehahinarvio_ennen_ensituloa` char(1) NOT NULL DEFAULT '',
  `tyomaaraystiedot_tarjouksella` char(1) NOT NULL DEFAULT '',
  `rinnakkaisostaja_myynnissa` char(1) NOT NULL DEFAULT '',
  `tilausrivien_toimitettuaika` char(1) NOT NULL DEFAULT '',
  `tilausrivin_esisyotto` varchar(1) NOT NULL DEFAULT '',
  `tilausvahvistus_jttoimituksista` char(1) NOT NULL DEFAULT '',
  `jt_rivien_saapumisajan_nayttaminen` char(1) NOT NULL DEFAULT '',
  `naytetaanko_osaston_ja_tryn_selite` char(1) NOT NULL DEFAULT '',
  `naytetaanko_ale_peruste_tilausrivilla` char(1) NOT NULL DEFAULT '',
  `tilauksen_myyntieratiedot` char(1) NOT NULL DEFAULT '',
  `tilaukselle_mittatiedot` varchar(1) NOT NULL DEFAULT '',
  `livetuotehaku_tilauksella` char(1) NOT NULL DEFAULT '',
  `livetuotehaku_minimi` tinyint(4) NOT NULL DEFAULT '3',
  `livetuotehaku_hakutapa` char(1) NOT NULL DEFAULT '',
  `livetuotehaku_hakutapa_extranet` char(1) NOT NULL DEFAULT '',
  `livetuotehaku_poistetut` char(1) NOT NULL DEFAULT '',
  `poistetut_lisays` char(1) NOT NULL DEFAULT '',
  `iltasiivo_mitatoi_ext_tilauksia` char(3) NOT NULL DEFAULT '',
  `iltasiivo_mitatoi_kassamyynti_tilauksia` varchar(150) NOT NULL DEFAULT '',
  `extranet_tilaus_varaa_saldoa` varchar(3) NOT NULL DEFAULT '',
  `extranet_nayta_saldo` char(1) NOT NULL DEFAULT '',
  `extranet_nayta_kuvaus` char(1) NOT NULL DEFAULT '',
  `extranet_poikkeava_toimitusosoite` char(1) NOT NULL DEFAULT '',
  `extranet_keraysprioriteetti` char(1) NOT NULL DEFAULT '',
  `extranet_private_label` varchar(1) NOT NULL DEFAULT '',
  `ext_tilauksen_hyvaksyja_myyjaksi` char(1) NOT NULL DEFAULT '',
  `tuoteperhe_suoratoimitus` char(1) NOT NULL DEFAULT '',
  `tuoteperheinfo_lahetteella` char(1) NOT NULL DEFAULT '',
  `kirjanpidon_tarkenteet` char(1) NOT NULL DEFAULT '',
  `tuoteperhe_kasittely` char(1) NOT NULL DEFAULT '',
  `hyvaksy_tarjous_tilaustyyppi` char(1) NOT NULL DEFAULT '',
  `vaihda_asiakas_hintapaiv` char(1) NOT NULL DEFAULT '',
  `keraysaikarajaus` char(2) NOT NULL DEFAULT '',
  `liitetiedostojen_nimeaminen` char(1) NOT NULL DEFAULT '',
  `varastoonvientipaiva` char(1) NOT NULL DEFAULT '',
  `varaston_splittaus` char(1) NOT NULL DEFAULT '',
  `oletusvarasto_tilaukselle` char(1) NOT NULL DEFAULT '',
  `ostoreskontra_kassaalekasittely` int(1) NOT NULL DEFAULT '0',
  `myyntilaskun_erapvmlaskenta` char(1) NOT NULL DEFAULT '',
  `vak_kasittely` char(1) NOT NULL DEFAULT '',
  `vak_erittely` char(1) NOT NULL DEFAULT '',
  `intrastat_kaytossa` char(1) NOT NULL DEFAULT '',
  `intrastat_pvm` char(1) NOT NULL DEFAULT '',
  `myynti_asiakhin_tallenna` char(1) NOT NULL DEFAULT '',
  `myynnin_alekentat` int(1) NOT NULL DEFAULT '1',
  `myynnin_alekentat_muokkaus` varchar(50) NOT NULL DEFAULT '1',
  `oston_alekentat` char(1) NOT NULL DEFAULT '1',
  `tilausrivi_omalle_tilaukselle` char(1) NOT NULL DEFAULT '',
  `haejaselaa_saapumispvm` char(1) NOT NULL DEFAULT '',
  `ennakkotilausten_toimitus` char(1) NOT NULL DEFAULT '',
  `jalkilaskenta_kuluperuste` varchar(2) NOT NULL DEFAULT '',
  `teeostotilaus_valmistuksen_tulosjonosta` char(1) NOT NULL DEFAULT '',
  `tarkista_eankoodi` char(1) NOT NULL DEFAULT '',
  `raaka_aineet_valmistusmyynti` char(1) NOT NULL DEFAULT '',
  `raaka_aine_tiliointi` char(1) NOT NULL DEFAULT '',
  `tulosta_valmistus_tulosteet` char(1) NOT NULL DEFAULT '',
  `valmistuksien_kasittely` char(1) NOT NULL DEFAULT '',
  `kehahinta_valmistuksella` char(1) NOT NULL DEFAULT '',
  `saldo_varastossa_valmistuksella` char(1) NOT NULL DEFAULT '',
  `varastonarvon_jako_usealle_valmisteelle` char(1) NOT NULL DEFAULT '',
  `maksukehotus_kentat` char(1) NOT NULL DEFAULT '',
  `maksukehotuksen_osoitetiedot` char(1) NOT NULL DEFAULT '',
  `viitemaksujen_kohdistus_sallittu_heitto` float(3,2) NOT NULL DEFAULT '0.00',
  `luottorajan_ylitys` char(1) NOT NULL DEFAULT '',
  `luottorajan_tarkistus` char(1) NOT NULL DEFAULT '',
  `erapaivan_ylityksen_raja` int(5) NOT NULL DEFAULT '15',
  `erapaivan_ylityksen_toimenpide` char(1) NOT NULL DEFAULT '',
  `vastaanottoraportti` char(1) NOT NULL DEFAULT '',
  `tositteen_tilioinnit` char(1) NOT NULL DEFAULT '',
  `ei_alennuksia_lapsituotteille` char(1) NOT NULL DEFAULT '',
  `epakurantoinnin_myyntihintaleikkuri` char(1) NOT NULL DEFAULT '',
  `ennakkolaskun_tyyppi` char(1) NOT NULL DEFAULT '',
  `asiakasvarasto` varchar(5) NOT NULL DEFAULT '',
  `maksusopimus_toimitus` char(1) NOT NULL DEFAULT '',
  `ennakkolasku_myyntitilaukselta` varchar(1) NOT NULL DEFAULT '',
  `matkalaskun_tarkastus` char(1) NOT NULL DEFAULT '',
  `seuraava_tuotenumero` char(1) NOT NULL DEFAULT '',
  `myyntitilausrivi_rekisterinumero` char(1) NOT NULL DEFAULT '',
  `ostotilauksen_kasittely` varchar(150) NOT NULL DEFAULT '',
  `vastaavat_tuotteet_esitysmuoto` char(1) NOT NULL DEFAULT '',
  `laite_huolto` char(1) NOT NULL DEFAULT '',
  `paivita_oletuspaikka` char(1) NOT NULL DEFAULT '',
  `myyntihinta_paivitys_saapuminen` char(1) NOT NULL DEFAULT '',
  `myyntihinnan_muutoksien_logitus` char(1) NOT NULL DEFAULT '',
  `suoratoim_lisamyynti_osto` char(1) NOT NULL DEFAULT '',
  `editilaus_suoratoimitus` varchar(1) NOT NULL DEFAULT '',
  `toimipaikkakasittely` char(1) NOT NULL DEFAULT '',
  `tarkenteiden_prioriteetti` char(1) NOT NULL DEFAULT '',
  `suoratoimitusvarasto` int(11) NOT NULL DEFAULT '0',
  `takuuvarasto` int(11) NOT NULL DEFAULT '0',
  `nouto_suoraan_laskutukseen` char(1) NOT NULL DEFAULT '',
  `reklamaatiot_lasku` char(1) NOT NULL DEFAULT '',
  `yhdistetaan_identtiset_laskulla` char(1) NOT NULL DEFAULT '',
  `sallitaanko_kateismyynti_laskulle` char(1) NOT NULL DEFAULT '',
  `lapsituotteen_poiston_esto` tinyint(1) NOT NULL DEFAULT '0',
  `pura_osaluettelot` char(1) NOT NULL DEFAULT '',
  `laiterekisteri_kaytossa` char(1) NOT NULL DEFAULT '',
  `inventointi_yhteenveto` char(1) NOT NULL DEFAULT '',
  `laaja_inventointilista` char(1) NOT NULL DEFAULT '',
  `inventointi_siirron_yhteydessa` char(1) NOT NULL DEFAULT '',
  `muokkaatilaus_pv_rajaus` int(11) NOT NULL DEFAULT '0',
  `tilausrivin_korvamerkinta` char(1) NOT NULL DEFAULT '',
  `tilausrivin_kateraja` int(11) NOT NULL DEFAULT '0',
  `viitemaksujen_oikaisut` char(1) NOT NULL DEFAULT '',
  `pdf_ruudulle_kieli` varchar(1) NOT NULL DEFAULT '',
  `laskun_kanavointitiedon_syotto` tinyint(4) NOT NULL DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yhtion_toimipaikat`
--

DROP TABLE IF EXISTS `yhtion_toimipaikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yhtion_toimipaikat` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `ovtlisa` varchar(16) NOT NULL DEFAULT '',
  `vat_numero` varchar(50) NOT NULL DEFAULT '',
  `nimitys` varchar(100) NOT NULL DEFAULT '',
  `varastotoimipaikka` varchar(1) NOT NULL DEFAULT '',
  `kotipaikka` varchar(25) NOT NULL DEFAULT '',
  `nimi` varchar(60) NOT NULL DEFAULT '',
  `osoite` varchar(30) NOT NULL DEFAULT '',
  `postino` varchar(15) NOT NULL DEFAULT '',
  `postitp` varchar(30) NOT NULL DEFAULT '',
  `maa` char(2) NOT NULL DEFAULT '',
  `fax` varchar(25) NOT NULL DEFAULT '',
  `puhelin` varchar(25) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `postittaja_email` varchar(200) NOT NULL DEFAULT '',
  `www` varchar(100) NOT NULL DEFAULT '',
  `lasku_logo` varchar(100) NOT NULL DEFAULT '',
  `tilino` varchar(6) NOT NULL DEFAULT '',
  `tilino_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_ei_eu` varchar(6) NOT NULL DEFAULT '',
  `tilino_kaanteinen` varchar(6) NOT NULL DEFAULT '',
  `tilino_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_osto_marginaali` varchar(6) NOT NULL DEFAULT '',
  `tilino_triang` varchar(6) NOT NULL DEFAULT '',
  `toim_alv` varchar(6) NOT NULL DEFAULT '',
  `toim_automaattinen_jtraportti` varchar(3) NOT NULL DEFAULT '',
  `ostotilauksen_kasittely` varchar(150) NOT NULL DEFAULT '',
  `sahkoinen_automaattituloutus` char(1) NOT NULL DEFAULT '',
  `liiketunnus` varchar(100) NOT NULL DEFAULT '',
  `kustp` int(11) NOT NULL DEFAULT '0',
  `kohde` int(11) NOT NULL DEFAULT '0',
  `projekti` int(11) NOT NULL DEFAULT '0',
  `pikahakuarvo` int(11) DEFAULT '0',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yhtion_toimipaikat_parametrit`
--

DROP TABLE IF EXISTS `yhtion_toimipaikat_parametrit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yhtion_toimipaikat_parametrit` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `toimipaikka` int(11) NOT NULL DEFAULT '0',
  `parametri` varchar(150) NOT NULL DEFAULT '',
  `arvo` text,
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  KEY `yhtio_index` (`yhtio`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yriti`
--

DROP TABLE IF EXISTS `yriti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yriti` (
  `yhtio` varchar(5) NOT NULL DEFAULT '',
  `kaytossa` char(1) NOT NULL DEFAULT '',
  `nimi` varchar(40) NOT NULL DEFAULT '',
  `tilino` varchar(35) NOT NULL DEFAULT '',
  `iban` varchar(34) NOT NULL DEFAULT '',
  `bic` varchar(11) NOT NULL DEFAULT '',
  `valkoodi` char(3) NOT NULL DEFAULT '',
  `factoring` char(1) NOT NULL DEFAULT '',
  `asiakastunnus` varchar(15) NOT NULL DEFAULT '',
  `maksulimitti` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tilinylitys` char(1) NOT NULL DEFAULT '',
  `hyvak` varchar(50) NOT NULL DEFAULT '',
  `oletus_kulutili` varchar(6) NOT NULL DEFAULT '',
  `oletus_kustp` int(11) NOT NULL DEFAULT '0',
  `oletus_kohde` int(11) NOT NULL DEFAULT '0',
  `oletus_projekti` int(11) NOT NULL DEFAULT '0',
  `oletus_rahatili` varchar(6) NOT NULL DEFAULT '',
  `oletus_selvittelytili` varchar(6) NOT NULL DEFAULT '',
  `laatija` varchar(50) NOT NULL DEFAULT '',
  `luontiaika` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muutospvm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `muuttaja` varchar(50) NOT NULL DEFAULT '',
  `tunnus` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tunnus`),
  UNIQUE KEY `yhtio_tilino` (`yhtio`,`tilino`),
  KEY `index_yriti_on_yhtio_and_iban` (`yhtio`,`iban`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-03-15 10:00:02
-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: referenssi
-- ------------------------------------------------------
-- Server version	5.1.73-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger sarjanumeroseuranta_insert_trigger before insert on sarjanumeroseuranta for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger sarjanumeroseuranta_update_trigger before update on sarjanumeroseuranta for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tapahtuma_insert_trigger before insert on tapahtuma for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tapahtuma_update_trigger before update on tapahtuma for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tilausrivi_insert_trigger before insert on tilausrivi for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tilausrivi_update_trigger before update on tilausrivi for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tuotepaikat_insert_trigger before insert on tuotepaikat for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, "0"), lpad(upper(varastopaikat.alkuhyllynro), 5, "0")) <= concat(rpad(upper(new.hyllyalue), 5, "0"), lpad(upper(new.hyllynro), 5, "0")) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, "0"), lpad(upper(varastopaikat.loppuhyllynro), 5, "0")) >= concat(rpad(upper(new.hyllyalue), 5, "0"), lpad(upper(new.hyllynro), 5, "0"))), new.hyllypaikka = concat(new.hyllyalue, new.hyllynro, new.hyllyvali, new.hyllytaso) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger tuotepaikat_update_trigger before update on tuotepaikat for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, "0"), lpad(upper(varastopaikat.alkuhyllynro), 5, "0")) <= concat(rpad(upper(new.hyllyalue), 5, "0"), lpad(upper(new.hyllynro), 5, "0")) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, "0"), lpad(upper(varastopaikat.loppuhyllynro), 5, "0")) >= concat(rpad(upper(new.hyllyalue), 5, "0"), lpad(upper(new.hyllynro), 5, "0"))), new.hyllypaikka = concat(new.hyllyalue, new.hyllynro, new.hyllyvali, new.hyllytaso) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Dumping routines for database 'referenssi'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-03-15 10:00:02
