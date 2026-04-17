<!-- Lightweight CSS-only tooltip wrapper. Uses Tailwind group-hover, no JS. -->
<script>
  let { text, side = 'right', children } = $props();

  const pos = {
    right: 'left-full top-1/2 -translate-y-1/2 ml-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
  };
</script>

<span class="group relative inline-flex">
  {@render children()}
  {#if text}
    <span
      class="pointer-events-none absolute z-50 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs normal-case tracking-normal text-white
             opacity-0 transition-opacity group-hover:opacity-100 {pos[side]}"
    >
      {text}
    </span>
  {/if}
</span>
