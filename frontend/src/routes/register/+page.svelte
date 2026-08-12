<script>
  import { post, navigate } from '$lib/api.js';
  import Message from '$lib/Message.svelte';

  let firstName     = $state('');
  let lastName      = $state('');
  let email         = $state('');
  let phone         = $state('');
  let its           = $state('');
  let area          = $state('');
  let householdSize = $state('');
  let msg           = $state('');
  let loading   = $state(false);
  let thaaliNumber = $state(null);
  let greet = $state('');

  async function handleSubmit(e) {
    e.preventDefault();
    msg = '';
    if (!/^[0-9]{8}$/.test(its)) {
      msg = 'ITS number must be exactly 8 digits';
      return;
    }
    if (phone.trim() === '') {
      msg = 'A WhatsApp phone number is required';
      return;
    }
    const phoneDigits = phone.replace(/[\s\-().+]/g, '');
    if (!/^\d{7,15}$/.test(phoneDigits)) {
      msg = 'Please enter a valid phone number (7–15 digits)';
      return;
    }
    loading = true;
    try {
      const res = await post('register.php', {}, { firstName, lastName, email, phone, its, area, householdSize });
      if (res.data && !res.msg) {
        localStorage.setItem('greet', res.data.greet);
        greet = res.data.greet;
        thaaliNumber = res.data.thaali;
      } else {
        msg = res.msg || 'Registration failed';
      }
    } catch (e) {
      msg = e.message || 'Request failed, try again';
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - Register</title>
</svelte:head>

<div class="min-h-screen flex flex-col items-center justify-center bg-subtle py-8">
  <div class="w-full max-w-sm bg-surface rounded-lg shadow overflow-hidden" style="border: 1px solid var(--border);">
  {#if thaaliNumber !== null}
    <div class="p-8 text-center">
      <h2 class="text-xl mb-4">Registration Successful</h2>
      <p class="text-sm mb-2">Your thaali number is:</p>
      <p class="text-5xl font-bold mb-4" style="color: var(--brand);">{thaaliNumber}</p>
      {@html __WELCOME_MESSAGE__}
      <div class="flex flex-col gap-2">
        <button
          onclick={() => navigate('/')}
          class="w-full bg-brand hover:bg-brand-dark text-white text-sm font-medium py-2 rounded transition-colors"
        >
          Go to RSVP
        </button>
        <button
          onclick={() => navigate('/login')}
          class="w-full text-sm font-medium py-2 rounded transition-colors"
          style="color: var(--brand);"
        >
          Go to Login
        </button>
      </div>
    </div>
  {:else}
    <div class="p-8">
      <h2 class="text-xl mb-6 text-center">Create Account</h2>

      <form onsubmit={handleSubmit} class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
          <label for="firstName">First Name *</label>
          <input
            id="firstName"
            type="text"
            bind:value={firstName}
            placeholder="First name"
            required
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="lastName">Last Name *</label>
          <input
            id="lastName"
            type="text"
            bind:value={lastName}
            placeholder="Last name"
            required
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="email">Email *</label>
          <input
            id="email"
            type="email"
            bind:value={email}
            placeholder="Enter email"
            required
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="its">HOF ITS Number *</label>
          <input
            id="its"
            type="text"
            bind:value={its}
            placeholder="Enter ITS number"
            required
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="phone">WhatsApp Phone *</label>
          <input
            id="phone"
            type="tel"
            bind:value={phone}
            placeholder="e.g. +1 555 123 4567"
            required
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="householdSize">Number of people in your household</label>
          <input
            id="householdSize"
            type="number"
            min="1"
            bind:value={householdSize}
            placeholder="Optional"
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="area">Area</label>
          <input
            id="area"
            type="text"
            bind:value={area}
            placeholder="Enter host area"
            class="border border-line rounded px-3 py-2 text-sm focus:outline-none focus:border-brand transition-colors"
          />
        </div>

        <Message {msg} msgType="error" />

        <button
          type="submit"
          disabled={loading}
          class="mt-2 bg-brand hover:bg-brand-dark text-white text-sm font-medium py-2 rounded transition-colors"
        >
          {loading ? 'Registering…' : 'Register'}
        </button>

        <p class="text-center text-sm">
          Already have an account?
          <a href="{__BASE_PATH__}/login" class="hover:underline" style="color: var(--brand);">Sign in</a>
        </p>
      </form>
    </div>
  {/if}
  </div>
</div>