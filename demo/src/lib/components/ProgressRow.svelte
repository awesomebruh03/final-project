<script>
	// One row: [dot] label  sub ........ [badge] value
	//          ======== progress bar ========
	const {
		label,
		value = '',
		percent = 0,
		color = '',
		barColor = '',
		sub = '',
		badge = null
	} = $props();
</script>

<div>
	<div class="flex items-center justify-between gap-3">
		<div class="flex min-w-0 items-center gap-2">
			{#if color}
				<span class="size-2.5 shrink-0 rounded-[3px]" style="background: {color}"></span>
			{/if}
			<span class="truncate font-semibold text-ink">{label}</span>
			{#if sub}
				<span class="text-muted">{sub}</span>
			{/if}
		</div>
		<div class="flex shrink-0 items-center gap-2">
			{#if badge}{@render badge()}{/if}
			<span class="text-muted">{value}</span>
		</div>
	</div>
	<div class="mt-1.5 h-2 rounded-full bg-stone-200/70">
		<div
			class="h-2 rounded-full"
			style="width: {Math.min(100, Math.max(percent, 0))}%; background: {barColor ||
				color ||
				'#0f766e'}"
		></div>
	</div>
</div>
