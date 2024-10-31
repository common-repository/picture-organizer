=== OVM Picture Organizer ===
Contributors: RudolfFiedler
Donate link: http://www.picture-organizer.com/donate
Tags: pictures, images, image-license, licenses, stock-images, written warning, abmahnung, quellenangaben, Bildquelle, Bildnachweis, Urheberrecht, Bilder, Fotos, fotolia, pixabay,aboutpixel, 123rf,pixelio, Picture-Management,Bildverwaltung, Bildverwaltung online, 
Requires at least: 4.0
Tested up to: 4.4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bildnachweise im Impressum einfach verwalten und anzeigen  - Abmahnungen vermeiden - Never written warnings because of missing picture-credits on your website

== Description ==
= Deutsch =
Bilder auf der Homepage oder dem Blog einzusetzen ist nicht nur einfaches Verwenden, sondern das muss organisiert sein. Einerseits ist es wichtig zu wissen, welchbe Bilder wo eingesetzt werden, und welche überhaupt auf der Webseite eingesetzt werden. Da geht schnell die Übersicht verloren.
Der Picture-Organizer hilft durch den Einsatz von Kategorien und Keywores den Überblick über die verwendeten Bilder zu erhalten, oder auch wieder zu bekommen. Eine einfache Verwaltung,
die WordPress-user von der Struktur her gewohnt sind, einfach zu begreifen sind, und kinderleicht zu bedienen: Kategorien und Schlagworte.
Die fehlende Übersicht über die verwendeten Bilder kann zudem sehr schnell viel Geld kosten. Wenn notwendige Urheberrechtsangaben nicht angezeigt werden,
sind Abmahnkosten von 1300 € und mehr fällig.


= Englisch =


== Installation ==
= Deutsch =

= Systemvoraussetzungen =
* WordPress 4.1 oder größer
* PHP 5.3 oder größer

