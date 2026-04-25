// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  css: ["~/assets/styles/tailwind.css"],

  vite: {
    plugins: [tailwindcss()],
    optimizeDeps: {
      include: [
        "@vue/devtools-core",
        "@vue/devtools-kit",
        "class-variance-authority",
        "reka-ui",
        "clsx",
        "tailwind-merge",
        "lucide-vue-next",
      ],
    },
  },
  modules: ["shadcn-nuxt", "@vueuse/nuxt", "@nuxt/eslint", "nuxt-auth-sanctum"],
  shadcn: {
    /**
     * Prefix for all the imported component.
     * @default "Ui"
     */
    prefix: "",
    /**
     * Directory that the component lives in.
     * Will respect the Nuxt aliases.
     * @link https://nuxt.com/docs/api/nuxt-config#alias
     * @default "@/components/ui"
     */
    componentDir: "@/components/ui",
  },
  sanctum: {
    baseUrl: "http://localhost:8000",
    mode: "cookie",
    endpoints: {
      csrf: "/sanctum/csrf-cookie",
      login: "/api/auth/login",
      logout: "/api/auth/logout",
      user: "/api/auth/me",
    },
    csrf: {
      cookie: "XSRF-TOKEN",
      header: "X-XSRF-TOKEN",
    },
    redirect: {
      onLogin: "/dashboard",
      onLogout: "/",
      onAuthOnly: "/login",
      onGuestOnly: "/dashboard",
    },
    globalMiddleware: {
      enabled: true,
    },
  },
});
