{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="empty-state tp-narrow">
        {icon name='alert' size=56}
        <h3>404 · Pagina non trovata</h3>
        <p>La risorsa che stai cercando non esiste, è stata spostata
           o non hai i permessi per accedervi.</p>
        <div class="flex tp-empty-actions">
            <a class="button is-primary" href="{url path='/'}">
                {icon name='home'} Torna alla home
            </a>
        </div>
    </div>
</div>
{/block}
