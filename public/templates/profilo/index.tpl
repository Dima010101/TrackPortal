{extends file='layouts/page.tpl'}

{block name=body}
{assign var='ru' value=$user.ruolo|default:''}
<div class="container">
    {if $user.ruolo|default:'' == 'pilota'}
        {back_button href='/dashboard' label='Home'}
    {else}
        {back_button href=$dash_back|default:'/' label='Dashboard'}
    {/if}

    <div class="page-header">
        <div>
            <h1>Il mio account</h1>
            <p class="lead">Gestisci dati personali, password e profilo specifico per il tuo ruolo
                ({ruolo_label ruolo=$ru}).</p>
        </div>
    </div>

    <div class="grid grid-2">
        <form method="post" class="card form-stack is-wide">
            {csrf_field}
            <input type="hidden" name="azione" value="aggiorna_dati">
            <h3 class="card-title">Dati anagrafici</h3>
            <div class="grid grid-2">
                <div class="form-row"><label>Nome</label><input name="nome" value="{$user.nome|default:''}" required></div>
                <div class="form-row"><label>Cognome</label><input name="cognome" value="{$user.cognome|default:''}" required></div>
            </div>
            <div class="form-row"><label>Email</label><input type="email" name="email" value="{$user.email|default:''}" required></div>
            <button class="button is-primary" type="submit">{icon name='check'} Salva dati</button>
        </form>

        <form method="post" class="card form-stack is-wide">
            {csrf_field}
            <input type="hidden" name="azione" value="cambia_password">
            <h3 class="card-title">Cambia password</h3>
            <div class="form-row"><label>Password attuale</label><input type="password" name="password_vec" required autocomplete="current-password"></div>
            <div class="form-row"><label>Nuova password</label><input type="password" name="password_new" minlength="8" required autocomplete="new-password"></div>
            <button class="button is-primary" type="submit">{icon name='lock'} Aggiorna password</button>
        </form>
    </div>

    {if $ru == 'pilota' && $extra}
        <form method="post" class="card form-stack tp-panel-medium mt-3">
            {csrf_field}
            <input type="hidden" name="azione" value="aggiorna_pilota">
            <h3 class="card-title">Profilo pilota</h3>
            <div class="form-row"><label>Categoria</label>
                <select name="categoria">
                    <option value="amatoriale"     {if $extra.categoria|default:'' == 'amatoriale'}selected{/if}>Amatoriale</option>
                    <option value="professionista" {if $extra.categoria|default:'' == 'professionista'}selected{/if}>Professionista</option>
                </select>
            </div>
            <div class="grid grid-2">
                <div class="form-row"><label>Licenza</label><input name="licenza" value="{$extra.licenza|default:''}"></div>
                <div class="form-row"><label>Scadenza licenza</label><input type="date" name="scadenza_licenza" value="{$extra.scadenza_licenza|default:''}"></div>
            </div>
            <button class="button is-primary" type="submit">{icon name='check'} Aggiorna profilo</button>
        </form>
    {elseif ($ru == 'gestore_circuiti' || $ru == 'gestore_noleggio') && $extra}
        {assign var='aff' value=$extra.affiliazione|default:''}
        {assign var='aff_badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
        <div class="card mt-3 tp-panel-medium">
            <h3 class="card-title">Profilo aziendale</h3>
            <p><strong>Società:</strong> {$extra.nome_societa|default:''}</p>
            <p><strong>Partita IVA:</strong> <code>{$extra.partita_iva|default:''}</code></p>
            <p><strong>Stato affiliazione:</strong>
               <span class="badge {$aff_badge}">
                   {tp_ucfirst s=$aff}
               </span>
            </p>
        </div>
    {/if}
</div>
{/block}
