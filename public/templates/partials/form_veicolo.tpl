{* Campi condivisi per inserimento/modifica veicolo di flotta *}
{assign var='cidSel' value=$form.circuito_id|default:0}
<div class="form-row">
    <label for="circuito_id">Circuito</label>
    <select id="circuito_id" name="circuito_id" required>
        <option value="">— Seleziona un circuito —</option>
        {foreach from=$circuiti item=c}
            <option value="{$c.id}" {if $cidSel == $c.id}selected{/if}>
                {$c.nome_circuito|escape}{if !empty($c.localita)} — {$c.localita|escape}{/if}
            </option>
        {/foreach}
    </select>
    <p class="text-muted text-small">Il veicolo sarà noleggiabile sul circuito selezionato.</p>
</div>
<div class="grid grid-2">
    <div class="form-row"><label for="targa">Targa</label>
        <input id="targa" name="targa" required class="input is-uppercase"
               maxlength="10" autocapitalize="characters" autocomplete="off" spellcheck="false"
               pattern="{literal}[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{2}[0-9]{5}{/literal}"
               title="Targa italiana: auto AA000AA oppure moto AA00000."
               placeholder="Es. AB123CD"
               value="{$form.targa|default:''|escape}">
    </div>
    <div class="form-row"><label for="categoria">Categoria</label>
        <select id="categoria" name="categoria">
            <option value="auto" {if $form.categoria|default:'auto' == 'auto'}selected{/if}>Auto</option>
            <option value="moto" {if $form.categoria|default:'' == 'moto'}selected{/if}>Moto</option>
        </select>
    </div>
    <div class="form-row"><label for="marca">Marca</label>
        <input id="marca" name="marca" maxlength="80" value="{$form.marca|default:''|escape}">
    </div>
    <div class="form-row"><label for="modello">Modello</label>
        <input id="modello" name="modello" maxlength="80" value="{$form.modello|default:''|escape}">
    </div>
    <div class="form-row"><label for="anno">Anno</label>
        <input type="number" id="anno" name="anno" min="1900" step="1" inputmode="numeric"
               placeholder="Es. 2022" value="{$form.anno|default:''|escape}">
    </div>
    <div class="form-row"><label for="potenza_cv">Potenza (cv)</label>
        <input type="number" id="potenza_cv" name="potenza_cv" min="0" step="1" inputmode="numeric"
               value="{$form.potenza_cv|default:''|escape}">
    </div>
    <div class="form-row"><label for="capienza">Capienza (posti)</label>
        <input type="number" id="capienza" name="capienza" value="{$form.capienza|default:1}"
               min="1" step="1" inputmode="numeric" required>
    </div>
    <div class="form-row"><label for="disponibile">Disponibilità</label>
        <select id="disponibile" name="disponibile">
            <option value="1" {if $form.disponibile|default:'1' == '1'}selected{/if}>Disponibile</option>
            <option value="0" {if $form.disponibile|default:'' == '0'}selected{/if}>Non disponibile</option>
        </select>
    </div>
</div>
<div class="grid grid-2">
    <div class="form-row">
        <label for="prezzo_giorno">Prezzo noleggio</label>
        <input type="number" step="0.01" min="0.01" inputmode="decimal" id="prezzo_giorno" name="prezzo_giorno"
               placeholder="es. 250.00"
               value="{$form.prezzo_giorno|default:''|escape}" required>
    </div>
    <div class="form-row">
        <label for="prezzo_valuta">Valuta</label>
        {assign var='pvSel' value=$form.prezzo_valuta|default:'EUR'}
        <select id="prezzo_valuta" name="prezzo_valuta">
            {foreach from=$valute|default:['EUR'] item=vcode}
                <option value="{$vcode}" {if $pvSel == $vcode}selected{/if}>{$vcode}</option>
            {/foreach}
        </select>
        <p class="text-muted text-small">Se scegli una valuta diversa dall'euro, l'importo viene
           convertito con i tassi correnti e salvato sempre in EUR.</p>
    </div>
</div>
