# HmIP-WRC6-230  

[![Image](../imgs/logo-homematic-ip.png)](https://homematic-ip.com/de)  

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Instanzkonfiguration

__Konfigurationsseite__:

| Name             | Beschreibung                                                                           |
|------------------|----------------------------------------------------------------------------------------|
| Info             | Modulinformationen                                                                     |
| Schaltaktor      | Schaltaktor - Gerätekanal 9                                                            |
| Staus LED        | Status LED - Gerätekanäle 12 - 17                                                      |
| Alle Status LEDs | Alle Status LEDs - Gerätekanal 18                                                      |
|                  | Hinweis:                                                                               |
|                  | Es wird empfohlen entweder `Alle Status LEDs (Gerätekanal 18) zu verwenden oder        |
|                  | die einzelnen Status LEDs (Gerätekanäle 12 - 17).                                      |
| Aktualisierung   | Prüft und aktualisert die Werte des Schaltaktor und der Status LEDs                    |
| Ablaufsteuerung  | Ablaufsteuerung                                                                        |
| Deaktivierung    | Hinweis zur Deaktivierung:                                                             |
|                  | Bei einer Deaktivierung wird der konfigurierte Schaltvorgang für den Schaltaktor,      |
|                  | sowie die konfigurierte Helligkeit für die Status LEDs verwendet.                      |
|                  | Hinweis zu (Re-)Aktivierung:                                                           |
|                  | Sofern Auslöser verwendet werden, so werden die Auslöser auf Ihre Bedingungen geprüft, |
|                  | andernfalls werden die konfigurierten Werte verwendet.                                 |
| Visualisierung   | Visualisierung                                                                         |

### Systemstart

Unter `Aktualisierung` in der Instanzkonfiguration kann festgelegt werden,  
ob beim Systemstart eine Aktualiserung erzwungen werden soll oder  
nur bei unterschiedlichen Werten Funkbefehle an das Gerät ausgeführt werden sollen.

### Änderung der Konfiguration

Bei Änderung der Konfiguration werden nur bei abweichenden Werten Funkbefehle an das Gerät übermittelt.

### Automatische Aktualisierung im Systembetrieb

Wird eine automatische Aktualisierung im Systembetrieb aktiviert, kann konfiguriert werden,  
ob eine Aktualisierung erzwungen werden soll oder  
ob nur bei unterschiedlichen Werten Funkbefehle an das Gerät ausgeführt werden sollen.