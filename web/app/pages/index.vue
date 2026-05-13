<script setup lang="ts">
import {
  Activity,
  Globe,
  Bell,
  BarChart3,
  ArrowRight,
  CheckCircle,
} from "lucide-vue-next";

definePageMeta({
  sanctum: {
    excluded: true,
  },
});

const features = [
  {
    icon: Globe,
    title: "Know when you're down",
    description:
      "We check your websites and APIs around the clock, so you find out before your users do.",
  },
  {
    icon: BarChart3,
    title: "Response time tracking",
    description:
      "See how fast your services respond over time. Spot slowdowns before they become outages.",
  },
  {
    icon: Bell,
    title: "Instant notifications",
    description:
      "Get alerted the moment something goes wrong — and again when it's back up.",
  },
];

const benefits = [
  "Monitor unlimited URLs",
  "Checks from multiple regions",
  "Uptime history & reports",
  "Email & Telegram alerts",
  "Incident timeline",
  "No vendor lock-in — self-hosted",
];
</script>

<template>
  <div class="min-h-screen bg-background text-foreground font-sans">
    <!-- Navbar -->
    <header
      class="border-b border-border sticky top-0 z-50 bg-background/95 backdrop-blur"
    >
      <div
        class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between"
      >
        <div class="flex items-center gap-2">
          <Activity class="text-primary" :size="20" />
          <span class="font-semibold text-base tracking-tight">NodeWatch</span>
        </div>
        <nav class="flex items-center gap-3">
          <NuxtLink to="/login">
            <Button variant="ghost" size="sm">Log in</Button>
          </NuxtLink>
          <NuxtLink to="/signup">
            <Button size="sm">Sign up free</Button>
          </NuxtLink>
        </nav>
      </div>
    </header>

    <!-- Hero -->
    <section class="max-w-5xl mx-auto px-6 py-28 text-center">
      <h1
        class="text-5xl sm:text-6xl font-bold tracking-tight leading-tight mb-6"
      >
        Is your website up<br />
        <span class="text-primary">right now?</span>
      </h1>
      <p
        class="text-xl text-muted-foreground max-w-xl mx-auto mb-10 leading-relaxed"
      >
        NodeWatch monitors your websites and APIs 24/7 and alerts you the moment
        something goes down.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <NuxtLink to="/signup">
          <Button size="lg" class="gap-2 px-8">
            Start monitoring for free
            <ArrowRight :size="16" />
          </Button>
        </NuxtLink>
        <NuxtLink to="/login">
          <Button size="lg" variant="outline" class="px-8"> Sign in </Button>
        </NuxtLink>
      </div>
    </section>

    <!-- Features -->
    <section class="max-w-5xl mx-auto px-6 pb-24">
      <div class="grid sm:grid-cols-3 gap-6">
        <Card
          v-for="feature in features"
          :key="feature.title"
          class="border-border text-center"
        >
          <CardHeader class="pb-3 items-center">
            <div
              class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-3"
            >
              <component :is="feature.icon" class="text-primary" :size="22" />
            </div>
            <CardTitle class="text-base">{{ feature.title }}</CardTitle>
          </CardHeader>
          <CardContent>
            <CardDescription class="text-sm leading-relaxed">{{
              feature.description
            }}</CardDescription>
          </CardContent>
        </Card>
      </div>
    </section>

    <!-- Benefits -->
    <section class="max-w-5xl mx-auto px-6 pb-24">
      <Card class="border-border bg-card">
        <CardContent class="py-12 px-10">
          <div class="grid sm:grid-cols-2 gap-8 items-center">
            <div>
              <h2 class="text-2xl font-bold tracking-tight mb-3">
                Everything in one place
              </h2>
              <p class="text-muted-foreground leading-relaxed">
                Set up a monitor in seconds. We handle the rest — checking your
                services, recording uptime history, and keeping you in the loop.
              </p>
            </div>
            <ul class="grid grid-cols-1 gap-3">
              <li
                v-for="benefit in benefits"
                :key="benefit"
                class="flex items-center gap-3 text-sm"
              >
                <CheckCircle class="text-primary shrink-0" :size="16" />
                {{ benefit }}
              </li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </section>

    <!-- CTA -->
    <section class="max-w-5xl mx-auto px-6 pb-28 text-center">
      <h2 class="text-3xl font-bold tracking-tight mb-4">
        Start monitoring in minutes
      </h2>
      <p class="text-muted-foreground mb-8 text-lg">
        Free to use. No credit card required.
      </p>
      <NuxtLink to="/signup">
        <Button size="lg" class="gap-2 px-10">
          Create free account
          <ArrowRight :size="16" />
        </Button>
      </NuxtLink>
      <p class="mt-4 text-sm text-muted-foreground">
        Already have an account?
        <NuxtLink to="/login" class="text-primary hover:underline ml-1"
          >Sign in</NuxtLink
        >
      </p>
    </section>

    <!-- Footer -->
    <footer class="border-t border-border">
      <div
        class="max-w-5xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4"
      >
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Activity :size="14" class="text-primary" />
          <span>NodeWatch</span>
        </div>
        <div class="flex items-center gap-6 text-sm text-muted-foreground">
          <NuxtLink to="/login" class="hover:text-foreground transition-colors"
            >Log in</NuxtLink
          >
          <NuxtLink to="/signup" class="hover:text-foreground transition-colors"
            >Sign up</NuxtLink
          >
        </div>
      </div>
    </footer>
  </div>
</template>
