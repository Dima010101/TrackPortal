-- TrackPortal - Schema database (MySQL/MariaDB, XAMPP).
-- Crea SOLO il database vuoto; i dati di prova vengono inseriti da seed.php.

DROP DATABASE IF EXISTS trackportal;
CREATE DATABASE trackportal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE trackportal;

CREATE TABLE account (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(80)  NOT NULL,
    cognome         VARCHAR(80)  NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    ruolo           ENUM('pilota','gestore_circuiti','gestore_noleggio','admin') NOT NULL,
    stato_account   ENUM('attivo','sospeso','bannato','in_attesa','rifiutato') NOT NULL DEFAULT 'attivo',
    email_verificata TINYINT(1) NOT NULL DEFAULT 0,
    token_verifica  VARCHAR(64) NULL,
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pilota (
    id              INT PRIMARY KEY,
    categoria       ENUM('amatoriale','professionista') NOT NULL DEFAULT 'amatoriale',
    licenza         VARCHAR(80)  NULL,
    scadenza_licenza DATE NULL,
    certificato_medico_path VARCHAR(255) NULL,
    licenza_path            VARCHAR(255) NULL,
    documenti_stato ENUM('in_attesa','approvati','respinti') NOT NULL DEFAULT 'in_attesa',
    codice_fiscale  VARCHAR(16)  NULL,
    indirizzo       VARCHAR(255) NOT NULL DEFAULT '',
    cap             VARCHAR(10)  NOT NULL DEFAULT '',
    comune          VARCHAR(120) NOT NULL DEFAULT '',
    provincia       VARCHAR(4)   NOT NULL DEFAULT '',
    fatt_indirizzo  VARCHAR(255) NOT NULL DEFAULT '',
    fatt_cap        VARCHAR(10)  NOT NULL DEFAULT '',
    fatt_comune     VARCHAR(120) NOT NULL DEFAULT '',
    fatt_provincia  VARCHAR(4)   NOT NULL DEFAULT '',
    CONSTRAINT fk_pilota_account
        FOREIGN KEY (id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gestore_circuiti (
    id              INT PRIMARY KEY,
    nome_societa    VARCHAR(150) NOT NULL,
    partita_iva     VARCHAR(20)  NOT NULL UNIQUE,
    codice_fiscale  VARCHAR(20)  NULL,
    indirizzo       VARCHAR(255) NOT NULL DEFAULT '',
    cap             VARCHAR(10)  NOT NULL DEFAULT '',
    comune          VARCHAR(120) NOT NULL DEFAULT '',
    provincia       VARCHAR(4)   NOT NULL DEFAULT '',
    affiliazione    ENUM('in_attesa','approvata','rifiutata') NOT NULL DEFAULT 'in_attesa',
    CONSTRAINT fk_gestore_account
        FOREIGN KEY (id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE azienda_noleggio (
    id              INT PRIMARY KEY,
    nome_societa    VARCHAR(150) NOT NULL,
    partita_iva     VARCHAR(20)  NOT NULL UNIQUE,
    codice_fiscale  VARCHAR(20)  NULL,
    indirizzo       VARCHAR(255) NOT NULL DEFAULT '',
    cap             VARCHAR(10)  NOT NULL DEFAULT '',
    comune          VARCHAR(120) NOT NULL DEFAULT '',
    provincia       VARCHAR(4)   NOT NULL DEFAULT '',
    affiliazione    ENUM('in_attesa','approvata','rifiutata') NOT NULL DEFAULT 'in_attesa',
    CONSTRAINT fk_noleggio_account
        FOREIGN KEY (id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE amministratore (
    id              INT PRIMARY KEY,
    CONSTRAINT fk_admin_account
        FOREIGN KEY (id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE circuito (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gestore_id      INT NOT NULL,
    nome_circuito   VARCHAR(150) NOT NULL,
    localita        VARCHAR(255) NOT NULL DEFAULT '',
    indirizzo       VARCHAR(255) NOT NULL DEFAULT '',
    lunghezza_km    DECIMAL(10,3) NULL,
    descrizione     TEXT NULL,
    tipologia_veicoli ENUM('auto','moto','entrambi') NOT NULL DEFAULT 'entrambi',
    numero_box      INT NOT NULL DEFAULT 0,
    telefono        VARCHAR(30) NULL,
    email           VARCHAR(190) NULL,
    sito_web        VARCHAR(255) NULL,
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_circuito_gestore
        FOREIGN KEY (gestore_id) REFERENCES gestore_circuiti(id)
) ENGINE=InnoDB;

CREATE TABLE circuito_foto (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    circuito_id INT NOT NULL,
    path_file   VARCHAR(255) NOT NULL,
    ordine      INT NOT NULL DEFAULT 0,
    didascalia  VARCHAR(255) NULL,
    CONSTRAINT fk_foto_circuito
        FOREIGN KEY (circuito_id) REFERENCES circuito(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sessione (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    circuito_id     INT NOT NULL,
    inizio          DATETIME NOT NULL,
    fine            DATETIME NOT NULL,
    tariffa_accesso DECIMAL(10,2) NOT NULL DEFAULT 0,
    posti_max       INT NOT NULL DEFAULT 1,
    posti_per_box   INT NOT NULL DEFAULT 1,
    note            TEXT NULL,
    stato           ENUM('amatoriale', 'professionistica', 'privata', 'annullata') NOT NULL DEFAULT 'privata',
    causa_annullamento VARCHAR(255) NULL,
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessione_circuito
        FOREIGN KEY (circuito_id) REFERENCES circuito(id) ON DELETE CASCADE,
    INDEX idx_sessione_circuito_inizio (circuito_id, inizio)
) ENGINE=InnoDB;

CREATE TABLE veicolo_noleggio (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    azienda_id      INT NOT NULL,
    circuito_id     INT NOT NULL,
    targa           VARCHAR(20) NOT NULL UNIQUE,
    categoria       ENUM('auto','moto') NOT NULL,
    capienza        INT NOT NULL DEFAULT 1,
    potenza_cv      INT NULL,
    anno            INT NULL,
    marca           VARCHAR(80) NULL,
    modello         VARCHAR(80) NULL,
    prezzo          DECIMAL(10,2) NOT NULL DEFAULT 0,
    disponibile     TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_veicolo_azienda
        FOREIGN KEY (azienda_id) REFERENCES azienda_noleggio(id),
    CONSTRAINT fk_veicolo_circuito
        FOREIGN KEY (circuito_id) REFERENCES circuito(id)
) ENGINE=InnoDB;

CREATE TABLE carta_credito (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pilota_id       INT NOT NULL,
    numero_masked   VARCHAR(20) NOT NULL,
    nome_titolare   VARCHAR(80) NOT NULL,
    cognome_titolare VARCHAR(80) NOT NULL,
    data_scadenza   VARCHAR(7) NOT NULL,
    CONSTRAINT fk_carta_pilota
        FOREIGN KEY (pilota_id) REFERENCES pilota(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE prenotazione (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    codice_identificativo   VARCHAR(32) NOT NULL UNIQUE,
    pilota_id               INT NOT NULL,
    sessione_id             INT NOT NULL,
    veicolo_noleggio_id     INT NULL,
    targa_veicolo           VARCHAR(20) NULL,
    numero_box              INT NULL,
    assicurazione           TINYINT(1) NOT NULL DEFAULT 0,
    prezzo_importo          DECIMAL(10,2) NOT NULL,
    prezzo_valuta           VARCHAR(3) NOT NULL DEFAULT 'EUR',
    imponibile_accesso       DECIMAL(10,2) NOT NULL DEFAULT 0,
    imponibile_noleggio      DECIMAL(10,2) NOT NULL DEFAULT 0,
    imponibile_assicurazione DECIMAL(10,2) NOT NULL DEFAULT 0,
    stato                   ENUM('confermata','cancellata') NOT NULL DEFAULT 'confermata',
    promozione_id           INT NULL,
    sconto_importo          DECIMAL(10,2) NOT NULL DEFAULT 0,
    rimborso_previsto       DECIMAL(10,2) NOT NULL DEFAULT 0,
    causa_cancellazione     VARCHAR(255) NULL,
    data_inserimento        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    carta_credito_id        INT NULL,
    CONSTRAINT fk_pren_pilota
        FOREIGN KEY (pilota_id) REFERENCES pilota(id),
    CONSTRAINT fk_pren_sessione
        FOREIGN KEY (sessione_id) REFERENCES sessione(id),
    CONSTRAINT fk_pren_veicolo
        FOREIGN KEY (veicolo_noleggio_id) REFERENCES veicolo_noleggio(id),
    CONSTRAINT fk_pren_carta
        FOREIGN KEY (carta_credito_id) REFERENCES carta_credito(id) ON DELETE SET NULL,
    INDEX idx_pren_sessione_stato (sessione_id, stato)
) ENGINE=InnoDB;

CREATE TABLE sanzione_pilota (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    emittente_tipo  ENUM('gestore_circuiti','azienda_noleggio') NOT NULL DEFAULT 'gestore_circuiti',
    gestore_id      INT NOT NULL,
    pilota_id       INT NOT NULL,
    tipo            ENUM('ban','sospensione') NOT NULL,
    motivo          VARCHAR(255) NULL,
    data_inizio     DATE NOT NULL,
    data_fine       DATE NULL,
    stato           ENUM('attiva','revocata') NOT NULL DEFAULT 'attiva',
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revocata_il     DATETIME NULL,
    CONSTRAINT fk_sanzione_emittente
        FOREIGN KEY (gestore_id) REFERENCES account(id) ON DELETE CASCADE,
    CONSTRAINT fk_sanzione_pilota
        FOREIGN KEY (pilota_id) REFERENCES pilota(id) ON DELETE CASCADE,
    INDEX idx_sanzione_lookup (gestore_id, pilota_id, stato)
) ENGINE=InnoDB;

CREATE TABLE documento_fiscale (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    tipo                     ENUM('fattura','nota_credito') NOT NULL,
    emittente_tipo           ENUM('gestore_circuiti','azienda_noleggio','piattaforma') NOT NULL,
    emittente_id             INT NOT NULL DEFAULT 0,
    anno                     INT NOT NULL,
    numero                   INT NOT NULL,
    numero_formattato        VARCHAR(20) NOT NULL,
    data_emissione           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    emittente_denominazione  VARCHAR(190) NOT NULL,
    emittente_piva           VARCHAR(20)  NOT NULL DEFAULT '',
    emittente_cf             VARCHAR(20)  NULL,
    emittente_indirizzo      VARCHAR(255) NOT NULL DEFAULT '',
    cliente_tipo             ENUM('pilota','gestore_circuiti','azienda_noleggio') NOT NULL,
    cliente_id               INT NOT NULL,
    cliente_denominazione    VARCHAR(190) NOT NULL,
    cliente_piva             VARCHAR(20)  NULL,
    cliente_cf               VARCHAR(20)  NULL,
    cliente_indirizzo        VARCHAR(255) NOT NULL DEFAULT '',
    prenotazione_id          INT NULL,
    documento_riferimento_id INT NULL,
    causale                  VARCHAR(255) NULL,
    valuta                   VARCHAR(3) NOT NULL DEFAULT 'EUR',
    totale_imponibile        DECIMAL(10,2) NOT NULL DEFAULT 0,
    totale_iva               DECIMAL(10,2) NOT NULL DEFAULT 0,
    totale_documento         DECIMAL(10,2) NOT NULL DEFAULT 0,
    bollo                    DECIMAL(10,2) NOT NULL DEFAULT 0,
    CONSTRAINT uq_doc_numerazione UNIQUE (emittente_tipo, emittente_id, anno, numero),
    CONSTRAINT fk_doc_prenotazione
        FOREIGN KEY (prenotazione_id) REFERENCES prenotazione(id) ON DELETE SET NULL,
    CONSTRAINT fk_doc_riferimento
        FOREIGN KEY (documento_riferimento_id) REFERENCES documento_fiscale(id) ON DELETE SET NULL,
    INDEX idx_doc_emittente (emittente_tipo, emittente_id),
    INDEX idx_doc_cliente   (cliente_tipo, cliente_id),
    INDEX idx_doc_pren      (prenotazione_id)
) ENGINE=InnoDB;

CREATE TABLE documento_fiscale_riga (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    documento_id    INT NOT NULL,
    descrizione     VARCHAR(255) NOT NULL,
    quantita        DECIMAL(10,2) NOT NULL DEFAULT 1,
    prezzo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    imponibile      DECIMAL(10,2) NOT NULL DEFAULT 0,
    aliquota_iva    DECIMAL(5,2)  NOT NULL DEFAULT 0,
    natura_iva      VARCHAR(4)    NULL,
    imposta         DECIMAL(10,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_riga_documento
        FOREIGN KEY (documento_id) REFERENCES documento_fiscale(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contatore_documento (
    emittente_tipo  ENUM('gestore_circuiti','azienda_noleggio','piattaforma') NOT NULL,
    emittente_id    INT NOT NULL DEFAULT 0,
    anno            INT NOT NULL,
    ultimo_numero   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (emittente_tipo, emittente_id, anno)
) ENGINE=InnoDB;

CREATE TABLE promozione (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    creator_account_id    INT NULL,
    veicolo_noleggio_id   INT NULL,
    circuito_id           INT NULL,
    titolo                VARCHAR(150) NOT NULL,
    descrizione           TEXT NULL,
    tipo_sconto           ENUM('importo','percentuale') NOT NULL DEFAULT 'percentuale',
    valore                DECIMAL(10,2) NOT NULL,
    codice                VARCHAR(40) NOT NULL UNIQUE,
    data_inizio           DATE NULL,
    data_fine             DATE NULL,
    segmento_destinatari  ENUM('tutti','storico_prenotazioni','prenotazione_periodo') NOT NULL DEFAULT 'tutti',
    soglia_prenotazioni   INT NULL,
    data_prenotazione_inizio DATE NULL,
    data_prenotazione_fine DATE NULL,
    stato_promozione      ENUM('attiva','sospesa','scaduta') NOT NULL DEFAULT 'attiva',
    CONSTRAINT fk_prom_creator_account
        FOREIGN KEY (creator_account_id) REFERENCES account(id) ON DELETE SET NULL,
    CONSTRAINT fk_promo_veicolo
        FOREIGN KEY (veicolo_noleggio_id) REFERENCES veicolo_noleggio(id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_circuito
        FOREIGN KEY (circuito_id) REFERENCES circuito(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE prenotazione
    ADD CONSTRAINT fk_pren_promozione
        FOREIGN KEY (promozione_id) REFERENCES promozione(id) ON DELETE SET NULL;

CREATE TABLE configurazione_piattaforma (
    id                       INT PRIMARY KEY,
    prezzo_assicurazione     DECIMAL(10,2) NOT NULL DEFAULT 0,
    percentuale_commissione  DECIMAL(5,2) NOT NULL DEFAULT 0,
    aliquota_iva             DECIMAL(5,2) NOT NULL DEFAULT 22.00,
    fisc_denominazione       VARCHAR(190) NOT NULL DEFAULT 'TrackPortal',
    fisc_partita_iva         VARCHAR(20)  NOT NULL DEFAULT '',
    fisc_codice_fiscale      VARCHAR(20)  NULL,
    fisc_indirizzo           VARCHAR(255) NOT NULL DEFAULT '',
    aggiornato_il            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_config_singleton CHECK (id = 1)
) ENGINE=InnoDB;

CREATE TABLE storico_configurazione (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    prezzo_assicurazione     DECIMAL(10,2) NOT NULL,
    percentuale_commissione  DECIMAL(5,2) NOT NULL,
    aliquota_iva             DECIMAL(5,2) NOT NULL DEFAULT 22.00,
    aggiornato_il            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
