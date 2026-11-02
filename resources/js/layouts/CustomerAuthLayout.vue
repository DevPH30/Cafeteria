<template>
    <div class="cafe-site customer-auth-site">
        <header class="site-header">
            <a class="brand" href="/" aria-label="Café Aroma - início">
                <span class="brand-mark">
                    <img
                        src="/images/cafe/logo-cup.png"
                        alt=""
                        aria-hidden="true"
                    />
                </span>
                <span>Café Aroma</span>
            </a>
            <a class="customer-auth-home" href="/">Voltar para o início</a>
        </header>
        <main class="customer-auth-main">
            <section class="customer-auth-card card">
                <slot />
            </section>
        </main>
        <footer class="customer-auth-footer">
            Feito com carinho, servido com aroma.
        </footer>
    </div>
</template>
