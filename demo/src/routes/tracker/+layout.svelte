<script>
	import Sidebar from '#lib/components/Sidebar.svelte';
	import { auth } from '#lib/auth.svelte.js';
	import { data, loadAll } from '#lib/data.svelte.js';
	import { view, prevMonth, nextMonth, canGoNext } from '#lib/view.svelte.js';
	import { monthLabel } from '#lib/format.js';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { resolve } from '$app/paths';

	const { children } = $props();

	// Page title + subtitle depend on which page we are on.
	/** @type {Record<string, { title: string, about: string }>} */
	const pages = {
		'/tracker': { title: 'Overview', about: 'Spending summary for {month}' },
		'/tracker/categories': { title: 'Categories', about: 'How your spending is split' },
		'/tracker/insights': { title: 'Insights', about: 'Categories, notes and habits' },
		'/tracker/budgets': { title: 'Budgets', about: 'Limits and how you are tracking' },
		'/tracker/expenses': { title: 'Expenses', about: 'Every expense for {month}' }
	};
	const current = $derived(pages[page.url.pathname.replace(/\/$/, '')] ?? pages['/tracker']);
	const subtitle = $derived(current.about.replace('{month}', monthLabel(view.month)));

	// Runs whenever auth.user changes:
	// - logged in -> load everything from the backend
	// - logged out (or token expired) -> back to the auth page
	$effect(() => {
		if (auth.user) {
			loadAll();
		} else {
			goto(resolve('/auth'));
		}
	});

	const controlClass =
		'rounded-xl border border-line bg-white font-semibold text-ink shadow-sm transition hover:bg-stone-50';
</script>

{#if auth.user}
	<div class="min-h-screen bg-canvas md:flex">
		<Sidebar />

		<div class="min-w-0 flex-1 px-5 py-6 sm:px-8 sm:py-8">
			<header class="mb-6 flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-3xl font-extrabold tracking-tight text-ink">{current.title}</h1>
					<p class="mt-1 text-muted">{subtitle}</p>
				</div>

				<div class="flex flex-wrap items-center gap-3">
					<!-- Month stepper -->
					<div class="flex items-center {controlClass}">
						<button
							type="button"
							onclick={prevMonth}
							aria-label="Previous month"
							class="cursor-pointer px-4 py-2.5"
						>
							‹
						</button>
						<span class="min-w-36 text-center text-sm">{monthLabel(view.month)}</span>
						<button
							type="button"
							onclick={nextMonth}
							disabled={!canGoNext()}
							aria-label="Next month"
							class="cursor-pointer px-4 py-2.5 disabled:cursor-default disabled:opacity-30"
						>
							›
						</button>
					</div>

					<!-- How many months the trend charts show -->
					<select
						aria-label="Trend range"
						value={view.range}
						onchange={(event) => (view.range = Number(event.currentTarget.value))}
						class="cursor-pointer py-2.5 pr-9 pl-4 text-sm {controlClass}"
					>
						<option value={3}>Last 3 months</option>
						<option value={6}>Last 6 months</option>
						<option value={12}>Last 12 months</option>
					</select>

					<a
						href={resolve('/tracker/expenses?add=1')}
						class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700"
					>
						+ Add expense
					</a>
				</div>
			</header>

			{#if data.error}
				<div class="mb-5 flex items-center justify-between gap-3 error-box">
					<span>{data.error}</span>
					<button
						type="button"
						class="cursor-pointer font-semibold underline"
						onclick={() => loadAll()}
					>
						Try again
					</button>
				</div>
			{/if}

			{#if data.loading}
				<p class="text-muted">Loading your data…</p>
			{:else}
				{@render children()}
			{/if}
		</div>
	</div>
{/if}
