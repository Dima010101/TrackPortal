{extends file='layouts/page.tpl'}

{block name=body}
{assign var='sid' value=$sessione.id|default:0}
{assign var='cid' value=$circuito.id|default:0}
{assign var='postiMax' value=$sessione.posti_max|default:1}
{assign var='postiLiberi' value=$postiMax - $posti_occupati|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Dettaglio sessione</h1>
            {if $circuito}
                <p class="lead">{$circuito.nome_circuito|default:''|escape}</p>
            {/if}
        </div>
    </div>

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
    {/foreach}

    {if $sessione && $circuito && $errors|@count == 0}
        {valuta_switcher}
        <div class="detail-grid">
            <div class="card form-stack">
                <h3 class="card-title">Informazioni sessione</h3>
                <p><strong>Inizio:</strong> {$sessione.inizio|default:''|escape}</p>
                <p><strong>Fine:</strong> {$sessione.fine|default:''|escape}</p>
                <p><strong>Posti:</strong> {$posti_occupati|default:0} / {$postiMax} occupati
                    ({$postiLiberi} liberi)</p>
                {if !empty($sessione.posti_per_box)}
                    <p><strong>Distribuzione box:</strong> fino a {$sessione.posti_per_box|default:1} piloti per box
                        ({$circuito.numero_box|default:0} box disponibili)</p>
                {/if}
                <p><strong>Tariffa accesso pista:</strong>
                    {prezzo value=$sessione.tariffa_accesso|default:0}</p>
                {if !empty($sessione.note)}
                    <p><strong>Note:</strong> {$sessione.note|escape|nl2br nofilter}</p>
                {/if}

                <hr class="divider">
                <h3 class="card-title">Modalità di partecipazione</h3>
                <form method="post" action="{url path='/prenotazione/conferma'}" class="form-stack">
                    {csrf_field}
                    <input type="hidden" name="sessione_id" value="{$sid}">

                    <label class="radio-card">
                        <input type="radio" name="modalita" value="proprio" checked>
                        <strong>Veicolo proprio (Opzione A)</strong>
                        <div class="text-muted radio-card-desc">
                            Paghi la tariffa accesso pista del circuito.
                        </div>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="modalita" value="noleggio"
                               {if $veicoli_noleggio|@count == 0}disabled{/if}>
                        <strong>Veicolo a noleggio (Opzione B)</strong>
                        <div class="text-muted radio-card-desc">
                            {if $veicoli_noleggio|@count == 0}
                                Nessun veicolo disponibile per questo circuito.
                            {else}
                                Scegli tra {$veicoli_noleggio|@count} veicoli disponibili.
                            {/if}
                        </div>
                    </label>

                    <button class="button is-primary" type="submit">
                        Continua {icon name='arrow-right'}
                    </button>
                </form>
            </div>

            <aside class="card">
                <h3 class="card-title">Il tuo profilo</h3>
                <p><strong>Nome:</strong> {$pilota.nome|default:''|escape} {$pilota.cognome|default:''|escape}</p>
                <p><strong>Categoria:</strong> {tp_ucfirst s=$pilota.categoria|default:''}</p>
                {if !empty($pilota.licenza)}
                    <p><strong>Licenza:</strong> {$pilota.licenza|escape}</p>
                {/if}
            </aside>
        </div>
    {/if}
</div>
{/block}
