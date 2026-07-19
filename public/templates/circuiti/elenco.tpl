{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    {assign var='crumbs' value=[['label'=>'Tutti i circuiti']]}
    {include file='partials/breadcrumb.tpl'}

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='flag' size=13} Circuiti</p>
            <h1>Tutti i circuiti</h1>
            <p class="lead">Sfoglia tutti gli autodromi disponibili e apri la scheda del circuito.</p>
        </div>
    </section>

    <section id="circuiti" class="mt-5">
        {include file='partials/circuiti_elenco.tpl'}
    </section>
</div>
{/block}
