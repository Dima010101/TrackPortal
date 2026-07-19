{extends file='layouts/page.tpl'}

{block name=body}
{assign var='p' value=$pending|default:[]}
{assign var='att' value=$profilo_attuale|default:[]}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Il mio account','url'=>'/account'],['label'=>'Riepilogo modifiche']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Riepilogo modifiche</h1>
            <p class="lead">Verifica le modifiche prima di confermare il salvataggio definitivo.</p>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Modifiche in sospeso</h3>
        <dl class="tp-dl">
            {if $att.nome|default:'' != $p.nome|default:''}
                <dt>Nome</dt>
                <dd><s class="text-muted">{$att.nome|default:''|escape}</s> → <strong>{$p.nome|default:''|escape}</strong></dd>
            {/if}
            {if $att.cognome|default:'' != $p.cognome|default:''}
                <dt>Cognome</dt>
                <dd><s class="text-muted">{$att.cognome|default:''|escape}</s> → <strong>{$p.cognome|default:''|escape}</strong></dd>
            {/if}
            {if $att.email|default:'' != $p.email|default:''}
                <dt>Email</dt>
                <dd><s class="text-muted">{$att.email|default:''|escape}</s> → <strong>{$p.email|default:''|escape}</strong></dd>
            {/if}
            {if $p.cambia_password|default:false}
                <dt>Password</dt>
                <dd><strong>Verrà aggiornata</strong></dd>
            {/if}
            {if $ruolo|default:'' == 'pilota' && $p.pilota|default:null}
                {assign var='pil' value=$p.pilota}
                {assign var='ext' value=$extra_attuale|default:[]}
                {if $ext.categoria|default:'' != $pil.categoria|default:''}
                    <dt>Categoria pilota</dt>
                    <dd><s class="text-muted">{tp_ucfirst s=$ext.categoria|default:''}</s> → <strong>{tp_ucfirst s=$pil.categoria|default:''}</strong></dd>
                {/if}
                {if $ext.licenza|default:'' != $pil.licenza|default:''}
                    <dt>Licenza</dt>
                    <dd><s class="text-muted">{$ext.licenza|default:'—'|escape}</s> → <strong>{$pil.licenza|default:'—'|escape}</strong></dd>
                {/if}
                {if $ext.scadenza_licenza|default:'' != $pil.scadenza_licenza|default:''}
                    <dt>Scadenza licenza</dt>
                    <dd><s class="text-muted">{$ext.scadenza_licenza|default:'—'|escape}</s> → <strong>{$pil.scadenza_licenza|default:'—'|escape}</strong></dd>
                {/if}
                {if $ext.codice_fiscale|default:'' != $pil.codice_fiscale|default:''}
                    <dt>Codice fiscale</dt>
                    <dd><s class="text-muted">{$ext.codice_fiscale|default:'—'|escape}</s> → <strong>{$pil.codice_fiscale|default:'—'|escape}</strong></dd>
                {/if}

                {capture name='resOld'}{$ext.indirizzo|default:''} {$ext.cap|default:''} {$ext.comune|default:''} {$ext.provincia|default:''}{/capture}
                {capture name='resNew'}{$pil.indirizzo|default:''} {$pil.cap|default:''} {$pil.comune|default:''} {$pil.provincia|default:''}{/capture}
                {if ($smarty.capture.resOld|trim) != ($smarty.capture.resNew|trim)}
                    <dt>Indirizzo di residenza</dt>
                    <dd><s class="text-muted">{$smarty.capture.resOld|trim|default:'—'|escape}</s> → <strong>{$smarty.capture.resNew|trim|default:'—'|escape}</strong></dd>
                {/if}

                {capture name='fattOld'}{$ext.fatt_indirizzo|default:''} {$ext.fatt_cap|default:''} {$ext.fatt_comune|default:''} {$ext.fatt_provincia|default:''}{/capture}
                {capture name='fattNew'}{$pil.fatt_indirizzo|default:''} {$pil.fatt_cap|default:''} {$pil.fatt_comune|default:''} {$pil.fatt_provincia|default:''}{/capture}
                {if ($smarty.capture.fattOld|trim) != ($smarty.capture.fattNew|trim)}
                    <dt>Indirizzo di fatturazione</dt>
                    <dd><s class="text-muted">{$smarty.capture.fattOld|trim|default:'—'|escape}</s> → <strong>{$smarty.capture.fattNew|trim|default:'—'|escape}</strong></dd>
                {/if}
            {/if}
        </dl>

        <form method="post" action="{url path='/account/salva'}" class="flex mt-3">
            {csrf_field}
            <button class="button is-primary" type="submit">{icon name='check'} Conferma e salva</button>
            <a class="button is-dark" href="{url path='/account'}">Annulla</a>
        </form>
    </div>
</div>
{/block}
