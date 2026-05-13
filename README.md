# TrackPortal

Piattaforma web per la prenotazione di sessioni in pista (track day) sviluppata
per essere eseguita su Apache con PHP/MySQL (stack XAMPP).

Stack: **PHP 8 + MySQL/MariaDB + HTML + CSS + JavaScript** (vanilla), **nessuna libreria o template engine esterno** — le view sono PHP nativi inclusi da `VBase::render()`.

## Architettura

TrackPortal segue il pattern accademico **MVC + Foundation** (variante del classico
Model-View-Controller con un livello esplicito di persistenza), modellato sullo
stile di FillSpaceWEB. Le cartelle in minuscolo identificano lo **strato
architetturale**; il prefisso a una lettera del nome classe identifica lo strato
in modo ridondante e abilita un autoloader "per convenzione".

| Prefisso | Strato      | Cartella       | Responsabilita                                                 |
|----------|-------------|----------------|----------------------------------------------------------------|
| `C`      | Controller  | `controller/`  | Logica applicativa, orchestrazione Entity/Foundation/View      |
| `E`      | Entity      | `entity/`      | Oggetti di dominio (POJO/POPO), mappano le tabelle DB          |
| `F`      | Foundation  | `foundation/`  | DAO/persistenza, traduzione fra Entity e righe SQL             |
| `V`      | View        | `view/`        | Assegnazione variabili e `include` dei template PHP in `public/templates/` |

Tutto il routing passa dal **`CFrontController`** (entry point: `index.php`):
un URL del tipo `/<Risorsa>/<metodo>/<param1>/...` viene tradotto nella
chiamata `C<Risorsa>::<metodo>($param1, ...)`. Gli endpoint AJAX/JSON vivono
sotto `/api/...`.

## Struttura del progetto

```
TrackPortal/
├── .htaccess               # Front-controller rewrite + sicurezza + cache
├── index.php               # Entry point: autoload + CFrontController
├── trackportal.sql         # Schema DB + dati seed (importare in MySQL)
├── seed.php                # Genera l'hash delle password degli utenti seed
├── README.md
│
├── controller/             # Strato Controller
│   ├── CFrontController.php
│   ├── CAuth.php           CAccount.php   CAdmin.php     CCircuito.php
│   ├── CDashboard.php      CGestore.php   CHome.php      CNoleggio.php
│   ├── CPrenotazione.php   CRicerca.php   CSessione.php  CError.php
│
├── entity/                 # Strato Entity (14 classi POPO)
│   ├── EUtente.php   EPilota.php   EGestore.php   EAziendaNoleggio.php
│   ├── ECircuito.php ECircuitoFoto.php   ESessione.php  EVeicoloNoleggio.php
│   ├── EPrenotazione.php   ECartaCredito.php   EFattura.php
│   ├── EPromozione.php     EPromozioneDestinatario.php
│   └── EParametroSistema.php   EStoricoParametro.php
│
├── foundation/             # Strato Foundation (DAO)
│   ├── FDataBase.php           # Singleton PDO
│   ├── FPersistentManager.php  # Facade store/load/delete/update/exist
│   └── FUtente.php  FPilota.php  FGestore.php  FAziendaNoleggio.php
│       FCircuito.php FSessione.php FVeicoloNoleggio.php FPrenotazione.php
│       FCartaCredito.php FFattura.php FPromozione.php FParametroSistema.php
│
├── view/                   # Strato View (wrapper `render()` → template PHP)
│   ├── VBase.php (astratta) VHome.php  VAuth.php  VAccount.php
│   ├── VCircuito.php  VPrenotazione.php  VDashboard.php
│   └── VGestore.php  VNoleggio.php  VAdmin.php  VError.php
│
├── public/                 # Template PHP + CSS/JS/immagini serviti dal web server
│   ├── templates/
│   │   ├── partials/{header,footer}.php
│   │   ├── home.php
│   │   ├── auth/{login,register}.php
│   │   ├── circuits/{list,detail}.php
│   │   ├── bookings/{list,detail,new}.php
│   │   ├── dashboard/{pilot,manager,rental,admin}.php
│   │   ├── account/index.php
│   │   ├── manager/{circuits,schedule,promotions}.php
│   │   ├── rental/fleet.php
│   │   ├── admin/{commissions,affiliations,invoices}.php
│   │   └── errors/{403,404}.php
│   ├── css/style.css       # Tema scuro racing
│   ├── js/main.js          # Comportamenti front-end
│   └── immagini/           # Immagini caricate
│
├── utility/                # Helper procedurali (stile utility/ di FillSpaceWEB)
│   ├── autoload.php        # spl_autoload_register: prima lettera -> cartella
│   ├── config.php          # Costanti DB e applicazione
│   └── functions.php       # e, url, money, icon, csrf, flash, ...
│
└── uploads/                # Upload utente (PHP non eseguibile via .htaccess)
```

