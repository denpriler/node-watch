<script setup lang="ts">
import { useQuery } from "@tanstack/vue-query";
import {
  VisXYContainer,
  VisLine,
  VisAxis,
  VisCrosshair,
  VisTooltip,
} from "@unovis/vue";

type MonitorLogBucket = App.DTO.Monitor.MonitorLogBucket;
type MonitorRegion = App.Enum.Monitor.MonitorRegion;
type Monitor = App.DTO.Monitor.Monitor;

const { monitor } = defineProps<{ monitor: Monitor }>();
const client = useSanctumClient();

const periods = [
  { value: "24h" as const, label: "24ч", days: 1 },
  { value: "7d" as const, label: "7д", days: 7 },
  { value: "30d" as const, label: "30д", days: 30 },
  { value: "90d" as const, label: "90д", days: 90 },
];
type Period = "24h" | "7d" | "30d" | "90d";
const period = ref<Period>("24h");

const range = computed(() => {
  const days = periods.find((p) => p.value === period.value)!.days;
  const to = new Date();
  const from = new Date(to.getTime() - days * 86400 * 1000);
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
  };
});

type MonitorLogsResponse = Partial<Record<MonitorRegion, MonitorLogBucket[]>>;

const { data: logs, isPending } = useQuery<MonitorLogsResponse>({
  queryKey: computed(() => ["monitor", "logs", monitor.id, period.value]),
  queryFn: () =>
    client<MonitorLogsResponse>(`/api/monitor/${monitor.id}/logs`, {
      query: range.value,
    }),
});

const regionConfig: Record<
  MonitorRegion,
  { label: string; dot: string; color: string }
> = {
  "eu-west": { label: "EU West", dot: "bg-cyan-400", color: "var(--chart-1)" },
  "us-east": {
    label: "US East",
    dot: "bg-purple-500",
    color: "var(--chart-2)",
  },
  "ap-south": {
    label: "AP South",
    dot: "bg-green-500",
    color: "var(--chart-3)",
  },
};

const activeRegions = computed(() => monitor.attributes.regions);
const hasRegion = (r: MonitorRegion) => activeRegions.value.includes(r);

type ChartPoint = {
  timestamp: number;
  euWest: number | null;
  usEast: number | null;
  apSouth: number | null;
  euWestBucket?: MonitorLogBucket;
  usEastBucket?: MonitorLogBucket;
  apSouthBucket?: MonitorLogBucket;
};

const chartData = computed((): ChartPoint[] => {
  if (!logs.value) return [];
  const map = new Map<string, ChartPoint>();

  const ensure = (bucket: string): ChartPoint => {
    if (!map.has(bucket)) {
      map.set(bucket, {
        timestamp: new Date(bucket).getTime(),
        euWest: null,
        usEast: null,
        apSouth: null,
      });
    }
    return map.get(bucket)!;
  };

  for (const b of logs.value["eu-west"] ?? []) {
    const pt = ensure(b.bucket);
    pt.euWest = Math.round(b.avg_response_time_ms);
    pt.euWestBucket = b;
  }
  for (const b of logs.value["us-east"] ?? []) {
    const pt = ensure(b.bucket);
    pt.usEast = Math.round(b.avg_response_time_ms);
    pt.usEastBucket = b;
  }
  for (const b of logs.value["ap-south"] ?? []) {
    const pt = ensure(b.bucket);
    pt.apSouth = Math.round(b.avg_response_time_ms);
    pt.apSouthBucket = b;
  }

  return [...map.values()].sort((a, b) => a.timestamp - b.timestamp);
});

const totalSamples = computed(() =>
  Object.values(logs.value ?? {})
    .flat()
    .reduce((sum, b) => sum + (b?.sample_count ?? 0), 0),
);

const isSparse = computed(
  () => totalSamples.value < 30 || chartData.value.length < 3,
);

const xAccessor = (d: ChartPoint) => d.timestamp;
const yEuWest = (d: ChartPoint) => d.euWest;
const yUsEast = (d: ChartPoint) => d.usEast;
const yApSouth = (d: ChartPoint) => d.apSouth;

const xFormatter = (v: Date | number) => {
  const date = new Date(typeof v === "number" ? v : v.getTime());
  if (period.value === "24h") {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }
  return date.toLocaleDateString([], { month: "short", day: "numeric" });
};

const yFormatter = (v: number) => `${v}ms`;

