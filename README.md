# TrackPortal

Piattaforma web per la prenotazione di sessioni in pista, sviluppata per
Apache con PHP e MySQL/MariaDB (stack XAMPP).

**Stack:** PHP 8.2+, MySQL/MariaDB, **Doctrine ORM/DBAL**, **Smarty 5**, Bulma CSS, JavaScript vanilla.

## Requisiti

- PHP >= 8.2
- Composer
- Apache (XAMPP)
- MySQL/MariaDB

## Architettura

TrackPortal segue il pattern accademico **MVC + Foundation**:

| Prefisso | Strato     | Cartella      | Responsabilità                                                                 |
|----------|------------|---------------|--------------------------------------------------------------------------------|
| `C`      | Control    | `Control/`    | Logica applicativa, orchestrazione Entity/Foundation/View                      |
| `E`      | Entity     | `Entity/`     | Oggetti di dominio mappati con attributi Doctrine ORM                          |
| `F`      | Foundation | `Foundation/` | Persistenza (ORM, repository, SQL nativo)                                      |
| `V`      | View       | `View/`       | Assegnazione variabili e rendering template Smarty in `public/templates/`      |

**Persistenza:** CRUD e transazioni tramite `FPersistentManager` (Doctrine ORM). Query SQL complesse o report su `FDataBase` (DBAL). Bootstrap ORM in `FEntityManager`.

**Presentazione:** template Smarty (`.tpl`) con plugin che espongono gli helper procedurali (`url`, `money`, `csrf_field`, …) definiti in `support/helpers.php`.
