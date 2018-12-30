# 1.6.3 
+ Ergänzt mehrsprachige Baustoffnamen
+ Ergänzt eine spanische Sprachversion
+ Ergänzt die Datenschutz-Seite
+ Ergänzt eine Auswertung nicht-bilanzierter Komponenten
+ Optimiert die Suche nach Baustoffkonfigurationen
+ Setzt Vorgabewerte für die Gebäude-Nutzungsdauer in Abhängigkeit des gewählten Benchmarksystems
+ Setzt im Bereich Endenergiebilanz passende Baustoff-Kategorien als Standardauswahl
+ Behebt Fehler beim Material-Datensatz-Mapping für den Projektimport-Assistenten
+ Behebt einen Fehler beim Kopieren von Bauteilen, die mit dem Fenster-Assistenten erstellt wurden

# 1.6.2
+ Behebt einen Fehler im Bauteileditor bei der Eingabe des Austauschzyklus
+ Behebt einen Fehler bei der Ermittlung kompatibler Baustoffdatenbanken von Bauteilvorlagen
+ Setzt im Baustoff-Auswahldialog den EPD-Subtyp auf "Alle" als Standardwert 

# 1.6.1
+ Ersetzt beim Import von Projekten einen nicht mehr vorhandenen Baustoff durch den Unbekannten Baustoff
+ Stellt die Kompatibilität zu älteren Versionen von Bauteilassistenten wieder her
+ Überschreibt die Schichtdicke in einem Bauteil beim Austausch eines Baustoffs, wenn dieser eine Schichtdicke spezifiziert 
+ Behebt einen Fehler in der Berechnung, wenn ein ÖKOBAUDAT Datensatz keine Werte liefert
+ Behebt einen Fehler bei der Baustoffauswahl, wenn kein Baustoff bzw. keine Kategorie ausgewählt wurde
+ Behebt einen Fehler im Bauteilvorlagen-Dialog im Zusammenhang mit öffentlichen und privaten Vorlagen
+ Behebt einen Fehler beim Speichern der Projekt-Stammdaten, wenn keine aktive Projektvariante ausgewählt wurde

# 1.6.0 (0.9.5)
+ Ergänzt die Suche nach dem EPD Subtyp im Auswahldialog
+ Ergänzt Kompatibilität von Bauteilvorlagen mit der ÖKOBAUDAT
+ Optimiert die Baustoffkonfigurationsprüfungen
+ Ergänzt eine Verwaltung für das Materialmapping
+ Ergänzt die übergreifende Suche in Baustoffkonfigurationen
+ Ergänzt die Aggregation der Module A1, A2 und A3 in das Modul A1-3
+ Verbessert den Ökobaudat-Import
+ Ergänzt den Import der Konvertierungsfaktoren "Umrechnung nach Masse"
+ Ergänzt den Import der geographischen Repräsentation von Baustoffen 
+ Ergänzt ein Feld "Schichtdicke" in Baustoffkonfigurationen
+ Ergänzt ein neues Projekt-Export-XML-Schema (Version 1.3)
+ Aktualisiert die Rechenhilfe Trinkwasser
+ Ergänzt die Funktion "Ersetzen von Bauteilkomponenten"
+ Ergänzt Referenz-Bauteilvorlagen
+ Verbessert die Aggregation für Referenzprojekte

# 1.5.4
+ Ergänzt und optimiert BNB Benchmarks für neue ÖKOBAUDAT-Versionen
+ Nachkommastellen im EnEV2eLCA-Import für Schicht-Parameter werden entsprechend Vorgaben gerundet
+ Lange Baustoffnamen im EnEV2eLCA-Import werden gekürzt dargestellt
+ Behebt einen Fehler beim Kopieren von Projekten im Zusammenhang mit LCC Kosten für Bauteile
+ Behebt einen Schreibfehler im Bereich Endenergiebilanz
+ Behebt einen Fehler im Treppenassistent beim Übernehmen der berechneten Laufplatten-Länge
+ Entfernt den Navigationspunkt Transporte im Bereich Auswertungen

