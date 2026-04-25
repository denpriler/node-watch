<script setup lang="ts">
import LoginForm from "@/components/auth/LoginForm.vue";

definePageMeta({
  sanctum: {
    excluded: true,
  },
});
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <LoginForm />
    </div>
  </div>
</template>
