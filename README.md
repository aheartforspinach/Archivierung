# Archivierung
Das Plugin erlaubt die leichtere Archivierung von Themen, indem es einen Button hinter den Thementitel anzeigt. Mit einem Klick auf diesen kann man das Thema - ohne die Nutzung des MyBB eigenen Verschiebungstool - in das Standardarchiv verschieben. Das Standardarchiv kann für jede Kategorie einzeln im AdimCP bestimmt werden. Falls man einen Inplaybereich hat, ist es möglich Themen sofort richtig einsortieren zu lassen. 

## Update
Dieser Branch unterstützt den Inplaytracker 3.0 von Jule und den Inplaytracker von Risuena. Falls ihr Jules Tracker in der Version 2.0 verwendet, müsst ihr [diesen Code](https://github.com/aheartforspinach/Archivierung/tree/version1) herunterladen

__Änderungen zu Version 1.0__
* Unterstützung des Inplaytracker 3.0
* Verschiebung der Templates vom globalen in den stylespezifischen
* Unterstützung von englischen/französischen etc. Inplayarchivnamen (s. Anmerkung Inplayarchiv)

Wenn ihr das Archivierungsplugin 1.0 verwendet, ladet den Quellcode herunter und bei euch wieder hoch __ohne__ das Plugin zu deaktivieren oder zu deinstallieren. Anschließend müsst ihr im AdminCP unter Tools&Verwaltung im Tab "Archivierung" auf Update drücken

## Funktionen
__allgemeine Funktionen__
* Verschiebung von Themen in den vorbestimmten Archivbereich (User können nur eigene verschieben)
* Bestätigungsseite fragt nach, ob dieses Archiv das richtige ist und verschiebt erst nach Zustimmung

falls Inplaybereich:
* jeder Szenenteilnehmer kann die Inplayszene archivieren

__Funktionen für Admins__
* Festlegung der Archivbereiches über das AdminCP

## Voraussetzungen
* FontAwesome muss eingebunden sein, andernfalls muss man die Icons in dem Template _archivingButton_ ersetzen
* [Enhanced Account Switcher](http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2) muss installiert sein 
* [Inplaytracker 3.0](https://github.com/ItsSparksFly/mybb-inplaytracker/) von Jule oder [Scene tracker](https://github.com/katjalennartz/scenetracker/) von Katja muss installiert sein

## Template-Änderungen
__Neue Templates:__
* archiving_button
* archiving_buttonThread
* archiving_submitSite

__Veränderte Templates:__
* forumdisplay_thread (wird um die Variable $archivingButton erweitert)

## Anmerkung Inplayarchiv
Seit der Version 2.0 ist es möglich den Namen der Kategorie selbst zu bestimmen. Nach wie vor muss das Inplayarchiv über Unterforen von jedem Monat verfügen. Standardmäßig ist es eingestellt, dass das Plugin nach "Monatsname YYYY" sucht (deutsche Schreibweise).

__Änderung der Sprache__
Um die Sprache zu ändern, sucht nach `$months` und verändert die Monatsnamen in die gewünschte Sprache.

## Vorschaubilder
__Einstellungen in der Foren-Editieren-Seite__
![Archivierung Einstellungen](https://aheartforspinach.de/upload/plugins/archiving_settings.png)

__Ansicht im Forum__
![Archivierung Forum](https://aheartforspinach.de/upload/plugins/archiving_forum.png)