# 1.5.3
+ Ergänzt den EnEV2eLCA-Projektimport (aktuell BKI Projektimport)
+ Ergänzt den Stützenassistenten
+ Behebt einen Fehler im Zusammenhang mit dem Ergebnis-Zwischenspeicher
+ Behebt einen Fehler beim Download eines PDFs für HTTPS

# 1.5.2
+ Ergänzt in Bauteilkomponenten eine automatische Korrektur der Bezugsgröße, wenn die Bezugsgröße auf m2 eingestellt ist und die Fläche von 1 m2 abweicht
+ Ergänzt eine Versionsinformation für den Trinkwasser Navigationspunkt
+ Ergänzt im Bauteileditor eine Funktion zur Berechnung der opaken Fläche, wenn Abzugsflächen eingesetzt werden
+ Ergänzt eine Warnung für Projekte, die eine nicht-normkonforme Berechnungsgrundlage verwenden
+ Optimiert die Auswertung "Bauteil-Ranking"
+ Optimiert die Performanz der Anwendung 
+ Behebt einen Fehler in der die Neuberechnung eines Projektes nicht ausgeführt wurde, falls das detaillierte LCC Verfahren eingesetzt wurde
+ Behebt einen Fehler in der Auswertung "Ranking Baustoffe", in der Baustoffe mehrfach angezeigt wurden
+ Behebt einen Fehler im Bereich Transporte, in dem die Gesamtbilanz nicht mehr angezeigt wurde
+ Behebt einen Fehler in Projekt-Exporten, in der die Endenergiebereitstellung nicht enthalten war 
+ Behebt einen Fehler beim Kopieren von Projekten, der Endenergiebereitstellungen nicht kopierte
+ Behebt einen Fehler beim Kopieren von Bauteilvorlagen

# 1.5.1
+ Ergänzt Projektfreigaben (geteilte Projekte für mehrere Benutzer)
+ Ergänzt die Angabe der Dicke in der Funktion Suchen & Ersetzen
+ Ergänzt eine Bereinigungsfunktion nach Umstellung der Baustoffdatenbank
+ Ergänzt das detaillierte Verfahren für LCC Berechnungen am Bauteil
+ Ergänzt den Assistenten für Treppen
+ Ergänzt die ID der Baustoffkonfiguration im CSV Export
+ Verbessert die Fehlermeldung für fehlende Umrechnungsfaktoren
+ Verbessert die Darstellung im Masse Ranking
+ Vereinfacht die Zuordnung von Entsorgungsprozessen (Admin-Funktion) 
+ Behebt einen Fehler in den Charts im Variantenvergleich für neue Baustoffdatenbanken
+ Behebt einen Fehler in der Import-Funktion für Projekte und Bauteile, wenn mehrere Gefache importiert wurden
+ Behebt einen Fehler beim Kopieren von Projekten

# 1.5.0 (0.9.4)
+ Ergänzt die Konfiguration des Berechnungsmodells auf Basis von EPD Modulen
+ Ergänzt die Suchen- und Ersetzenfunktion von Baustoffen
+ Ergänzt den Assistenten für Fenster-Bauteile
+ Ergänzt die Auswertung nach EPD Subtypen
+ Ergänzt den Passwortschutz für Projekte
+ Ergänzt die englische Sprachversion
+ Ergänzt ein SSL Zertifikat und verwendet ausschließlich HTTPS 
+ Verbessert den Import neuer Datensätze aus der ÖKOBAUDAT
+ Behebt einen Fehler bei der Generierung von PDF Auswertungen
+ Behebt Fehler beim Import und Export von Projekten
+ Behebt einen Fehler bei der Angabe von Gründen für abweichende Nutzungsdauern
+ Behebt einen Fehler beim Verschieben von Bauteilkomponenten
+ Behebt einen Fehler in den Verweisen auf Datensatz-Datenblättern
+ Behebt einen Fehler in Freitextsuchfeldern

