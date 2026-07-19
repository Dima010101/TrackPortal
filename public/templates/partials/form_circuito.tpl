{* Campi condivisi per inserimento/modifica circuito *}
<div class="form-row">
    <label for="nome">Nome circuito</label>
    <input type="text" id="nome" name="nome" required maxlength="150"
           value="{$form.nome|default:''|escape}">
</div>
<div class="form-row">
    <label for="localita">Località</label>
    <input type="text" id="localita" name="localita" required maxlength="255"
           placeholder="es. Monza (MB)" value="{$form.localita|default:''|escape}">
</div>
<div class="form-row">
    <label for="indirizzo">Indirizzo / sede</label>
    <input type="text" id="indirizzo" name="indirizzo" maxlength="255"
           placeholder="Via…" value="{$form.indirizzo|default:''|escape}">
</div>
<div class="form-row">
    <label for="lunghezza_km">Lunghezza tracciato (km)</label>
    <input type="number" id="lunghezza_km" name="lunghezza_km" step="0.001" min="0.001" max="100"
           inputmode="decimal" placeholder="es. 5.793" value="{$form.lunghezza_km|default:''|escape}">
</div>
<div class="form-row">
    <label for="tipologia">Tipologia veicoli</label>
    <select id="tipologia" name="tipologia">
        {assign var='tip' value=$form.tipologia|default:'entrambi'}
        <option value="entrambi" {if $tip == 'entrambi'}selected{/if}>Auto e moto</option>
        <option value="auto" {if $tip == 'auto'}selected{/if}>Solo auto</option>
        <option value="moto" {if $tip == 'moto'}selected{/if}>Solo moto</option>
    </select>
</div>
<div class="form-row">
    <label for="numero_box">Numero box</label>
    <input type="number" id="numero_box" name="numero_box" min="1" max="999" step="1" inputmode="numeric"
           value="{$form.numero_box|default:'10'|escape}">
    <p class="text-muted text-small">I piloti verranno assegnati automaticamente ai box in base ai posti per sessione.</p>
</div>
<div class="form-row">
    <label for="telefono">Telefono</label>
    <input type="tel" id="telefono" name="telefono" required maxlength="30"
           inputmode="tel" autocomplete="tel"
           placeholder="es. +39 02 1234567" value="{$form.telefono|default:''|escape}">
</div>
<div class="form-row">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="190"
           inputmode="email" autocomplete="email"
           placeholder="info@circuito.it" value="{$form.email|default:''|escape}">
</div>
<div class="form-row">
    <label for="sito_web">Sito web (opzionale)</label>
    <input type="url" id="sito_web" name="sito_web" maxlength="255"
           placeholder="https://www.circuito.it" value="{$form.sito_web|default:''|escape}">
</div>
<div class="form-row">
    <label for="descrizione">Descrizione</label>
    <textarea id="descrizione" name="descrizione">{$form.descrizione|default:''|escape}</textarea>
</div>
<div class="form-row">
    <label>Foto del circuito</label>
    <div class="notification is-info mb-3">
        {icon name='info'}
        <span>La <strong>prima foto</strong> sarà la <strong>copertina</strong> del circuito: è l'immagine mostrata nelle anteprime dei circuiti e in homepage.</span>
    </div>
    <p class="text-muted text-small">
        Seleziona immagini dal tuo computer (JPG, PNG, GIF o WEBP, max 5&nbsp;MB ciascuna)
        e assegna a ognuna un nome che la descriva.
    </p>
    <div class="tp-foto-uploader" data-foto-uploader data-foto-max="12">
        {if !empty($form.foto_didascalie)}
            {foreach from=$form.foto_didascalie item=dd}
                <div class="tp-foto-upload-row" data-foto-row>
                    <input type="file" name="foto[]" class="input"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <input type="text" name="foto_didascalia[]" class="input" maxlength="255"
                           placeholder="Nome / descrizione immagine" value="{$dd|escape}">
                    <button type="button" class="button is-danger is-small" data-foto-remove>
                        {icon name='trash'} Rimuovi
                    </button>
                </div>
            {/foreach}
        {/if}
    </div>
    <div>
        <button type="button" class="button is-dark is-small" data-foto-add data-no-lock>
            {icon name='plus'} Aggiungi foto
        </button>
    </div>
    <template data-foto-row-template>
        <div class="tp-foto-upload-row" data-foto-row>
            <input type="file" name="foto[]" class="input"
                   accept="image/jpeg,image/png,image/gif,image/webp">
            <input type="text" name="foto_didascalia[]" class="input" maxlength="255"
                   placeholder="Nome / descrizione immagine">
            <button type="button" class="button is-danger is-small" data-foto-remove>
                {icon name='trash'} Rimuovi
            </button>
        </div>
    </template>
</div>
