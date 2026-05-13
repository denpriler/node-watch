<script setup lang="ts">
import { Pause, CirclePlay } from "lucide-vue-next";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { onServerPrefetch } from "vue";
import type { ApiResponse } from "~/types";

type Monitor = App.DTO.Monitor.Monitor;
type MonitorApiResponse = ApiResponse<Monitor>;

definePageMeta({
  validate(route) {
    return typeof route.params.id === "string" && /^\d+$/.test(route.params.id);
  },
  layout: "dashboard",
});

const route = useRoute();
const client = useSanctumClient();
const queryClient = useQueryClient();

const {
  data: monitor,
  isPending,
  suspense,
} = useQuery({
  queryKey: computed(() => ["monitor", route.params.id as string]),
  queryFn: () => client<MonitorApiResponse>(`/api/monitor/${route.params.id}`),
  select: (data: MonitorApiResponse) => data.data,
});

const { mutate: updateMonitor, isPending: isUpdatingMonitor } = useMutation({
  mutationFn: (is_active: boolean) =>
    client<MonitorApiResponse>(`/api/monitor/${route.params.id}`, {
      body: { is_active },
      method: "PUT",
    }),
  onSuccess: (data: MonitorApiResponse) => {
    queryClient.setQueryData(["monitor", route.params.id as string], data);
  },
});
const switchStatus = () =>
  updateMonitor(!toValue(monitor)?.attributes.is_active);

onServerPrefetch(suspense);

const statusConfig = computed(() => {
  switch (toValue(monitor)?.attributes.last_status) {
    case 1:
      return {
        label: "UP",
        dot: "bg-green-500",
        badge: "bg-green-500/15 text-green-500",
      };
    case 2:
      return {
        label: "DOWN",
        dot: "bg-red-500",
        badge: "bg-red-500/15 text-red-500",
      };
    default:
      return {
        label: "PENDING",
        dot: "bg-muted-foreground",
        badge: "bg-muted text-muted-foreground",
      };
  }
});

const tabs = [
  { key: "overview", label: "Обзор" },
  { key: "incidents", label: "Инциденты" },
  { key: "regions", label: "Регионы" },
] as const;

type TabKey = (typeof tabs)[number]["key"];
const activeTab = ref<TabKey>("overview");

const timeRanges = ["1h", "24h", "7d", "30d"] as const;
type TimeRange = (typeof timeRanges)[number];
const activeRange = ref<TimeRange>("24h");
</script>

<template>
  <div class="container">
    <!-- skeleton -->
    <template v-if="isPending || !monitor">
      <div class="flex items-center justify-between">
        <Skeleton class="h-4 w-32" />
        <div class="flex gap-2">
          <Skeleton class="h-8 w-32" />
          <Skeleton class="h-8 w-28" />
        </div>
      </div>
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <Skeleton class="h-3 w-3 rounded-full" />
          <Skeleton class="h-7 w-48" />
          <Skeleton class="h-5 w-12 rounded-full" />
        </div>
        <Skeleton class="h-4 w-64 ml-6" />
      </div>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Skeleton v-for="i in 4" :key="i" class="h-28 rounded-xl" />
      </div>
      <Skeleton class="h-80 rounded-xl" />
    </template>

    <!-- content -->
    <template v-else>
      <!-- top bar -->
      <div class="flex items-center justify-between mb-4">
        <Button
          :disabled="isUpdatingMonitor"
          variant="outline"
          size="sm"
          @click="switchStatus"
        >
          <Component
            :is="monitor.attributes.is_active ? Pause : CirclePlay"
            class="h-4 w-4 mr-2"
          />
          {{ monitor.attributes.is_active ? "Stop" : "Run" }}
        </Button>
        <!--        <Button variant="outline" size="sm">-->
        <!--          <Settings class="h-4 w-4 mr-2" />-->
        <!--          Настройки-->
        <!--        </Button>-->
      </div>

      <!-- monitor header -->
      <div class="space-y-1">
        <div class="flex items-center gap-3">
          <span :class="['h-3 w-3 rounded-full shrink-0', statusConfig.dot]" />
          <h1 class="text-2xl font-bold">{{ monitor.attributes.name }}</h1>
          <span
            :class="[
              'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
              statusConfig.badge,
            ]"
          >
            {{ statusConfig.label }}
          </span>
        </div>
        <p class="text-sm text-muted-foreground pl-6">
          {{ monitor.attributes.url }}
        </p>
      </div>

      <!-- stat cards -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 my-4">
        <MonitorStatCard
          label="UPTIME 24Ч"
          value="99.24%"
          delta="-0.11%"
          delta-color="red"
        />
        <MonitorStatCard
          label="UPTIME 30Д"
          value="99.982%"
          delta="+0.04%"
          delta-color="green"
        />
        <MonitorStatCard
          label="AVG RESPONSE"
          value="142 ms"
          delta="-12ms"
          delta-color="green"
        />
        <MonitorStatCard
          label="INCIDENTS 30Д"
          value="2"
          note="2 активных"
          note-color="orange"
        />
      </div>

      <!-- tabs + time range -->
      <div class="flex items-center justify-between">
        <div class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
              'px-4 py-1.5 text-sm font-medium rounded-md transition-colors',
              activeTab === tab.key
                ? 'bg-foreground text-background'
                : 'text-muted-foreground hover:text-foreground',
            ]"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </div>
        <div class="flex gap-1">
          <button
            v-for="range in timeRanges"
            :key="range"
            :class="[
              'px-3 py-1 text-xs font-medium rounded-md transition-colors',
              activeRange === range
                ? 'bg-muted text-foreground'
                : 'text-muted-foreground hover:text-foreground',
            ]"
            @click="activeRange = range"
          >
            {{ range }}
          </button>
        </div>
      </div>

      <!-- charts -->
      <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
        <MonitorResponseChart :monitor="monitor" />
        <MonitorUptimeCard :monitor="monitor" />
      </div>
    </template>
  </div>
</template>
