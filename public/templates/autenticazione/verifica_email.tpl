{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="auth-wrapper">
        <div class="card tp-auth-card">
            <div class="card-content">
            <div class="tp-auth-brand">{logo class='tp-logo tp-logo--auth'}</div>
            <h1 class="title is-4">Conferma il tuo indirizzo email</h1>
            <p class="subtitle is-6 text-muted">
                Per accedere devi prima confermare il tuo indirizzo email.
                Ti abbiamo inviato un messaggio con il link di conferma a
                <strong>{$email}</strong>: controlla anche la cartella spam.
            </p>

            <div class="notification is-warning tp-flash">
                {flash_icon type='warn'}<span>L'accesso resta bloccato finché non confermi l'indirizzo email.</span>
            </div>

            <form method="post" action="{url path='/auth/rinvia-email'}" class="form-stack">
                {csrf_field}
                <input type="hidden" name="email" value="{$email}">
                <button type="submit" class="button is-primary is-fullwidth">
                    {icon name='mail'} Invia di nuovo l'email di conferma
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
