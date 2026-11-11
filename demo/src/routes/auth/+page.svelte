<script>
	import Logo from '#lib/components/Logo.svelte';
	import { auth, register, login } from '#lib/auth.svelte.js';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { resolve } from '$app/paths';

	// Which tab is showing. The landing page's "Create free account"
	// button opens this page with ?mode=register.
	let mode = $state(page.url.searchParams.get('mode') === 'register' ? 'register' : 'login');

	// ----- Register form -----
	let regName = $state('');
	let regEmail = $state('');
	let regPassword = $state('');
	let regError = $state('');

	async function handleRegister(event) {
		event.preventDefault();
		regError = '';

		if (!regName.trim() || !regEmail.trim() || !regPassword) {
			regError = 'Please fill in all fields.';
			return;
		}

		try {
			// On success this saves the user + token and we go to the app.
			await register(regName.trim(), regEmail.trim(), regPassword);
			goto(resolve('/tracker'));
		} catch (error) {
			// e.g. "email has already been taken" from the backend.
			regError = error.message;
		}
	}

	// ----- Login form -----
	let loginEmail = $state('');
	let loginPassword = $state('');
	let loginError = $state('');

	async function handleLogin(event) {
		event.preventDefault();
		loginError = '';

		if (!loginEmail.trim() || !loginPassword) {
			loginError = 'Please fill in both fields.';
			return;
		}

		try {
			await login(loginEmail.trim(), loginPassword);
			goto(resolve('/tracker'));
		} catch (error) {
			// "Wrong email or password" from the backend.
			loginError = error.message;
		}
	}

	const tabBase = 'flex-1 cursor-pointer rounded-xl px-4 py-3 text-base font-semibold transition';
	const tabActive = 'bg-white text-ink shadow-sm';
	const tabIdle = 'text-muted hover:text-ink';
</script>

<div class="min-h-screen bg-canvas px-4 py-14">
	<div class="mx-auto w-full max-w-[540px]">
		<div class="mb-8 flex items-center justify-between">
			<a href={resolve('/')} aria-label="Spendly home"><Logo /></a>
			<a href={resolve('/')} class="text-muted underline underline-offset-2 hover:text-ink">Back</a>
		</div>

		<div class="card p-10">
			<!-- Log in / Create account switch -->
			<div class="flex gap-1 rounded-2xl bg-stone-100 p-1" role="tablist">
				<button
					type="button"
					role="tab"
					aria-selected={mode === 'login'}
					class="{tabBase} {mode === 'login' ? tabActive : tabIdle}"
					onclick={() => (mode = 'login')}
				>
					Log in
				</button>
				<button
					type="button"
					role="tab"
					aria-selected={mode === 'register'}
					class="{tabBase} {mode === 'register' ? tabActive : tabIdle}"
					onclick={() => (mode = 'register')}
				>
					Create account
				</button>
			</div>

			{#if mode === 'login'}
				<form onsubmit={handleLogin} class="mt-8 space-y-5">
					<div>
						<h1 class="text-3xl font-extrabold tracking-tight text-ink">Welcome back</h1>
						<p class="mt-1 text-lg text-muted">Log in to see your expenses.</p>
					</div>

					<div>
						<label for="login-email" class="label">Email</label>
						<input
							id="login-email"
							type="email"
							bind:value={loginEmail}
							placeholder="you@example.com"
							class="field"
							required
						/>
					</div>

					<div>
						<label for="login-password" class="label">Password</label>
						<input
							id="login-password"
							type="password"
							bind:value={loginPassword}
							placeholder="Your password"
							class="field"
							required
						/>
					</div>

					{#if loginError}
						<p class="error-box">{loginError}</p>
					{/if}

					<button type="submit" class="btn-primary w-full">Log in</button>
				</form>
			{:else}
				<form onsubmit={handleRegister} class="mt-8 space-y-5">
					<div>
						<h1 class="text-3xl font-extrabold tracking-tight text-ink">Create your account</h1>
						<p class="mt-1 text-lg text-muted">Start tracking your spending in seconds.</p>
					</div>

					<div>
						<label for="reg-name" class="label">Name</label>
						<input
							id="reg-name"
							type="text"
							bind:value={regName}
							placeholder="Your name"
							class="field"
							required
						/>
					</div>

					<div>
						<label for="reg-email" class="label">Email</label>
						<input
							id="reg-email"
							type="email"
							bind:value={regEmail}
							placeholder="you@example.com"
							class="field"
							required
						/>
					</div>

					<div>
						<label for="reg-password" class="label">Password</label>
						<input
							id="reg-password"
							type="password"
							bind:value={regPassword}
							placeholder="At least 6 characters"
							class="field"
							required
							minlength="6"
						/>
					</div>

					{#if regError}
						<p class="error-box">{regError}</p>
					{/if}

					<button type="submit" class="btn-primary w-full">Create account</button>
				</form>
			{/if}
		</div>

		<!-- Helpful if you are already logged in and end up here anyway -->
		{#if auth.user}
			<p class="mt-6 text-center text-sm text-muted">
				You are logged in as {auth.user.name} —
				<a href={resolve('/tracker')} class="font-medium text-brand-600 hover:underline"
					>go to your tracker</a
				>
			</p>
		{/if}
	</div>
</div>
