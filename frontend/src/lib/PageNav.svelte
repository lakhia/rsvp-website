<script>
  let {
    prevSteps = [],
    nextSteps = [],
    onSave = null,
    dirty = false,
    saving = false,
    prevDisabled = false,
    nextDisabled = false,
    class: klass = '',
    children,
  } = $props();
</script>

<div class="mt-4 flex justify-center items-center gap-2 {klass}">
  {#each prevSteps as step, i}
    <button onclick={step.onClick} disabled={prevDisabled} class="btn-secondary">
      {i === 0 && prevSteps.length > 1 ? '«' : '‹'}
      {step.label}
    </button>
  {/each}
  {#if children}{@render children()}{/if}
  {#if onSave}
    <button onclick={onSave} disabled={!dirty || saving} class="btn-primary min-w-22">
      {saving ? 'Saving…' : 'Save'}
    </button>
  {/if}
  {#each nextSteps as step, i}
    <button onclick={step.onClick} disabled={nextDisabled} class="btn-secondary">
      {step.label}
      {i === nextSteps.length - 1 && nextSteps.length > 1 ? '»' : '›'}
    </button>
  {/each}
</div>
