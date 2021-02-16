# Pupesoft 7
Suomalainen, avoimen lähdekoodin toiminnanohjausjärjestelmä PK-yrityksille. PHP 8 (ja 7.4) -yhteensopiva fork. UTF-8 kytketty käyttöön myös tietokannoille ja monia toimintoja muutettu sujuvammiksi. Virallinen asennusohje löytyy GitHubin [Wikistä](https://github.com/devlab-oy/pupesoft/wiki/Asennusohje "Pupesoft - Asennusohje"). Virallinen repository: https://github.com/devlab-oy/pupesoft/

Seuraava pika-asennusohje on tarkoitettu lähinnä muistilistaksi käyttäjille, joille LAMP-ympäristö on jo entuudestaan tuttu.


# Pika-asennusohje (minimaalinen asennus ilman Ruby on Rails/Pupenext-ympäristöä)
1. Asenna Apache, MySQL/MariaDB (server), PHP, PHP-Apache, PHP-GD ja PHP-MySQL. Esimerkiksi Debian-pohjaisissa jakeluissa "apt install a2ps apache2 libapache2-mod-php mariadb-server php-curl php-gd php-mbstring php-mcrypt php-mysql recode". Arch-pohjaisissa jakeluissa "pacman -Syu a2ps apache mariadb mcrypt php php-apache php-gd recode", AUR-pakettivarastoista paketti libiconv (curl ja mbstring löytyvät jo valmiiksi PHP-paketista) ja PHP-lisäosa "pecl install mcrypt". Pupesoftin muut riippuvuudet on listattu tarkemmin virallisessa asennusohjeessa.

2. Kytke palvelut käyttöön: "systemctl enable --now httpd" (tai apache2) ja "systemctl enable --now mysqld"

3. Aja "mysql_secure_installation", poistaen anonyymit käyttäjät ja oletuskannat. Salli yhteydet vain localhostista. Sitten kytke PHP-moduli Apachen käyttöön ("a2enmod enable php" tai muokkaamalla Apachen asetustiedostoja /etc/httpd- tai /etc/apache2-kansioissa). Muista kytkeä myös ainakin PHP-lisäosat "gd" ja "mysqli" käyttöön, esimerkiksi poistamalla kommenttimerkintä ";" php.ini-tiedostosta rivien "extension=gd" ja "extension=mysqli" alusta. Pankkiyhteyksille myös mcrypt vaaditaan.

4. MySQL:n/MariaDB:n oletusasetukset tavallisesti toimivat, mutta virallisessa asennusohjeessa on tietoa tietokantamoottorin lisäoptimoinnista (myös replikoinnista). Toinen hyvä tietolähde on Arch Linuxin Wiki MariaDB:stä: https://wiki.archlinux.org/index.php/MariaDB

5. Muokkaa inc/salasanat.php-tiedostoon tietokannan käyttäjä ja salasana, tietokantapalvelimen osoitteeksi "localhost".

6. Mikäli kyseessä on uusi asennus, perusta tietokanta ("mysql -uKÄYTTÄJÄ -pSALASANA", sitten "CREATE DATABASE pupesoft CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;" (tai "utf8mb4_unicode_ci" vanhemmalla tietokantamoottorilla). Aja referenssidata ja tietokantarakenne sisään ("mysql -uKÄYTTÄJÄ -pSALASANA pupesoft < alkudata_pupesoft7.sql"). Voit myös ensin muuttaa tietokannoissa käytettävän yritystunnisteen komennolla "sed -i 's/demo/OMATUNNISTE/g' alkudata_pupesoft7.sql".

7. Tietokannan käyttäjien/oikeuksien ja crontab-varmuuskopiointien asettamiseen löytyy ohjeita virallisesta asennusohjeesta.

8. Ainakin seuraavat oletuksista poikkeavat asetukset on hyvä asettaa /etc/php-kansion alta löytyvään php.ini-tiedostoon:

upload_max_filesize = 80M, 
max_execution_time = 3000, 
max_input_vars = 10000, 
memory_limit = 520M, 
mssql.charset = "UTF-8" ([MSSQL] alle)

Vaikka käytössä ei olekaan MS SQL -tietokanta, Pupesoft lukee mssql.charset-asetusta.

9. Kirjaudu Pupesoftiin oletustunnuksilla admin/1234.


# Latin1/ISO-8859-1 -tietokannan UTF-8 -konversio
Mikäli kyseessä on vanha tietokanta, saattaa tietokantarakenteeseen joutua lisäämään puuttuvia sarakkeita tai muuntamaan tyyppejä. Asia menee kuitenkin laajuudessaan tämän geneerisen pikaohjeen ulkopuolelle. Tämä työkalu on todettu hyväksi apuvälineeksi schemojen vertailussa: https://www.infoom.se/compare-mysql-online/

Kytke kaikki PHP:n virheilmoitukset päälle /etc/php-kansion alta löytyvästä php.ini-tiedostosta:

error_reporting = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED, 
display_errors = On, 
display_startup_errors = On, 
log_errors = On

Seuraa /var/log/apache2/error_log- tai /var/log/httpd/error_log-tiedostoa. Tämä auttaa myös ratkomaan ongelmatilanteita, joissa selain näyttää tyhjää valkoista sivua tuomalla virheilmoitukset esiin.

Vanhan latin1/ISO-8859-1 -tietokannan konvertointiin UTF-8 -merkistöön on monta tapaa. Tärkeintä on ensin varmuuskopioida kanta ("mysqldump -uKÄYTTÄJÄ -pSALASANA pupesoft >pupesoft.sql"). Yksi vaihtoehto on ajaa "ALTER/MODIFY"-kyselyt kannalle, tauluille ja sarakkeille, toisaalta joskus parempia tuloksia on saatu ajamalla tietokantadumppiin konversio-ohjelma, kuten "iconv --from-code=ISO-8859-1 --to-code=UTF-8 pupesoft.sql >pupesoft-utf8.sql", minkä lisäksi "sed -i 's/latin1/utf8/g' pupesoft-utf8.sql", sitten dumppi uudestaan sisään kantaan "mysql -uKÄYTTÄJÄ -pSALASANA pupesoft < pupesoft-utf8.sql".

Konversion jälkeen Pupesoftissa kannattaa käydä mahdollisimman monen modulin puolella ja tarkistaa, että "ääkköset" näkyvät oikein. Muussa tapauksessa palautetaan varmuuskopio ja kokeilla toista tapaa. Myös Pupesoftin omaa konversioskriptiä UTF8_mysqlkonversio.php voi testata (UTF8_konversio.php on jo ajettu tähän koodiin). Tietokannan oletusmerkistön voi tarkistaa SQL-kyselyllä:

"SELECT default_character_set_name FROM information_schema.SCHEMATA WHERE schema_name = 'pupesoft';"