## Mappatura URL (front controller)

| URL                                                | Controller -> metodo            |
|----------------------------------------------------|---------------------------------|
| `/TrackPortal/`                                    | `CHome::index`                  |
| `/TrackPortal/Auth/login`                          | `CAuth::login`                  |
| `/TrackPortal/Auth/register`                       | `CAuth::register`               |
| `/TrackPortal/Auth/logout` (POST + CSRF)           | `CAuth::logout`                 |
| `/TrackPortal/Circuito/lista[?q=monza]`            | `CCircuito::lista`              |
| `/TrackPortal/Circuito/dettaglio/<id>`             | `CCircuito::dettaglio`          |
| `/TrackPortal/Account/index`                       | `CAccount::index`               |
| `/TrackPortal/Prenotazione/lista`                  | `CPrenotazione::lista`          |
| `/TrackPortal/Prenotazione/dettaglio/<id>`         | `CPrenotazione::dettaglio`      |
| `/TrackPortal/Prenotazione/nuova/<sessione_id>`    | `CPrenotazione::nuova`          |
| `/TrackPortal/Dashboard/{pilota,gestore,noleggio,admin}` | `CDashboard::*`           |
| `/TrackPortal/Gestore/{circuiti,calendario/<id>,promozioni}` | `CGestore::*`         |
| `/TrackPortal/Noleggio/flotta`                     | `CNoleggio::flotta`             |
| `/TrackPortal/Admin/{commissioni,affiliazioni,fatture}`  | `CAdmin::*`                |
| `/TrackPortal/api/circuiti/ricerca?q=monza`        | `CRicerca::circuiti` (JSON)     |
| `/TrackPortal/api/sessioni/<id>`                   | `CSessione::perCircuito` (JSON) |

## Setup su XAMPP

1. **Avvia Apache e MySQL** dal pannello XAMPP.
2. **Importa il database** dal file `trackportal.sql`:
   - Apri `http://localhost/phpmyadmin/`
   - Tab "Importa" → seleziona `c:\xampp\htdocs\TrackPortal\trackportal.sql` → Esegui.
3. **Genera le password seed** visitando una sola volta:
   `http://localhost/TrackPortal/seed.php`
4. Apri la home: **`http://localhost/TrackPortal/`**

## Account di test (password: `Password123!`)

| Email                       | Ruolo                  |
|-----------------------------|------------------------|
| `admin@trackportal.test`    | Amministratore         |
| `marco@trackportal.test`    | Pilota amatoriale      |
| `luca@trackportal.test`     | Gestore di circuiti    |
| `rent@trackportal.test`     | Azienda di noleggio    |

Si possono creare nuovi account da **`/Auth/register`**. Le aziende
(gestore/noleggio) restano in stato **"in attesa"** finche l'amministratore
non approva la richiesta dalla pagina **Affiliazioni**.

## Use case implementati

| UC  | Funzionalita                                       | Rotta principale                                |
|-----|----------------------------------------------------|--------------------------------------------------|
| 1   | Prenotazione sessione                              | `/Prenotazione/nuova/<sessione_id>`              |
| 2   | Aggiornare schedule del circuito                   | `/Gestore/calendario/<id>`                       |
| 3   | Gestione flotta azienda di noleggio                | `/Noleggio/flotta`                               |
| 4   | Assicurazione + percentuale di commissione         | `/Admin/commissioni`                             |
| 6   | Visualizzazione e ricerca circuiti                 | `/`, `/Circuito/lista`                           |
| 7   | Gestione prenotazioni e storico                    | `/Prenotazione/lista` + `/Prenotazione/dettaglio/<id>` |
| 8   | Gestione documenti di fatturazione                 | `/Admin/fatture`                                 |
| 9   | Gestione e aggiunta circuiti                       | `/Gestore/circuiti`                              |
| 10  | Gestione fidelizzazioni e offerte                  | `/Gestore/promozioni`                            |
| 11  | Gestione account                                   | `/Account/index`                                 |
| 12  | Approvazione affiliazione                          | `/Admin/affiliazioni`                            |

