
# TrackPortal

Piattaforma web per la prenotazione di sessioni in pista, sviluppata per
Apache con PHP e MySQL/MariaDB (stack XAMPP).

**Stack:** PHP 8.2+, MySQL/MariaDB, **Doctrine ORM 3 / DBAL 4**, **Smarty 5**,
Bulma CSS, JavaScript vanilla. PDF con **Dompdf**, email con **PHPMailer**,
cache con **Symfony Cache**.

## Requisiti

- PHP >= 8.2
- Composer
- Apache con `mod_rewrite` (XAMPP)
- MySQL/MariaDB

## Installazione

1. Clonare il progetto in `htdocs` così che risponda su `http://localhost/TrackPortal/`
   (il `RewriteBase` in `.htaccess` e le rotte `ErrorDocument` assumono questo percorso).
2. Installare le dipendenze:

   ```bash
   composer install
   ```

3. Importare lo schema del database: `trackportal.sql` crea **solo** il database
   `trackportal` vuoto (nessun dato).
4. Creare il file di configurazione **fuori dalla document root** in
   `C:/xampp/Trackportal-config/config.php` (vedi sotto).
5. Popolare il database con i dati di prova visitando
   `http://localhost/TrackPortal/seed.php` (oppure da CLI: `php seed.php`).
   Lo script è idempotente: se trova account esistenti salta l'inserimento.
6. Aprire `http://localhost/TrackPortal/`.

### Configurazione

`index.php` e `seed.php` caricano `C:/xampp/Trackportal-config/config.php`,
che deve definire le costanti usate dall'applicazione:

| Gruppo    | Costanti                                                                                                          |
|-----------|-------------------------------------------------------------------------------------------------------------------|
| App       | `APP_NAME`, `APP_URL`, `APP_BASE_URL`, `APP_DEBUG`, `APP_TIMEZONE`                                                 |
| Database  | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`                                                |
| Email     | `MAIL_ENABLED`, `MAIL_TRANSPORT`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |



## Architettura

TrackPortal segue il pattern **MVC + Foundation**:

| Prefisso | Strato     | Cartella      | Responsabilità                                                                 |
|----------|------------|---------------|--------------------------------------------------------------------------------|
| `C`      | Control    | `Control/`    | Logica applicativa, orchestrazione Entity/Foundation/View                      |
| `E`      | Entity     | `Entity/`     | Oggetti di dominio mappati con attributi Doctrine ORM                          |
| `F`      | Foundation | `Foundation/` | Persistenza (ORM, repository, SQL nativo) e servizi (mail, PDF, notifiche)     |
| `V`      | View       | `View/`       | Assegnazione variabili e rendering template Smarty in `public/templates/`      |

**Routing:** `index.php` è l'unico entry point; `.htaccess` inoltra ogni
richiesta non statica a `CFrontController`, che ricostruisce controller e
azione dai segmenti dell'URL (es. `/prenotazione/...` →
`CPrenotazioneSessione`). Il ramo `/api/*` risponde in JSON
(es. `/api/valute` per i tassi di cambio).

**Persistenza:** CRUD e transazioni tramite `FPersistentManager` (Doctrine ORM).
Query SQL complesse o report su `FDataBase` (DBAL). Bootstrap ORM in
`FEntityManager`; sessione PHP incapsulata in `Foundation/Session.php`.

**Presentazione:** template Smarty (`.tpl`) con plugin registrati in
`support/smarty.php` che espongono gli helper procedurali (`url`, `money`,
`csrf_field`, …) definiti in `support/helpers.php`. Escaping HTML automatico
(`escape_html = true`).

## Struttura del progetto

```
Control/          Controller applicativi (auth, prenotazioni, fatture, sanzioni, ...)
Entity/           Entità di dominio (EAccount, EPilota, ECircuito, ESessione, ...)
Foundation/       Persistenza e servizi (FPersistentManager, FDataBase, FMailer, FFatturaPdf, ...)
View/             View che preparano e renderizzano i template
public/
  css/, js/, img/ Asset statici (Bulma + style.css, main.js)
  templates/      Template Smarty organizzati per area (prenotazioni, fatture, ...)
support/
  helpers.php     Funzioni procedurali (URL, formattazione, CSRF, validazioni carta/CF)
  smarty.php      Bootstrap del motore Smarty e registrazione plugin
uploads/          File caricati (circuiti/ pubblici, piloti/ riservati)
var/              Cache runtime (Doctrine, compile/cache Smarty) - non versionata
index.php         Entry point (autoload, config, sessione, front controller)
seed.php          Popolamento del database di prova
trackportal.sql   Schema del database (solo struttura)
```

## Ruoli

Quattro ruoli con dashboard e funzionalità dedicate:

- **Pilota** — ricerca circuiti, prenota sessioni, gestisce documenti
  (licenza, certificato medico), carte di credito e storico prenotazioni.
- **Gestore circuiti** — aggiunge e gestisce circuiti, calendario/schedule
  delle sessioni, promozioni, fatturazione, sanzioni verso i piloti.
- **Gestore noleggio** — gestisce la flotta di veicoli a noleggio e le
  relative sanzioni.
- **Amministratore** — approva le affiliazioni dei gestori, configura
  assicurazione/commissione/IVA, sospende o banna gli account.

## Note di sicurezza

- La configurazione con le credenziali vive fuori dalla document root.
- `.htaccess` blocca l'esecuzione di PHP in `uploads/`, nega l'accesso diretto
  a `uploads/piloti/` (servita solo dal front controller previa verifica dei
  permessi), a `var/` e ai file `trackportal.sql` / `composer.*`.
- Header di sicurezza (CSP, `X-Frame-Options`, `nosniff`, ...) impostati via
  Apache; token CSRF sui form; password con hash bcrypt.