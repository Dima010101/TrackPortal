<div class="form-row">
    <label for="titolo">Titolo</label>
    <input type="text" id="titolo" name="titolo" required maxlength="150"
           value="{$form.titolo|default:''|escape}">
</div>
<div class="grid grid-2">
    <div class="form-row">
        <label for="tipo_sconto">Tipo sconto</label>
        {assign var='ts' value=$form.tipo_sconto|default:'percentuale'}
        <select id="tipo_sconto" name="tipo_sconto">
            <option value="percentuale" {if $ts == 'percentuale'}selected{/if}>Percentuale</option>
            <option value="importo" {if $ts == 'importo'}selected{/if}>Importo fisso</option>
        </select>
    </div>
    <div class="form-row">
        <label for="valore">Valore</label>
        <input type="number" id="valore" name="valore" step="0.01" min="0.01" inputmode="decimal" required
               value="{$form.valore|default:''|escape}">
    </div>
    <div class="form-row">
        <label for="data_inizio">Inizio validità (facoltativo)</label>
        <input type="date" id="data_inizio" name="data_inizio" value="{$form.data_inizio|default:''|escape}">
    </div>
    <div class="form-row">
        <label for="data_fine">Fine validità (facoltativo)</label>
        <input type="date" id="data_fine" name="data_fine" value="{$form.data_fine|default:''|escape}">
    </div>
</div>
<div class="form-row">
    <label for="soglia_prenotazioni">Soglia prenotazioni (facoltativa)</label>
    <input type="number" id="soglia_prenotazioni" name="soglia_prenotazioni" min="1" step="1" inputmode="numeric"
           placeholder="Es. 3" value="{$form.soglia_prenotazioni|default:''|escape}">
</div>
<p class="text-muted text-small mb-2">
    Periodo di validità e soglia prenotazioni sono entrambi facoltativi e cumulabili.
</p>
<div class="form-row">
    <label for="descrizione">Descrizione</label>
    <textarea id="descrizione" name="descrizione">{$form.descrizione|default:''|escape}</textarea>
</div>
