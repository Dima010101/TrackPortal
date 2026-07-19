{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="auth-wrapper">
        <div class="card tp-auth-card">
            <div class="card-content">
            <div class="tp-auth-brand">{logo class='tp-logo tp-logo--auth'}</div>
            <h1 class="title is-4">Accedi a {$app_name}</h1>
            <p class="subtitle is-6 text-muted">Inserisci le tue credenziali per continuare.</p>

            <form method="post" class="form-stack" autocomplete="on">
                {csrf_field}
                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" class="input" type="email" name="email" required autofocus
                           autocomplete="email" placeholder="nome@example.com">
                </div>
                <div class="form-row">
                    <label for="password">Password</label>
                    <input id="password" class="input" type="password" name="password" required
                           autocomplete="current-password">
                </div>
                <button type="submit" class="button is-primary is-fullwidth">
                    {icon name='login'} Entra
                </button>
            </form>

            <hr class="divider">
            <p class="text-center text-muted mb-0">
                Non hai ancora un account?
                <a href="{url path='/auth/registrazione'}"><strong>Registrati</strong></a>.
            </p>
            </div>
        </div>
    </div>
</div>
{/block}
