<script>
	import Badge from './Badge.svelte';
	import { formatMoney, formatTime } from '../format.js';

	// One expense in the list.
	const { expense, color, deleting = false, onedit, ondelete } = $props();
</script>

<li class="flex items-center gap-4 rounded-2xl border border-line/80 bg-white p-3.5">
	<!-- First letter of the category on the category colour -->
	<span
		class="grid size-12 shrink-0 place-items-center rounded-xl text-lg font-bold text-white"
		style="background: {color}"
	>
		{expense.category.charAt(0).toUpperCase()}
	</span>

	<div class="min-w-0 flex-1">
		{#if expense.note}
			<p class="truncate font-semibold text-ink">{expense.note}</p>
		{:else}
			<p class="truncate text-muted italic">No note</p>
		{/if}
		<div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
			<Badge tone="teal">{expense.category}</Badge>
			{#if expense.time}<span>{formatTime(expense.time)}</span>{/if}
			{#if expense.recurring}<Badge tone="blue">Recurring</Badge>{/if}
		</div>
	</div>

	<span class="text-lg font-bold text-ink">{formatMoney(expense.amount)}</span>

	<button
		type="button"
		onclick={onedit}
		aria-label="Edit expense"
		class="grid size-10 shrink-0 cursor-pointer place-items-center rounded-xl border border-line text-ink transition hover:bg-stone-50"
	>
		<svg viewBox="0 0 24 24" class="size-4.5" fill="none" aria-hidden="true">
			<path
				d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17v3zM14 7l3 3"
				stroke="currentColor"
				stroke-width="1.8"
				stroke-linecap="round"
				stroke-linejoin="round"
			/>
		</svg>
	</button>

	<button
		type="button"
		onclick={ondelete}
		disabled={deleting}
		aria-label="Delete expense"
		class="grid size-10 shrink-0 cursor-pointer place-items-center rounded-xl border border-red-200 text-red-600 transition hover:bg-red-50 disabled:opacity-50"
	>
		<svg viewBox="0 0 24 24" class="size-4.5" fill="none" aria-hidden="true">
			<path
				d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"
				stroke="currentColor"
				stroke-width="1.8"
				stroke-linecap="round"
				stroke-linejoin="round"
			/>
		</svg>
	</button>
</li>
