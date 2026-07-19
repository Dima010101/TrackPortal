{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Il mio account','url'=>'/account'],['label'=>'Esito modifica']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Modifica account</h1>
        </div>
    </div>

    {if $successo|default:false}
        <div class="notification is-success">
            {icon name='check'}
            <strong>Modifica completata.</strong>
            I tuoi dati sono stati aggiornati correttamente.
        </div>
        <div class="card">
            <h3 class="card-title">Dati aggiornati</h3>
            <p><strong>Nome:</strong> {$profilo.nome|default:''|escape} {$profilo.cognome|default:''|escape}</p>
            <p><strong>Email:</strong> {$profilo.email|default:''|escape}</p>
            <a class="button is-primary mt-2" href="{url path='/account'}">
                {icon name='user'} Torna al profilo
            </a>
        </div>
    {else}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
        <a class="button is-primary" href="{url path='/account'}">
            Ripeti la modifica
        </a>
    {/if}
</div>
{/block}
