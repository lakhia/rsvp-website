<script>
  import '../app.css';
  import { goto } from '$app/navigation';
  import { page } from '$app/state';
  import { isLoggedIn, isAdmin, logout } from '$lib/auth.js';
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
    { href: '/event', label: 'Events', icon: 'utensils', adminOnly: true },
    { href: '/measure', label: 'Measure', icon: 'scale', adminOnly: true },
    { onclick: handleLogout, label: 'Logout', icon: 'logout', adminOnly: false },
  ];

  const navItems = $derived(ALL_NAV.filter((n) => !n.adminOnly || admin));

  $effect(() => {
    // Re-read cookies on every navigation so admin state is always current
    const _ = page.url.pathname;
    loggedIn = isLoggedIn();
    admin = isAdmin();
    menuBig = localStorage.getItem('menuBig') === '1';
    if (page.route.id !== '/login' && !loggedIn) {
      goto(__BASE_PATH__ + '/login');
    }
  });

  function menuToggle() {
    menuBig = !menuBig;
    localStorage.setItem('menuBig', menuBig ? '1' : '0');
  }

  function handleLogout() {
    logout();
    goto(__BASE_PATH__ + '/login');
  }

  const isLogin = $derived(page.route.id === '/login');
  const activeRoute = $derived(page.route.id);
</script>

{#if isLogin}
  {@render children()}
{:else}
  <nav
    class="fixed left-0 top-0 bottom-0 flex flex-col bg-gray-100 z-10 transition-all duration-200"
    style="width: {menuBig ? '140px' : '40px'}"
  >
    <!-- Toggle / app name -->
    <button
      onclick={menuToggle}
      aria-label={menuBig ? 'Collapse menu' : 'Expand menu'}
      aria-expanded={menuBig}
      class="flex gap-2 px-2 py-3 bg-brand text-white hover:bg-brand-dark transition-colors"
      style="min-height: 44px;"
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
          onclick={item.onclick ?? (() => goto(__BASE_PATH__ + item.href))}
          aria-label={item.label}
          aria-current={active ? 'page' : undefined}
          class="w-full flex gap-2 px-2 py-3 transition-colors
            {active ? 'text-gray-400 pointer-events-none' : 'text-gray-600 hover:bg-gray-200'}"
        >
          <span class="shrink-0"><Icon name={item.icon} size={20} /></span>
          {#if menuBig}<span class="whitespace-nowrap text-sm">{item.label}</span>{/if}
        </button>
      </Tooltip>
    {/each}
  </nav>

  <main
    class="transition-all duration-200 p-3 overflow-x-hidden"
    style="margin-left: {menuBig ? '140px' : '40px'}"
  >
    {@render children()}
    <footer class="mt-8 pt-4 border-t border-gray-200 text-xs text-gray-400">
      Please provide <a href={__LINK_FEEDBACK__} class="underline"
        >thaali feedback</a
      >. Contact us via
      <a href="mailto:{__EMAIL_CONTACT__}" class="underline">email</a>.
    </footer>
  </main>
{/if}

<style>
  @media print {
    nav {
      display: none !important;
    }
    main {
      margin-left: 0 !important;
    }
  }
</style>
