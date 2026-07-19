<div class="confirm-panel card {$confirm_panel_class|default:''}">
    {if $confirm_eyebrow|default:''}
        <p class="confirm-panel-eyebrow">{$confirm_eyebrow|escape}</p>
    {/if}
    <div class="confirm-panel-icon">{icon name='alert' size=28}</div>
    <h3 class="confirm-panel-title">{$confirm_title|default:'Conferma'}</h3>
    <p class="confirm-panel-message">{$confirm_message|default:''}</p>
    {if $confirm_highlight|default:''}
        <div class="confirm-panel-highlight">
            {if $confirm_highlight_icon|default:''}{icon name=$confirm_highlight_icon size=18 class='confirm-panel-highlight-icon'}{/if}
            {* Markup di sola presentazione costruito dal chiamante (può contenere importi
               convertibili): nessun dato utente, output non filtrato. *}
            <span>{$confirm_highlight nofilter}</span>
        </div>
    {/if}
    {if $confirm_extra|default:''}{$confirm_extra nofilter}{/if}
    <form method="post"{if $confirm_form_action|default:''} action="{$confirm_form_action|escape}"{/if} class="form-stack confirm-panel-form">
        {csrf_field}
        <input type="hidden" name="azione" value="{$confirm_action|default:'elimina'}">
        {if isset($confirm_item_id) && $confirm_item_id !== ''}
            <input type="hidden" name="id" value="{$confirm_item_id}">
        {/if}
        {if $confirm_show_causa|default:false}
            <div class="form-row">
                <label>Causa cancellazione</label>
                <input type="text" name="causa" placeholder="Motivo della cancellazione" required
                       maxlength="255" value="{$confirm_causa_value|default:''|escape}">
            </div>
        {/if}
        <div class="confirm-panel-actions btn-group">
            <a href="{$confirm_cancel_url|default:'#'}" class="button is-dark">
                {$confirm_cancel_label|default:'Annulla'}
            </a>
            <button type="submit" class="button is-danger confirm-panel-submit">
                {$confirm_ok_label|default:'Conferma'}
            </button>
        </div>
    </form>
</div>
