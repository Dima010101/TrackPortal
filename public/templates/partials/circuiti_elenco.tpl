{* Elenco circuiti riutilizzabile: barra di ricerca live (client-side, opzionale)
   + griglia di card. Richiede: $circuiti (list). Il filtro è gestito da main.js
   via #live-search. Passa mostra_ricerca=false per nascondere la ricerca
   (es. vetrina "I più popolari" in home). *}
{if !isset($mostra_ricerca) || $mostra_ricerca}
<form method="get" class="search-bar is-wide mb-5" role="search">
    <label for="live-search" class="sr-only">Cerca circuito</label>
    <input id="live-search" class="input" type="search" name="q" value="{$q|default:''}"
           placeholder="Trova il circuito più adatto a te" autocomplete="off">
    <button type="submit" class="button is-primary" aria-label="Cerca">{icon name='search'}</button>
</form>
{/if}

{if $circuiti|@count == 0}
    <div class="empty-state">
        {icon name='search' size=56}
        <h3>Nessun circuito disponibile</h3>
        <p>Al momento non ci sono autodromi pubblicati sulla piattaforma.</p>
    </div>
{else}
    <div class="grid-cards">
        {foreach from=$circuiti item=c}
            {assign var='cid' value=$c.id|default:0}
            <a class="circuit-card"
               data-search-target="{$c.nome_circuito|default:''} {$c.indirizzo|default:''|trim}"
               href="{url path="/circuiti/{$cid}"}">
                <div class="cc-img">
                    {if !empty($c.copertina)}
                        <img src="{url path=$c.copertina}" alt="{$c.nome_circuito|default:''|escape}" loading="lazy">
                    {else}
                        <span>{$c.nome_circuito|default:''}</span>
                    {/if}
                </div>
                <div class="cc-body">
                    <h3>{$c.nome_circuito|default:''}</h3>
                    <div class="cc-meta">
                        {icon name='home' size=12} {$c.indirizzo|default:''}
                    </div>
                    <div class="cc-meta">
                        {icon name='car' size=12} {tp_ucfirst s=$c.tipologia_veicoli|default:''}
                        · {$c.numero_box|default:0} box
                        · {$c.veicoli_noleggio|default:0} veicoli
                    </div>
                </div>
            </a>
        {/foreach}
    </div>
    {if !isset($mostra_ricerca) || $mostra_ricerca}
    <div id="live-search-empty" class="empty-state mt-3 tp-hidden">
        {icon name='search' size=56}
        <h3>Nessun risultato</h3>
        <p>Prova a cancellare il filtro o cerca un altro termine.</p>
    </div>
    {/if}
{/if}
