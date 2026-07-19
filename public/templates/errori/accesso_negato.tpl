{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="empty-state tp-narrow">
        {icon name='lock' size=56}
        <h3>403 · Accesso negato</h3>
        <p>
            {if !empty($allowed_labels)}
                Questa sezione è riservata a:
                <strong>{$allowed_labels}</strong>.
            {else}
                Non hai i permessi per visualizzare questa pagina.
            {/if}
        </p>
        <div class="flex tp-empty-actions">
            <a class="button is-primary" href="{url path='/'}">
                {icon name='home'} Torna alla home
            </a>
        </div>
    </div>
</div>
{/block}