(UC5 supporto: escluso per esame come da requisiti.)

## Mappatura UML → Entity → Tabella

| Classe UML            | Entity (`entity/`)         | Tabella                                    |
|-----------------------|----------------------------|--------------------------------------------|
| Utente                | `EUtente`                  | `utente`                                   |
| Pilota                | `EPilota`                  | `pilota` (estende `utente`)                |
| Gestore Circuiti      | `EGestore`                 | `gestore_circuiti` (estende `utente`)      |
| Azienda Noleggio      | `EAziendaNoleggio`         | `azienda_noleggio` (estende `utente`)      |
| Amministratore        | `EUtente` (`ruolo=admin`)  | `utente`                                   |
| Circuito              | `ECircuito`                | `circuito` (+ `circuito_foto`)             |
| Sessione              | `ESessione`                | `sessione`                                 |
| Veicolo Noleggio      | `EVeicoloNoleggio`         | `veicolo_noleggio`                         |
| Prenotazione          | `EPrenotazione`            | `prenotazione`                             |
| Carta di Credito      | `ECartaCredito`            | `carta_credito` (masked + cvv hashato)     |
| Fattura               | `EFattura`                 | `fattura`                                  |
| Promozione            | `EPromozione`              | `promozione` (+ `promozione_destinatario`) |
| Parametro Sistema     | `EParametroSistema`        | `parametro_sistema` (+ `storico_parametro`) |

## Sicurezza implementata

- Password hashate con `password_hash` (BCRYPT).
- Token **CSRF** su tutti i form (incluso il logout, esposto solo via POST).
- Sessioni con cookie `HttpOnly` e `SameSite=Lax`, regenerate su login.
- Tutte le query usano **prepared statements PDO** (nessuna concatenazione di input).
- Output HTML escapato nei template con la funzione `e()` (`htmlspecialchars`) dove serve testo dinamico.
- Headers di sicurezza nell'`.htaccess`: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- `.htaccess` blocca l'esecuzione di file PHP nella cartella `uploads/`.
- Pagine di errore personalizzate (route `/Error/nonTrovato` e `/Error/accessoNegato`).
- Numero carta salvato solo in formato `**** **** **** 1234`; CVV hashato.

## Convenzioni di sviluppo

- **Routing**: aggiungere una nuova rotta significa creare un nuovo metodo
  pubblico statico su un controller esistente, oppure creare un nuovo file
  `controller/CMioController.php`. Non occorre toccare `CFrontController` ne
  registrare nulla altrove.
- **Persistenza**: per accedere al DB usare `FDataBase::getInstance()` o,
  preferibilmente, le classi `F*` che incapsulano le query e idratano le
  `E*` corrispondenti. `FPersistentManager` espone una facade generica
  `store/load/delete/update/exist`.
- **Template**: file `.php` sotto `public/templates/`; usano `<?= ... ?>` e chiamano
  direttamente gli helper in `utility/functions.php` (`e()`, `url()`, `money()`, `icon()`, …).
- **Cartelle vs prefissi**: cartelle in **minuscolo** (`controller/`, `entity/`,
  `foundation/`, `view/`), classi con prefisso **maiuscolo** (`CHome`, `EUtente`,
  ...) come imposto dall'autoloader e dalle convenzioni PSR sui nomi PHP.

## Estensioni previste (basi gia pronte)

Servizi: Opzione di noleggio veicolo e sottoscrizione assicurazione integrata.

Area Personale: Gestione account e visualizzazione dello storico prenotazioni.

🌐 Utenti Non Registrati

Consultazione: Visualizzazione libera delle schede tecniche dei circuiti e degli orari delle sessioni.

🚀 Funzionalità Principali

Sistema di Prenotazione Intelligente: Validazione automatica dei requisiti (licenze/certificati) prima della conferma.

Gestione Box: Algoritmo per l'assegnazione degli spazi tecnici ai piloti prenotati.

Infrastruttura di Analisi: Grafici in tempo reale per monitorare interazioni e flussi finanziari.

Comunicazione Integrata: Sistema di chat e messaggistica automatizzata per conferme e marketing.
 
 peppe marino 
 
