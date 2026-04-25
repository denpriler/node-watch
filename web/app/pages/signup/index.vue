<script setup lang="ts">
import SignUpForm from "~/components/auth/SignUpForm.vue";

definePageMeta({
  sanctum: {
    excluded: true,
  },
});
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <SignUpForm />
    </div>
  </div>
</template>
