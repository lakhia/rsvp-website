<script>
	import { post } from '$lib/api.js';

	let email = $state('');
	let pass = $state('');
	let msg = $state('');
	let loading = $state(false);

	async function handleSubmit(e) {
		e.preventDefault();
		msg = '';
		loading = true;
		try {
			const res = await post('login.php', {}, { email, pass });
			if (res.data && !res.msg) {
				localStorage.setItem('greet', res.data);
				window.location.href = '/';
			} else {
				msg = res.msg || 'Login failed';
			}
		} catch {
			msg = 'Request failed, try again';
		} finally {
			loading = false;
		}
	}
</script>

<svelte:head>
	<title>{__APP_NAME__} - Login</title>
</svelte:head>

<div class="min-h-screen flex items-center justify-center bg-gray-50">
	<div class="w-full max-w-sm bg-white rounded-lg shadow p-8">
		<h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">Enter your credentials</h2>

		<form onsubmit={handleSubmit} class="flex flex-col gap-4">
			<div class="flex flex-col gap-1">
				<label for="email" class="text-sm text-gray-600">Email</label>
				<input
					id="email"
					type="email"
					bind:value={email}
					placeholder="Enter email"
					required
					class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
				/>
			</div>

			<div class="flex flex-col gap-1">
				<label for="pass" class="text-sm text-gray-600">Password</label>
				<input
					id="pass"
					type="password"
					bind:value={pass}
					placeholder="Enter thaali number"
					required
					class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
				/>
			</div>

			{#if msg}
				<p class="text-center text-sm text-red-600">{msg}</p>
			{/if}

			<button
				type="submit"
				disabled={loading}
				class="mt-2 bg-brand hover:bg-brand-dark disabled:opacity-50 text-white text-sm font-medium py-2 rounded transition-colors"
			>
				{loading ? 'Signing in…' : 'Next'}
			</button>
		</form>
	</div>
</div>
