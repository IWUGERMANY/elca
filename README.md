# Installationsanleitung eLCA
## CD Inhalt
Das Archiv oder die CD enthalten folgende Daten:

```
/etc (Konfigurationsdateien)
/app (Programmdateien)
/docs (Dokumentationsdateien)
/www (Dateien der DocumentRoot)
/scripts (Skriptdateien)
/db (Datenbank Dump)
INFO.txt (Verweis auf diese Datei unter /docs) COPYING (Lizenzbedingungen
```

## Systemvoraussetzungen Systemumgebung und Software-Abhängigkeiten
Für den Betrieb der datenbank-gestützten Online-Anwendung eLCA werden die folgenden grundsätzlichen Software-Pakete vorausgesetzt.

- Skriptsprache: PHP 7
- Paketverwaltung Composer
- Datenbank: PostgreSQL 9.4 oder größer
- Der Betrieb wurde unter Debian/Linux sowie unter Mac OS X getestet.

### Detaillierte Voraussetzungen

* Composer
  * Bezugsquelle: https://getcomposer.org/
* PHP7
  * Bezugsquelle: http://www.php.net/
  * Folgende Extensions müssen aktiviert sein
    * DOM
    * gd
    * hash
    * iconv
    * imagick 
    * json
    * libxml
    * mbstring 
    * pcre
    * PDO
    * pdo_pgsql 
    * pgsql
    * session
    * SPL
    * xml
    * xmlreader
    * xmlwriter

* Datenbank PostgreSQL Version 9.4 oder höher
  * Bezugsquelle: http://www.postgresql.org/

## Installation
### Kopieren der Programmdateien
Die Dateien und Verzeichnisse des Archivs bzw. der CD müssen in ein Basisverzeichnis der Anwendung (z.B. ~/src/elca) kopiert werden.

### Abhängige Pakete installieren

    # cd ~/src/elca
    # composer install

### Einrichten der Datenbank
Die Datenbank muss zunächst mit UTF-8 Encoding erstellt werden:

    # createdb –-encoding=UTF8 elca

Anschließend kann der Datenbank-Dump eingespielt werden:

    # pg_restore -Fc -d elca db/elca-db-init.sqlc

### Konfigurationsdatei anpassen
In der Konfigurationsdatei etc/config.local.ini muss der DSN auf die Datenbank angepasst werden:

    ;; example local config. overwrite parameters from the default namespace
    [localhost : default]
    ;; database settings
    db.handles = default
    db.default.dsn = "pgsql:host=localhost port=5432 dbname=elca user=elca password="

Im DSN String (db.default.dsn) müssen host, port, dbname sowie die Zugangsdaten des Datenbankusers user und password eingetragen werden.

### Server starten

    # cd ~/src/elca
    # php -S localhost:7000 -t ./www

Im Browser kann die Anwendung nun über die URL http://localhost:7000/ erreicht werden.

## Initiale Zugangsdaten
Im mitgelieferten Datenbank-Dump ist ein Nutzer mit Administrationsrechten und den folgenden Zugangsdaten angelegt.

    Benutzername: admin_bbsr
    Passwort: changeme
    
    