= Installation =
= Deutsch =
 1. Lade  das Plugin "Picture-Organizer" einfach über die WordPress-Pluginfunktion in Ihre WordPress-Seite (Als Suchbegriff einfach "Picture-Organizer" eingeben.
 2. Aktiviere das Plugin über die Plugin-Übersichtsseite
 3. Füge den Shortcode [ovm_picture-organizer liste] am besten in das Impressum an der Stelle ein, an der die Liste mit den Urheberrechtsdaten gezeigt werden soll.
 4. Das war's auch schon, weitere Einstellungen sind nicht notwendig.
 5. Die Premium-Module können bei Bedarf direkt über die Picture-Organizer-Einstellungen bezogen werden. 
 
 Video-Tutorial Installation, Einrichtung und Handhabung vom Picture-Organizer: http://www.picture-organizer.com/support-2/support/
 
= Englisch =
= Requirements =
* WordPress 4.1 or greater
* PHP 5.3 or greater

= Installation =
1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Put the shortcode [ovm_picture-organizer liste] into your imprint-page, where do you want to place this infos, thats all.
4. That it was, there is nothing else to do. You can start with collection the copyright-infos within the media-center, by editing every image.
5. If you need premium-features, you can easyly oder with the picture-organizer - Premium-Settings

== Frequently Asked Questions ==
= Deutsch = 

= Wo finde ich den Shortcode für die Ausgabe der Urheberrechtsinfos im Impressum? =

Du findest den Shortcode in der Beschreibung des Plugins auf der Plugin-Seite, oder hier,
der Shortcode ist:    [ovm_picture-organizer liste]


= Warum werden manche Bilder/Attachments angezeigt, und andere nicht? =

Es werden nur die Bilder/Attachments angezeigt, die im Feld "Lizenzkey" einen Wert stehen haben, oder das entsprechende Klickfeld aktiviert haben.
Grund ist: Es gibt ja oft Bilder, für die keine Lizenz verfügbar oder notwendig ist (eigene Bilder, Platzhalter o. ä.)

= Englisch = 

= Where I can find the shortcode to place the copyright-infos? =

You will find the shortcode in the plugins-description of your plugins page.

== Screenshots ==

1. Plugin-Beschreibung mit Angabe des Shortcodes für die Anzeige der Daten im Inhalt
2. Attachment-Details bei der Neuanlage eines Bildes
3. Nochmal Attachment-Details - Detailansicht
4. Anzeige der Beispieldaten im Frontend


== Changelog ==

= 2.0.1
* Secure loading premium-file


= 2.0
* Die Anzeige der Credits im Impressum oder auf der Seite wird nur noch über das Klickfeld gesteuert,
* Fehler beim Speichern des Klickfeldes, behoben (entfernen war nicht möglich)
* Erweiterung der Medien-Übersichtsseite (Premium-Version)  
* Gruppierung der Credit-Ausgabe nach dem Rechteinhaber
* Vereinfachter Bezug der Premium-Version
* Affiliate-System: Der erste Affiliate-Vermittler wird innerhalb der WP-Options gesichert
* Umstellung der Premium-Verwaltung auf 1 WP-Option - Speicherung
* Uninstall: Uneingeschränkte Berücksichtigung der Lösch-Option für alle gespeicherten infos in wp-otions und attachment-Metas
* Premium-Version: Optimierung der Kategorien und Schlagwort-Einstellungen
* Deutsche Sprachdatei optimiert  
* wpnonce - im Adminbereich (Einstellungen) integriert

= 1.6.2 =
* Sicherheit der Formulare im Admin-Bereich erhöht


= 1.6.1 =
* Selektion der Credits für die Ausgabe: Ergänzung der Ausgabe, wenn Lizenz-Nr. > ''

= 1.6 =
* Erweiterung Zuordnung von Kategorien und Suchfunktion über Kategorien
* Erweiterung Zuordnung von Tags/Keywords/Schlageworten  (Premium-Version)
* Realisierung eines manuellen Backups (Premium-Version)
* Erweiterung der Sprachdateien (Deutsch/Englisch)
* Einrichtung der Affiliate-Version
* Sortierung und Gruppierung der Urheberrechtsnachweise nach dem RechteInhaber 
* Ausgabe vorgegebener Urheberrechtsnachweise auch direkt am Ende der Seite, auf der die Bilder ausgegeben werden (Premium-Version)
* Umstellung aller php-tags auf <?php, Entfernung von <? : Die Short-Open-Tags wurden nicht von allen Servern unterstützt.

= 1.5.2 =
* Fehler behoben in plugin_init() --unset--


= 1.5.1 =
* Fehlerbehebung: Jetzt werden nur noch die Attachments angezeigt, für die ein Lizenzkey eingegeben wurde


= 1.5 =
* Fehlerbehebung: Anzeige von nur 5 Bildnachweisen auf alle geändert
* Verwaltung der CSS-Angabe für die publizierten Bildnachweise eingefügt
* Anstelle der Lizenznummer wird eine Thumbnailversion des Bildes mit Link auf eine größere Version angezeigt
* Ergänzung dynamische Supportseite mit Links zu weiterführenden Seiten
* Vorbereitung Einbindung Premium-Version

= 1.4.3 =
* Ergänzung optonale Promotion über den Bildnachweise-Shortcode im Frontend
* Umstellung der Kontaktaufnahmen mit dem Server - nur noch für Dashboard+Einstellungsseite
* Umstellung diverser globalen Konstanten auf Klasseneigenschaften
 

= 1.4.1 =
* Removing frontend-calls to plugin-website

= 1.3 = 
* Optimize curl-calls
= 1.0 = 
* Secure formfields - validation

= 0.9 = 
* adding uninstall-file to remove all the data during uninstallation of the plugin

= 0.8 = 
* First Version after local tests only with the most important features

== Upgrade Notice ==

= 1.0 = 
* Überarbeitete Optik der Einfabefelder und der Ausgabe über den Shortcode
