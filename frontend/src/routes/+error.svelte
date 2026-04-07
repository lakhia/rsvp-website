<script>
	import { page } from '$app/state';
</script>

<svelte:head>
	<title>{__APP_NAME__} - Error</title>
</svelte:head>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
	<p class="text-6xl font-bold text-gray-200 mb-4">{page.status}</p>
	<p class="text-lg font-semibold text-gray-700 mb-2">
		{page.status === 404 ? 'Page not found' : 'Something went wrong'}
	</p>
	<p class="text-sm text-gray-500 mb-6">{page.error?.message ?? ''}</p>
	<a href="/" class="px-4 py-2 text-sm rounded text-white bg-brand hover:bg-brand-dark transition-colors">
		Go home
	</a>
</div>
