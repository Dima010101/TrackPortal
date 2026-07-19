<!DOCTYPE html>
<html lang="it" class="has-navbar-fixed-top" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="description" content="{$page_description|default:''}">
    <meta name="robots" content="index,follow">
    <title>{$page_title|default:''} · {$app_name|default:''}</title>

    <link rel="icon" type="image/svg+xml" href="{$logo_url}">

    <link rel="stylesheet" href="{url path='/public/css/bulma.min.css'}?v={$bulma_css_version}">
    <link rel="stylesheet" href="{url path='/public/css/style.css'}?v={$css_version}">
</head>
<body>
<nav class="navbar is-fixed-top tp-navbar" role="navigation" aria-label="Navigazione principale">
    <div class="container">
        <div class="navbar-brand">
            <a class="navbar-item tp-brand" href="{url path='/'}">
                {logo}
                <span class="has-text-weight-bold">{$app_name}</span>
            </a>
        </div>

        <div id="main-nav" class="navbar-menu">
            <div class="navbar-start">
                {if $user}
                    {assign var='ruolo' value=$user.ruolo|default:''}
                    {if $ruolo == 'pilota'}
                        <a href="{url path='/dashboard'}" class="navbar-item {nav_active path='/dashboard'}">{icon name='home' class='tp-nav-icon'}<span>Home</span></a>
                        <a href="{url path='/circuiti'}" class="navbar-item {nav_active path='/circuiti'}">{icon name='flag' class='tp-nav-icon'}<span>Tutti i circuiti</span></a>
                        <a href="{url path='/prenotazioni'}" class="navbar-item {nav_active path='/prenotazioni'}">{icon name='history' class='tp-nav-icon'}<span>Prenotazioni</span></a>
                    {elseif $ruolo == 'gestore_circuiti'}
                        <a href="{url path='/dashboard'}" class="navbar-item {nav_active path='/dashboard'}">{icon name='dashboard' class='tp-nav-icon'}<span>Dashboard</span></a>
                        <a href="{url path='/circuitiGestore'}" class="navbar-item {nav_active path='/circuitiGestore'}">{icon name='flag' class='tp-nav-icon'}<span>I miei circuiti</span></a>
                        <a href="{url path='/sanzioniGestore'}" class="navbar-item {nav_active path='/sanzioniGestore'}">{icon name='users' class='tp-nav-icon'}<span>Sanzioni</span></a>
                        <a href="{url path='/prenotazioni/storico'}" class="navbar-item {nav_active path='/prenotazioni/storico'}">{icon name='history' class='tp-nav-icon'}<span>Storico</span></a>
                        <a href="{url path='/promozioni'}" class="navbar-item {nav_active path='/promozioni'}">{icon name='tag' class='tp-nav-icon'}<span>Promozioni</span></a>
                    {elseif $ruolo == 'gestore_noleggio'}
                        <a href="{url path='/dashboard'}" class="navbar-item {nav_active path='/dashboard'}">{icon name='dashboard' class='tp-nav-icon'}<span>Dashboard</span></a>
                        <a href="{url path='/flotta'}" class="navbar-item {nav_active path='/flotta'}">{icon name='car' class='tp-nav-icon'}<span>Flotta</span></a>
                        <a href="{url path='/sanzioniNoleggio'}" class="navbar-item {nav_active path='/sanzioniNoleggio'}">{icon name='users' class='tp-nav-icon'}<span>Sanzioni</span></a>
                        <a href="{url path='/prenotazioni/storico'}" class="navbar-item {nav_active path='/prenotazioni/storico'}">{icon name='history' class='tp-nav-icon'}<span>Storico</span></a>
                        <a href="{url path='/promozioni'}" class="navbar-item {nav_active path='/promozioni'}">{icon name='tag' class='tp-nav-icon'}<span>Promozioni</span></a>
                    {elseif $ruolo == 'admin'}
                        <a href="{url path='/dashboard'}" class="navbar-item {nav_active path='/dashboard'}">{icon name='dashboard' class='tp-nav-icon'}<span>Dashboard</span></a>
                        <a href="{url path='/commissioni'}" class="navbar-item {nav_active path='/commissioni'}">{icon name='gauge' class='tp-nav-icon'}<span>Commissioni</span></a>
                        <a href="{url path='/affiliazioni'}" class="navbar-item {nav_active path='/affiliazioni'}">{icon name='building' class='tp-nav-icon'}<span>Convalida</span></a>
                        <a href="{url path='/fatture'}" class="navbar-item {nav_active path='/fatture'}">{icon name='receipt' class='tp-nav-icon'}<span>Fatturazione</span></a>
                    {/if}
                {else}
                    <a href="{url path='/circuiti'}" class="navbar-item {nav_active path='/circuiti'}">{icon name='flag' class='tp-nav-icon'}<span>Tutti i circuiti</span></a>
                {/if}
            </div>

            <div class="navbar-end">
                {if $user}
                    <div class="navbar-item has-dropdown tp-user-menu" id="user-menu">
                        <button type="button" class="navbar-link tp-user-trigger is-arrowless" id="user-trigger"
                                aria-haspopup="true" aria-expanded="false" aria-controls="user-dropdown">
                            <span class="tp-avatar role-{$user.ruolo|default:''}">{$user_initials}</span>
                            <span>{$user.nome|default:''}</span>
                            {icon name='arrow-down' class='tp-chevron'}
                        </button>
                        <div class="navbar-dropdown is-right" id="user-dropdown" role="menu">
                            <div class="tp-dropdown-header">
                                <strong>{$user.nome|default:''} {$user.cognome|default:''}</strong>
                                <span>{ruolo_label ruolo=$user.ruolo|default:''}</span>
                            </div>
                            <a href="{url path='/account'}" class="navbar-item" role="menuitem">
                                {icon name='user'} Il mio account
                            </a>
                            <hr class="navbar-divider">
                            <form action="{url path='/auth/logout'}" method="post" class="px-4 py-2">
                                {csrf_field}
                                <button type="submit" class="button is-danger is-small is-fullwidth is-light" role="menuitem">
                                    {icon name='logout'} Esci
                                </button>
                            </form>
                        </div>
                    </div>
                {else}
                    <div class="navbar-item">
                        <div class="buttons">
                            <a href="{url path='/auth/accesso'}" class="button is-ghost {nav_active path='/auth/accesso'}">{icon name='login'}<span>Accedi</span></a>
                            <a class="button is-primary {nav_active path='/auth/registrazione'}"
                               href="{url path='/auth/registrazione'}">Registrati</a>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</nav>

{if !empty($flash_messages)}
<div class="container pt-4">
    {foreach from=$flash_messages item=msg}
        {assign var='t' value=$msg.type|default:'info'}
        {assign var='bulma' value=($t == 'error') ? 'is-danger' : (($t == 'warn') ? 'is-warning' : (($t == 'ok') ? 'is-success' : 'is-info'))}
        <div class="notification {$bulma} tp-flash mb-3" role="alert">
            {flash_icon type=$t}
            <span>{$msg.message|default:''}</span>
        </div>
    {/foreach}
</div>
{/if}

<main class="section tp-page{if !empty($page_main_class)} {$page_main_class}{/if}" id="main-content">