# 1.4.0 (0.9.3)
+ Benutzeraccounts benötigen ab sofort eine E-Mailadresse.
+ Benutzer können Ihr Passwort über den Link "Passwort vergessen" wiederherstellen
+ Ergänzt die Möglichkeit Auswertungen als PDF zu speichern
+ Ergänzt eine Vorschau-Grafik auf Bauteile in den Bauteillisten
+ Hebt Meldungen farblich hervor
+ Erzwingt die Angabe einer Begründung bei abweichender Nutzungsdauer eines Materials an Bauteilkomponenten
+ Erweitert die Auswertungen um eine Liste von Bauteilen mit abweichender Nutzungsdauer
+ Beschränkt die Anzahl von Projekten für einen Benutzer
+ Ermöglicht die Bilanzierung von Laborgebäuden
+ Ergänzt die Rolle Organisation
+ Ergänzt die Fähigkeit Texte mehrsprachig darzustellen (aktuell nur deutsche Sprache verfügbar)
+ Behebt einen Fehler in der Darstellung von Ergebnissen in der Trinkwasser-Rechenhilfe

# 1.3.6
+ Behebt einen Fehler, der beim Kopieren eines Projekts dazu geführt hat, dass die aktuellste Projektphase
  in der Kopie nicht korrekt gesetzt wurde.

# 1.3.5
+ Behebt einen Fehler bei der Berechnung der relativen Abweichung im Variantenvergleich.
  Für den Fall, dass die Referenz-Variante A negative Werte aufwieß, wurde das Vorzeichen der Abweichung
  nicht korrekt ermittelt. Die Formel wurde auf `Rel.Abweichung = (B - A) / |A|` geändert.

# 1.3.4
+ Behebt einen Fehler beim Löschen von Bauteilkomponenten

# 1.3.3
+ Behebt einen Fehler in der Verwaltung von Benchmark-Versionsdatensätzen

# 1.3.2
+ Ergänzt eine Prüfung für eine Datenbank-konsistente Baustoffauswahl in Bauteilen

# 1.3.1
+ Ergänzt das eLCA und BBSR Logo
+ Behebt einen Fehler bei der Berechnung von Abzugsflächen nach Löschen einer nicht-opaken Bauteilkomponente

# 1.3.0 (0.9.2 beta)
+ Ergänzt das Referenzgebäudeverfahren für die Berechnung von Benchmarks nach BNB
+ Ergänzt einen Link zum Handbuch in der Meta-Navigation
+ Behebt einen Fehler bei der Berechnung von Benchmarks für Extremwerte
+ Behebt einen Fehler in der Darstellung von Transporten / Verkehrsmittel
+ Behebt einen Fehler in der Darstellung des Variantenvergleichs für Bauteilgruppen
+ Behebt einen Fehler bei der Berechnung und Speicherung von Ergebniswerten Cache
+ Behebt einen Fehler beim Export von Ergebnissen im CSV Format

# 1.2.19
+ Behebt einen Fehler in der Baustoffauswahl. Es können nur noch Baustoffe ausgewählt werden, die einer freigegebenen Baustoffdatenbank zugeordnet sind.

# 1.2.18
+ Der Bereich "Export" unter Projektdaten wurde umstrukturiert und um die Möglichkeit eines CSV-Exports erweitert.
+ Da es vorkommem kann, dass die NGF EnEV größer als die NGF ist, wurde die Prüfung im Bereich der Endenergiebilanz entfernt.
+ Die Rolle "Beta-Tester" wurde in Forscher umbenannt.
+ Schreibfehler auf der Impressumsseite wurden behoben.

# 1.2.17
+ Behebt ein Problem bei der Berechnung der Instandhaltung von Bestandskomponenten.

# 1.2.16
+ Behebt einen weiteren Fehler bei der Berechnung von Abzugsflächen in Bauteilen
+ Diese Versionshistorie ist nun in eLCA abrufbar

# 1.2.15
+ Behebt einen Fehler bei der Berechnung von Abzugsflächen in Bauteilen
+ Behebt einen Fehler beim Exportieren von Baustoffkonfigurationen
+ BNB 4.1.4 Auswertungen sind nun für alle Anwender verfügbar

