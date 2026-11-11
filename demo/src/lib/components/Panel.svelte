<script>
	// A white card with a title on the left and a small muted hint on the right.
	const { title, hint = '', class: extra = '', children } = $props();
</script>

<section class="card p-6 {extra}">
	{#if title}
		<div class="mb-4 flex items-baseline justify-between gap-3">
			<h2 class="text-xl font-bold tracking-tight text-ink">{title}</h2>
			{#if hint}
				<span class="text-sm text-muted">{hint}</span>
			{/if}
		</div>
	{/if}
	{@render children()}
</section>
