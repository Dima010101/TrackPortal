{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Promozioni','url'=>'/promozioni'],['label'=>'Promozioni servizio']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Promozioni servizio</h1>
            {if $tipo_entita == 'veicolo'}
                <p class="lead">
                    Veicolo: <strong>{$entita.marca|default:''} {$entita.modello|default:''}</strong>
                    ({$entita.targa|default:''})
                </p>
            {else}
                <p class="lead">
                    Circuito: <strong>{$entita.nome_circuito|default:''}</strong>
                    {if !empty($entita.localita)} — {$entita.localita}{/if}
                </p>
            {/if}
        </div>
    </div>

    {if $conferma_ok|default:false && $promo_creata|default:null}
        <div class="notification is-success">
            {icon name='check'}
            <strong>Conferma creazione:</strong>
            la promozione <em>{$promo_creata.titolo|default:''|escape}</em>
            è stata salvata. Codice: <code>{$promo_creata.codice|default:''|escape}</code>
        </div>
    {/if}

    {if $promo|@count > 0}
        <div class="tp-promo-toolbar mb-3">
            <a class="button is-primary" href="{url path="/promozioni/servizio/{$entita_id|default:0}/nuova"}">
                {icon name='plus'} Nuova promozione
            </a>
        </div>
    {/if}

    {include file='partials/tabella_promozioni.tpl'}
</div>
{/block}
