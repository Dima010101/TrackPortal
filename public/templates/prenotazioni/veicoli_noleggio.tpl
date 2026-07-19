{extends file='layouts/page.tpl'}

{block name=body}
{assign var='sid' value=$sessione.id|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Scegli il veicolo a noleggio</h1>
            {if $circuito}
                <p class="lead">{$circuito.nome_circuito|default:''|escape} · Opzione B</p>
            {/if}
        </div>
    </div>

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
    {/foreach}

    {if $veicoli|@count == 0}
        <div class="empty-state">
            {icon name='car' size=56}
            <h3>Nessun veicolo disponibile</h3>
        </div>
    {else}
        {valuta_switcher}
        <form method="post">
            {csrf_field}
            <div class="detail-grid detail-grid--booking">
                <div class="card form-stack">
                    <div class="grid grid-2">
                        {foreach from=$veicoli item=v}
                            {assign var='vid' value=$v.id|default:0}
                            <label class="radio-card">
                                <input type="radio" name="veicolo_id" value="{$vid}"
                                       {if $state.veicolo_id|default:0 == $vid}checked{/if} required>
                                <strong>{$v.marca|default:''|escape} {$v.modello|default:''|escape}</strong>
                                <div class="text-muted radio-card-desc">
                                    {tp_ucfirst s=$v.categoria|default:''}
                                    · {$v.potenza_cv|default:0}cv
                                    · cap. {$v.capienza|default:1}<br>
                                    Targa: {$v.targa|default:''|escape}
                                </div>
                                <div class="mt-1 tp-price">
                                    {prezzo value=$v.prezzo|default:0}
                                </div>
                            </label>
                        {/foreach}
                    </div>
                    <button class="button is-primary" type="submit"
                            formaction="{url path='/prenotazione/veicolo'}">
                        Continua al riepilogo {icon name='arrow-right'}
                    </button>
                </div>

                {include file='partials/box_assicurazione.tpl'
                    prezzo_assicurazione=$prezzo_assicurazione|default:0
                    assicurazione_on=$state.assicurazione|default:0}
            </div>
        </form>
    {/if}
</div>
{/block}
