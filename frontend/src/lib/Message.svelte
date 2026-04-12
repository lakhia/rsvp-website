<script>
	let { msg = '', msgType = 'success' } = $props();
</script>

<!-- Fixed height so surrounding buttons don't shift when message appears/disappears -->
<div class="mt-3 h-5 text-center text-sm">
	{#if msg}
		<span class={msgType === 'error' ? 'text-no-dark' : 'text-yes-dark'}>
			{msg}
		</span>
	{/if}
</div>
