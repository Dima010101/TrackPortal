{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='flag' size=13} TrackPortal</p>
            <h1>Prenota il tuo tempo in pista</h1>
            <p class="lead">Scegli l'autodromo, trova il mezzo. Corri</p>
        </div>
    </section>

    <section id="circuiti" class="mt-5">
        <div class="page-header">
            <div>
                <h2 class="section-title">Circuiti</h2>
                <p class="lead">Sfoglia tutti gli autodromi disponibili sulla piattaforma.</p>
            </div>
        </div>
        {include file='partials/circuiti_elenco.tpl'}
    </section>
</div>
{/block}
