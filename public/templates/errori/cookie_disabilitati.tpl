{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="empty-state tp-narrow">
        {icon name='alert' size=56}
        <h3>Cookie non abilitati</h3>
        <p>
            Questa operazione richiede i <strong>cookie</strong> per mantenere la sessione
            e proteggere il modulo da invii non autorizzati. Il tuo browser non li ha inviati:
            probabilmente sono <strong>disabilitati</strong>.
        </p>
        <p class="text-muted text-small">
            Abilita i cookie per questo sito nelle impostazioni del browser, ricarica la pagina
            e ripeti l'operazione.
        </p>
        <div class="flex tp-empty-actions">
            <a class="button is-primary" href="javascript:history.back()">
                {icon name='arrow-left'} Torna indietro e riprova
            </a>
            <a class="button is-dark" href="{url path='/'}">
                {icon name='home'} Vai alla home
            </a>
        </div>
    </div>
</div>
{/block}
