{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Circuito aggiunto']]}
    {include file='partials/breadcrumb.tpl'}

  <div class="page-header">
    <div>
      <h1>Circuito aggiunto</h1>
      <p class="lead">Il nuovo tracciato è stato registrato correttamente.</p>
    </div>
  </div>

  <div class="notification is-success">
    {icon name='check'}
    <strong>Conferma aggiunta:</strong>
    il circuito <em>{$circuito.nome_circuito|default:''|escape}</em>
    {if !empty($circuito.localita)} a {$circuito.localita|escape}{/if}
    è ora associato al tuo profilo gestore.
  </div>

  <div class="card">
    <h3 class="card-title">Riepilogo</h3>
    <dl class="tp-dl">
      <dt>Nome</dt>
      <dd>{$circuito.nome_circuito|default:''|escape}</dd>
      <dt>Località</dt>
      <dd>{$circuito.localita|default:''|escape}</dd>
      {if !empty($circuito.lunghezza_km)}
        <dt>Lunghezza</dt>
        <dd>{$circuito.lunghezza_km|escape} km</dd>
      {/if}
      <dt>Tipologia veicoli</dt>
      <dd>{tp_ucfirst s=$circuito.tipologia_veicoli|default:''}</dd>
      <dt>Box</dt>
      <dd>{$circuito.numero_box|default:0}</dd>
      <dt>Foto</dt>
      <dd>{if !empty($circuito.foto)}{$circuito.foto|@count} caricate{else}Nessuna foto{/if}</dd>
      {if !empty($circuito.telefono)}
        <dt>Telefono</dt>
        <dd>{$circuito.telefono|escape}</dd>
      {/if}
      {if !empty($circuito.email)}
        <dt>Email</dt>
        <dd><a href="mailto:{$circuito.email|escape}">{$circuito.email|escape}</a></dd>
      {/if}
      {if !empty($circuito.sito_web)}
        <dt>Sito web</dt>
        <dd><a href="{$circuito.sito_web|escape}" target="_blank" rel="noopener">{$circuito.sito_web|escape}</a></dd>
      {/if}
    </dl>
    <div class="flex mt-2">
      <a class="button is-primary" href="{url path='/circuitiGestore'}">
        {icon name='flag'} Vai ai miei circuiti
      </a>
      {assign var='cid' value=$circuito.id|default:0}
      {if $cid > 0}
        <a class="button is-dark" href="{url path="/calendario?circuito_id={$cid}"}">
          {icon name='calendar'} Apri calendario
        </a>
        <a class="button is-dark" href="{url path="/circuitiGestore/{$cid}/modifica"}">
          {icon name='edit'} Modifica circuito
        </a>
      {/if}
      <a class="button is-dark" href="{url path='/circuitiGestore/nuovo'}">
        {icon name='plus'} Aggiungi un altro
      </a>
    </div>
  </div>
</div>
{/block}
