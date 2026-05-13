<script setup lang="ts">
type Monitor = App.DTO.Monitor.Monitor;

const props = defineProps<{ monitor: Monitor }>();

const intervalLabel = computed(
  () => `${props.monitor.attributes.check_interval}s`,
);
const timeoutLabel = computed(() => `${props.monitor.attributes.timeout}s`);

type BarStatus = "up" | "down" | "partial";

const uptimeBars: BarStatus[] = Array.from({ length: 90 }, (_, i) => {
  if (i === 45 || i === 46) return "down";
  if (i === 72 || i === 73 || i === 74) return "partial";
  return "up";
});

const barClass: Record<BarStatus, string> = {
  up: "bg-green-500",
  down: "bg-red-500",
  partial: "bg-orange-500",
};

const details = computed(() => [
  { label: "Метод", value: props.monitor.attributes.method },
  { label: "Интервал", value: intervalLabel.value },
  { label: "Таймаут", value: timeoutLabel.value },
  {
    label: "Ожид. код",
    value: String(props.monitor.attributes.expected_status),
  },
]);
</script>

<template>
  <Card>
    <CardContent class="p-6 space-y-4">
      <div>
        <h3 class="font-bold">Uptime последние 90 дней</h3>
        <p class="text-xs text-muted-foreground mt-0.5">
          Каждый столбик — сутки
        </p>
      </div>

      <div class="flex items-stretch gap-[2px] h-8">
        <div
          v-for="(bar, i) in uptimeBars"
          :key="i"
          :class="['flex-1 rounded-[1px]', barClass[bar]]"
        />
      </div>

      <div class="flex justify-between">
        <span class="text-xs text-muted-foreground">90д назад</span>
        <span class="text-xs text-muted-foreground">сегодня</span>
      </div>

      <Separator />

      <div class="space-y-3">
        <div
          v-for="item in details"
          :key="item.label"
          class="flex justify-between text-sm"
        >
          <span class="text-muted-foreground">{{ item.label }}</span>
          <span class="font-medium">{{ item.value }}</span>
        </div>

        <div class="flex justify-between items-center text-sm">
          <span class="text-muted-foreground">SSL</span>
          <span
            class="inline-flex items-center rounded-full bg-green-500/15 px-2 py-0.5 text-xs font-semibold text-green-500"
          >
            valid · 81d
          </span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
