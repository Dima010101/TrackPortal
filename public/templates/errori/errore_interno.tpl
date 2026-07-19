{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="empty-state tp-narrow">
        {icon name='alert' size=56}
        <h3>500 · Errore interno</h3>
        <p>Si è verificato un errore imprevisto durante l'elaborazione della richiesta.
           Il problema è stato registrato: riprova tra qualche istante.</p>
        <div class="flex tp-empty-actions">
            <a class="button is-primary" href="{url path='/'}">
                {icon name='home'} Torna alla home
            </a>
        </div>
        {if $dettagli|default:'' !== ''}
            <pre class="text-small tp-debug-trace">{$dettagli}</pre>
        {/if}
    </div>
</div>
{/block}
