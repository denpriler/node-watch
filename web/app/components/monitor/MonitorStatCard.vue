<script setup lang="ts">
defineProps<{
  label: string;
  value: string;
  delta?: string;
  deltaColor?: "green" | "red" | "orange";
  note?: string;
  noteColor?: "orange";
}>();
</script>

<template>
  <Card>
    <CardContent class="p-6 space-y-2">
      <p
        class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
      >
        {{ label }}
      </p>
      <p class="text-3xl font-bold tracking-tight">{{ value }}</p>
      <p
        v-if="delta"
        :class="[
          'text-sm font-medium',
          deltaColor === 'green' && 'text-green-500',
          deltaColor === 'red' && 'text-red-500',
          deltaColor === 'orange' && 'text-orange-500',
          !deltaColor && 'text-muted-foreground',
        ]"
      >
        {{ delta }}
      </p>
      <p
        v-if="note"
        :class="[
          'text-sm',
          noteColor === 'orange' ? 'text-orange-500' : 'text-muted-foreground',
        ]"
      >
        {{ note }}
      </p>
    </CardContent>
  </Card>
</template>