const crosshairTemplate = (dataArg: unknown): string => {
  const raw = dataArg as Record<string, unknown>;
  const d = ("data" in raw ? raw.data : raw) as ChartPoint;

  const date = new Date(d.timestamp);
  const timeStr =
    period.value === "24h"
      ? date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
      : date.toLocaleDateString([], {
          month: "short",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        });

  const allRows: Array<{ region: MonitorRegion; bucket?: MonitorLogBucket }> = [
    { region: "eu-west" as MonitorRegion, bucket: d.euWestBucket },
    { region: "us-east" as MonitorRegion, bucket: d.usEastBucket },
    { region: "ap-south" as MonitorRegion, bucket: d.apSouthBucket },
  ];
  const rows = allRows.filter(
    (r) => hasRegion(r.region) && r.bucket !== undefined,
  );

  if (!rows.length) return "";

  let html = `<div style="padding:8px 10px;background:var(--card);border:1px solid var(--border);border-radius:6px;font-size:12px;min-width:175px;box-shadow:0 2px 8px rgba(0,0,0,.15)">`;
  html += `<div style="color:var(--muted-foreground);margin-bottom:6px;font-size:11px">${timeStr}</div>`;

  for (const { region, bucket } of rows) {
    if (!bucket) continue;
    const cfg = regionConfig[region];
    html += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">`;
    html += `<span style="width:8px;height:8px;border-radius:50%;background:${cfg.color};flex-shrink:0"></span>`;
    html += `<span style="flex:1;color:var(--foreground)">${cfg.label}</span>`;
    html += `<span style="font-weight:600;color:var(--foreground)">${Math.round(bucket.avg_response_time_ms)}ms</span>`;
    html += `</div>`;
    html += `<div style="padding-left:14px;color:var(--muted-foreground);font-size:11px;margin-bottom:${bucket.down_count > 0 ? 2 : 5}px">`;
    html += `min ${bucket.min_response_time_ms} / max ${bucket.max_response_time_ms}ms`;
    html += `</div>`;
    if (bucket.down_count > 0) {
      html += `<div style="padding-left:14px;color:#ef4444;font-size:11px;margin-bottom:5px">${bucket.down_count} failed probe${bucket.down_count > 1 ? "s" : ""}</div>`;
    }
  }

  html += `</div>`;
  return html;
};
</script>

<template>
  <Card class="flex flex-col">
    <CardContent class="p-6 flex flex-col gap-4 flex-1">
      <!-- Header + period selector -->
      <div class="flex items-center justify-between">
        <div class="space-y-0.5">
          <h3 class="font-bold">Response time по регионам</h3>
          <p class="text-xs text-muted-foreground">
            Среднее значение за период
          </p>
        </div>
        <div class="flex gap-1">
          <button
            v-for="p in periods"
            :key="p.value"
            :class="[
              'px-3 py-1 text-xs font-medium rounded-md transition-colors',
              period === p.value
                ? 'bg-muted text-foreground'
                : 'text-muted-foreground hover:text-foreground',
            ]"
            @click="period = p.value"
          >
            {{ p.label }}
          </button>
        </div>
      </div>

      <!-- Region legend -->
      <div class="flex items-center gap-5">
        <div
          v-for="region in activeRegions"
          :key="region"
          class="flex items-center gap-1.5 text-sm text-muted-foreground"
        >
          <span
            :class="[
              'h-2.5 w-2.5 rounded-full shrink-0',
              regionConfig[region].dot,
            ]"
          />
          {{ regionConfig[region].label }}
        </div>
      </div>

      <!-- Chart area -->
      <div class="relative flex-1 min-h-64">
        <!-- Loading skeleton -->
        <Skeleton v-if="isPending" class="absolute inset-0 rounded-md" />

        <!-- Sparse placeholder -->
        <div
          v-else-if="isSparse"
          class="h-full min-h-64 flex flex-col items-center justify-center gap-2 rounded-md bg-muted/30"
        >
          <p class="text-sm font-medium">Your data is coming soon...</p>
          <p class="text-xs text-muted-foreground">
            {{ totalSamples }} проверок собрано
          </p>
          <p class="text-xs text-muted-foreground">Подождите 5–10 минут</p>
        </div>

        <!-- Chart -->
        <ClientOnly v-else>
          <div
            class="h-full min-h-64 [&_[data-vis-xy-container]]:h-full [&_[data-vis-xy-container]]:w-full"
            style="
              --vis-tooltip-padding: 0px;
              --vis-tooltip-background-color: transparent;
              --vis-tooltip-border-color: transparent;
              --vis-tooltip-text-color: none;
              --vis-tooltip-shadow-color: none;
              --vis-tooltip-backdrop-filter: none;
              --vis-crosshair-circle-stroke-color: #0000;
              --vis-crosshair-line-stroke-width: 1px;
              --vis-font-family: var(--font-sans);
              --vis-axis-tick-label-color: var(--muted-foreground);
              --vis-axis-tick-color: var(--border);
              --vis-axis-domain-color: var(--border);
            "
          >
            <VisXYContainer :data="chartData" :height="220">
              <VisLine
                v-if="hasRegion('eu-west')"
                :x="xAccessor"
                :y="yEuWest"
                :color="regionConfig['eu-west'].color"
              />
              <VisLine
                v-if="hasRegion('us-east')"
                :x="xAccessor"
                :y="yUsEast"
                :color="regionConfig['us-east'].color"
              />
              <VisLine
                v-if="hasRegion('ap-south')"
                :x="xAccessor"
                :y="yApSouth"
                :color="regionConfig['ap-south'].color"
              />
              <VisAxis type="x" :tick-format="xFormatter" :num-ticks="6" />
              <VisAxis type="y" :tick-format="yFormatter" :num-ticks="4" />
              <VisCrosshair :template="crosshairTemplate" />
              <VisTooltip />
            </VisXYContainer>
          </div>
          <template #fallback>
            <Skeleton class="absolute inset-0 rounded-md" />
          </template>
        </ClientOnly>
      </div>
    </CardContent>
  </Card>
</template>