# 1.2.14
+ Berücksichtigung regenerativer Energien (vorerst nur für eingeschränkten Anwenderkreis)
+ Anzeige von Einheiten in den Spaltenüberschriften im Bereich Endenergiebilanz
+ Auswertungen um Recyclingpotential erweitert (nur für EN 15804 kompatible Baustoffdatenbanken)
+ Vermeidung eines Umbruch von negativen Zahlen mit mehreren Nachkommastellen im Internet Explorer
+ Neue API-URL für den Baustoff-Import über Soda4LCA

# 1.2.13
+ Beim Erstellen einer neuen Phase wird die erste Variante nach der Phase benannt

# 1.2.12
+ Korrektur der Darstellung der Gesamtsumme beim Löschen eines Transports
+ Synchronisation von Mengenangaben bei Bauteilen und Bauteilkomponenten mit Bezugsgrößen m und Stück
+ Optimierungen beim Import von Baustoffdatenbanken über Soda4LCA (Admins)
+ CSV Export von Baustoffkonfigurationen für EN15804-kompatible Baustoffdatenbanken
+ Austausch des numerischen Eingabefelds für bessere Eingabe und Beschränkung auf eine feste Anzahl an Nachkommastellen
+ Korrektur der Filterfunktion für öffentliche und private Bauteilvorlagen

# 1.2.11
+ Optimierungen beim Import von Baustoffdatenbanken über Soda4LCA (Admins)
+ Downloadmöglichkeit der NOTES-Datei für Administratoren (Admins)
+ Prozesse des Moduls D lassen sich nun wie EOL-Prozesse mit einem prozentualem Anteil konfigurieren (Admins)

# 1.2.10
+ Korrektur von Seitenumbrüchen in der Druckdarstellung von Auswertungen

# 1.2.9
+ Anpassung der Konfiguration für den Import von Baustoffdatenbanken über Soda4LCA
+ Ergänzung der Baustoff-Kategorien "5.08 Reaktionsharze auf Methacrylatbasis" und "7.06 Türen und Tore"

# 1.2.8
+ Optimierung der Darstellung von Lebenszyklen in den Auswertungsdiagrammen für EN 15804 Baustoffe

# 1.2.7
+ Für Bestandskomponenten können nun Restlaufzeiten spezifiziert werden
+ Neue Auswertungen für den Variantenvergleich
+ Transportrechner
+ Vertikale Balkendiagramme zeigen nun einen Überlauf an
+ Restrukturierung des Ergebniscaches

# 1.2.6
+ Optimierung bei der Numerierung von Bauteillkomponenten in einem Bauteil nach dem Hinzufügen oder Löschen einer Komponente
+ Korrektur des Verhaltens beim Betätigen des Buttons "Abbrechen" im Kontext von Bauteilen

# 1.2.5
+ Korrektur in der Berechnung von 4.1.4 Auswertungen
+ Berechnungsmethode "nach Masse" ist Standard für 4.1.4 Auswertungen

# 1.2.4
+ Korrektur des CSV Exports für Baustoffkonfigurationen

# 1.2.3
+ Optimierungen in der Darstellung im Kontext von 4.1.4 Auswertungen

# 1.2.2
+ Optimierungen in der Darstellung der Navigation

# 1.2.1
+ BNB 4.1.4 Auswertungen

# 1.2.0 (0.9.1 beta)
+ Verwaltung und Zuordnungen von Schraffuren für die Bauteildarstellung
+ eBNB Export - Export von Projekt-Ergebnissen
+ Verwaltung und Bearbeitung von Benchmarksystemen und -versionen
+ Detailergebnisse für Baustoffe in den Auswertungen
+ Ergänzung des PE Gesamt Wirkindikator für alte Baustoffdaten
+ Neuer Bereich für Qualitätssicherung von Baustoffkonfigurationen
+ Bereich für Neuigkeiten auf der Willkommensseite
+ Neue Rolle "Beta-Anwender"

# 1.1 (0.9 beta)
+ Anbindung an Soda4LCA für den Import von EN 15804-kompatiblen Baustoffdatenbanken
+ Unterstützung für Baustoff-Szenarien
+ Unterstützung für Baustoff-Varianten

# 1.0 (0.8 beta)
+ erste eLCA beta Version
