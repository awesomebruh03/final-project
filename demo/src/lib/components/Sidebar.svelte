<script>
	import Logo from './Logo.svelte';
	import { auth, logout } from '../auth.svelte.js';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { resolve } from '$app/paths';

	const links = [
		{ label: 'Overview', href: resolve('/tracker') },
		{ label: 'Categories', href: resolve('/tracker/categories') },
		{ label: 'Insights', href: resolve('/tracker/insights') },
		{ label: 'Budgets', href: resolve('/tracker/budgets') },
		{ label: 'Expenses', href: resolve('/tracker/expenses') }
	];

	// "Yada Yada" -> "YY", "sam" -> "S"
	const initials = $derived(
		(auth.user?.name || '')
			.split(/\s+/)
			.filter(Boolean)
			.slice(0, 2)
			.map((part) => part[0].toUpperCase())
			.join('')
	);

	function isActive(href) {
		return page.url.pathname.replace(/\/$/, '') === href;
	}

	// Logs out, then sends the user back to the auth page.
	async function handleLogout() {
		await logout();
		goto(resolve('/auth'));
	}
</script>

<aside
	class="flex shrink-0 flex-col border-line bg-white/50 max-md:border-b md:sticky md:top-0 md:h-screen md:w-60 md:border-r"
>
	<div class="flex items-center justify-between px-6 pt-6 pb-4 md:block md:pb-6">
		<a href={resolve('/tracker')} aria-label="Spendly overview"><Logo /></a>

		<!-- Phones: log out sits in the top bar -->
		<button
			onclick={handleLogout}
			class="cursor-pointer text-sm text-muted underline underline-offset-2 md:hidden"
		>
			Log out
		</button>
	</div>

	<nav class="flex gap-1 overflow-x-auto px-3 pb-3 md:flex-1 md:flex-col md:overflow-visible">
		{#each links as link (link.href)}
			{@const active = isActive(link.href)}
			<a
				href={link.href}
				aria-current={active ? 'page' : undefined}
				class="flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 font-semibold whitespace-nowrap transition-colors {active
					? 'bg-brand-50 text-brand-700'
					: 'text-muted hover:bg-stone-100 hover:text-ink'}"
			>
				<span
					class="size-2 rounded-full {active ? 'bg-brand-600' : 'bg-stone-300'}"
					aria-hidden="true"
				></span>
				{link.label}
			</a>
		{/each}
	</nav>

	<div class="hidden items-center gap-3 px-6 py-6 md:flex">
		<span
			class="grid size-10 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700"
			title={auth.user?.name}
		>
			{initials}
		</span>
		<button
			onclick={handleLogout}
			class="cursor-pointer text-sm font-medium text-ink hover:underline"
		>
			Log out
		</button>
	</div>
</aside>
