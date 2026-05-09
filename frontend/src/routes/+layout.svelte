<script>
  import '../app.css';
  import { page } from '$app/state';
  import { isLoggedIn, isAdmin, logout } from '$lib/auth.js';
  import { navigate } from '$lib/api.js';
  import Icon from '$lib/Icon.svelte';
  import Tooltip from '$lib/Tooltip.svelte';

  let { children } = $props();
  let menuBig = $state(false);
  let admin = $state(false);
  let loggedIn = $state(false);

  const ALL_NAV = [
    { href: '/', label: 'RSVP', icon: 'home', adminOnly: false },
    { href: '/print', label: 'Filling', icon: 'printer', adminOnly: false },
    { href: '/plan', label: 'Plan', icon: 'calendar', adminOnly: false },
    { href: '/shop', label: 'Shopping', icon: 'cart', adminOnly: false },
    { href: '/family', label: 'Family', icon: 'users', adminOnly: true },
    { href: '/event', label: 'Events', icon: 'fork-knife', adminOnly: true },
    { href: '/measure', label: 'Measure', icon: 'scale', adminOnly: true },
    { href: '/ingred', label: 'Ingredients', icon: 'list', adminOnly: true },
    { onclick: handleLogout, label: 'Logout', icon: 'logout', adminOnly: false },
  ];

  const navItems = $derived(ALL_NAV.filter((n) => !n.adminOnly || admin));

  $effect(() => {
    const _ = page.url.pathname;
    loggedIn = isLoggedIn();
    admin = isAdmin();
    menuBig = localStorage.getItem('menuBig') === '1';
    if (page.route.id !== '/login' && !loggedIn) {
      navigate('/login');
    }
  });

  function menuToggle() {
    menuBig = !menuBig;
    localStorage.setItem('menuBig', menuBig ? '1' : '0');
  }

  function handleLogout() {
    logout();
    navigate('/login');
  }

  const isLogin = $derived(page.route.id === '/login');
  const activeRoute = $derived(page.route.id);
</script>

{#if isLogin}
  {@render children()}
{:else}
  <nav
    class="fixed left-0 top-0 bottom-0 flex flex-col z-10 transition-all duration-200"
    style="width: {menuBig ? '140px' : '38px'}; background: var(--surface); border-right: 1px solid var(--border);"
  >
    <!-- Toggle / app name -->
    <button
      onclick={menuToggle}
      aria-label={menuBig ? 'Collapse menu' : 'Expand menu'}
      aria-expanded={menuBig}
      class="nav-toggle flex gap-2 px-2 py-3 transition-opacity hover:opacity-90"
    >
      <span class="text-lg leading-none shrink-0">&#9776;</span>
      {#if menuBig}
        <span class="whitespace-nowrap text-sm font-bold">{__APP_NAME__}</span>
      {/if}
    </button>

    <!-- Nav links -->
    {#each navItems as item}
      {@const active = !item.onclick && activeRoute === item.href}
      <Tooltip text={menuBig ? '' : item.label} side="right">
        <button
          onclick={item.onclick ?? (() => navigate(item.href))}
          aria-label={item.label}
          aria-current={active ? 'page' : undefined}
          class="nav-link w-full flex gap-2 px-2 py-3 transition-colors"
        >
          <span class="shrink-0"><Icon name={item.icon} size={20} /></span>
          {#if menuBig}<span class="whitespace-nowrap text-sm">{item.label}</span>{/if}
        </button>
      </Tooltip>
    {/each}
  </nav>

  <main
    class="transition-all duration-200 py-3 pr-3 overflow-x-hidden"
    style="margin-left: {menuBig ? '140px' : '56px'}; min-height: 100vh; background: var(--bg);"
  >
    {@render children()}
    <footer class="mt-8 pt-4 text-xs" style="border-top: 1px solid var(--border); color: var(--muted);">
      Please provide <a href={__LINK_FEEDBACK__} class="text-brand">thaali feedback</a>.
      Contact us via <a href="mailto:{__EMAIL_CONTACT__}" class="text-brand">email</a>.
    </footer>
  </main>
{/if}

<style>
  .nav-toggle {
    min-height: 44px;
    background: var(--accent);
    color: white;
  }

  .nav-link { color: var(--muted); }
  .nav-link[aria-current="page"] { color: var(--accent); pointer-events: none; }

  @media print {
    nav { display: none !important; }
    main { margin-left: 0 !important; }
  }
</style>
