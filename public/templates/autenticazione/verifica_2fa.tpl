{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="auth-wrapper">
        <div class="card tp-auth-card">
            <div class="card-content">
            <div class="tp-auth-brand">{logo class='tp-logo tp-logo--auth'}</div>
            <h1 class="title is-4">Verifica l'accesso</h1>
            <p class="subtitle is-6 text-muted">
                Per la tua sicurezza, abbiamo inviato un codice di verifica a
                <strong>{$email_mascherata}</strong>. Inseriscilo per completare l'accesso.
            </p>

            {foreach from=$errors|default:[] item=err}
                <div class="notification is-danger tp-flash">{flash_icon type='error'}<span>{$err}</span></div>
            {/foreach}

            <form method="post" class="form-stack" autocomplete="one-time-code">
                {csrf_field}
                <div class="form-row">
                    <label for="codice">Codice di verifica</label>
                    <input id="codice" class="input" type="text" name="codice" required autofocus
                           inputmode="numeric" pattern="[0-9]*" maxlength="6"
                           autocomplete="one-time-code" placeholder="123456"
                           style="letter-spacing:6px; text-align:center; font-size:1.25rem;">
                </div>
                <button type="submit" class="button is-primary is-fullwidth">
                    {icon name='check'} Conferma e accedi
                </button>
            </form>

            <form method="post" action="{url path='/auth/rinvia-codice'}" class="mt-3">
                {csrf_field}
                <button type="submit" class="button is-ghost is-small is-fullwidth">
                    {icon name='mail'} Non hai ricevuto il codice? Invialo di nuovo
                </button>
            </form>

            <hr class="divider">
            <p class="text-center text-muted mb-0">
                <a href="{url path='/auth/accesso'}">{icon name='arrow-left'} Torna all'accesso</a>
            </p>
            </div>
        </div>
    </div>
</div>
{/block}
