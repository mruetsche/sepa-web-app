# SEPA Überweisungsmanager - WebApp

## Installation

### Systemanforderungen
- PHP 7.4 oder höher
- SQLite3 Support
- Composer (für TCPDF Installation)

### Installation ohne Composer

Falls Sie keinen Composer haben, können Sie TCPDF manuell herunterladen:

1. Download TCPDF von https://github.com/tecnickcom/TCPDF/releases
2. Entpacken Sie TCPDF in den `vendor/tecnickcom/tcpdf/` Ordner
3. Struktur sollte sein: `vendor/tecnickcom/tcpdf/tcpdf.php`

### Installation mit Composer

```bash
composer install
```

### Einrichtung

1. **Upload**: Laden Sie alle Dateien auf Ihren Webserver hoch

2. **Berechtigungen setzen**:
```bash
chmod 755 database/
chmod 644 database/sepa_manager.db  # wird automatisch erstellt
```

3. **Zugriff**: Öffnen Sie die WebApp in Ihrem Browser:
```
http://ihr-server.de/sepa_webapp/
```

### Standard-Login

- **Benutzername**: admin
- **Passwort**: admin123

**WICHTIG**: Ändern Sie das Passwort nach dem ersten Login!

## Verzeichnisstruktur

```
sepa_webapp/
├── ajax/              # AJAX Handler
├── assets/            # CSS und JavaScript
├── config/            # Konfiguration und Datenbank
├── database/          # SQLite Datenbank (automatisch erstellt)
├── includes/          # PHP Include-Dateien
├── pages/             # Seiten-Templates
├── vendor/            # TCPDF Library
├── composer.json      # Composer Dependencies
├── index.php          # Hauptdatei
├── login.php          # Login-Seite
└── logout.php         # Logout-Script
```

## Features

- ✅ **SEPA-Überweisungsformular** nach offiziellem Standard
- ✅ **PDF-Generierung** zum Ausdrucken
- ✅ **Adressbuch** für häufige Empfänger
- ✅ **IBAN-Validierung** mit Modulo-97 Check
- ✅ **BIC-Validierung**
- ✅ **Benutzerverwaltung** mit Login
- ✅ **Responsive Design** (Bootstrap 5)
- ✅ **Single Page Application** mit jQuery
- ✅ **SQLite Datenbank** (keine MySQL erforderlich)

## Sicherheit

1. **HTTPS verwenden**: Nutzen Sie immer HTTPS in Produktion
2. **Passwort ändern**: Ändern Sie das Standard-Passwort
3. **Backup**: Sichern Sie regelmäßig die database/sepa_manager.db

## Troubleshooting

### Fehler: "Database connection failed"
- Prüfen Sie, ob PHP SQLite3 Support hat: `php -m | grep sqlite`
- Stellen Sie sicher, dass der database/ Ordner beschreibbar ist

### Fehler: "TCPDF not found"
- Installieren Sie TCPDF via Composer: `composer require tecnickcom/tcpdf`
- Oder laden Sie TCPDF manuell herunter (siehe oben)

### PDF wird nicht generiert
- Prüfen Sie PHP Memory Limit (min. 128M empfohlen)
- Prüfen Sie PHP Execution Time (min. 30 Sekunden)

## Anpassungen

### Eigenes Logo hinzufügen
Ersetzen Sie in `ajax/generate_pdf.php` den Titel-Bereich mit Ihrem Logo.

### Weitere Felder hinzufügen
Erweitern Sie die Datenbank-Tabelle `transfers` und passen Sie das Formular an.

## Support

Bei Fragen oder Problemen:
- Prüfen Sie die PHP Error Logs
- Aktivieren Sie Debug-Modus in config/config.php
- Stellen Sie sicher, dass alle Requirements erfüllt sind

## Lizenz

Diese Software wird "as is" zur Verfügung gestellt.

## Version

1.0.0 - Erste stabile Version
