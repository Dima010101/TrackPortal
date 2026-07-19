{*
  Breadcrumb riutilizzabile (Bulma). Antepone automaticamente la radice
  ("Home" per il pilota e gli ospiti, "Dashboard" per gestori e admin) con
  destinazione in base al ruolo dell'utente loggato. Le voci successive
  arrivano da $crumbs: lista di ['label' => string, 'url' => string?].
  L'ultima voce (o qualunque voce priva di 'url') è la pagina corrente.
  Nessuna icona nel testo.
*}
{assign var='_bc_ruolo' value=$user.ruolo|default:''}
{if $_bc_ruolo == 'pilota'}
    {assign var='_bc_root_label' value='Home'}{assign var='_bc_root_url' value='/dashboard'}
{elseif $_bc_ruolo != ''}
    {assign var='_bc_root_label' value='Dashboard'}{assign var='_bc_root_url' value='/dashboard'}
{else}
    {assign var='_bc_root_label' value='Home'}{assign var='_bc_root_url' value='/'}
{/if}
<nav class="breadcrumb has-arrow-separator mb-4" aria-label="Percorso di navigazione">
    <ul>
        {if empty($crumbs)}
            <li class="is-active"><a href="#" aria-current="page">{$_bc_root_label}</a></li>
        {else}
            <li><a href="{url path=$_bc_root_url}">{$_bc_root_label}</a></li>
            {foreach from=$crumbs item=_cr name=_bc}
                {if $smarty.foreach._bc.last || empty($_cr.url)}
                    <li class="is-active"><a href="#" aria-current="page">{$_cr.label|default:''}</a></li>
                {else}
                    <li><a href="{url path=$_cr.url}">{$_cr.label|default:''}</a></li>
                {/if}
            {/foreach}
        {/if}
    </ul>
</nav>
