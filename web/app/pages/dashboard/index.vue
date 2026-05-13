<script setup lang="ts">
import { useQuery } from "@tanstack/vue-query";
import { onServerPrefetch } from "vue";
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationFirst,
  PaginationItem,
  PaginationLast,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";

type Monitor = App.DTO.Monitor.Monitor;
type MonitorStatus = App.Enum.Monitor.MonitorStatus;
type MonitorRegion = App.Enum.Monitor.MonitorRegion;
type MonitorResponse = Illuminate.LengthAwarePaginator<never, Monitor>;

definePageMeta({ layout: "dashboard" });

const route = useRoute();
const router = useRouter();
const client = useSanctumClient();

const page = computed(() => Number(route.query.page ?? 1));

const { data, isPending, suspense } = useQuery({
  queryKey: ["monitors", page],
  queryFn: () =>
    client<MonitorResponse>("/api/monitor", { query: { page: page.value } }),
});

onServerPrefetch(suspense);

const monitors = computed(
  (): Monitor[] => (data.value?.data ?? []) as Monitor[],
);
const meta = computed(() => data.value?.meta);

function setPage(p: number) {
  router.push({ query: { ...route.query, page: p } });
}

function monitorStatus(m: Monitor): {
  label: string;
  dot: string;
  text: string;
} {
  if (!m.attributes.is_active) {
    return { label: "Paused", dot: "bg-yellow-400", text: "text-yellow-500" };
  }
  const map: Record<
    MonitorStatus,
    { label: string; dot: string; text: string }
  > = {
    0: {
      label: "Pending",
      dot: "bg-muted-foreground",
      text: "text-muted-foreground",
    },
    1: { label: "Up", dot: "bg-green-500", text: "text-green-500" },
    2: { label: "Down", dot: "bg-red-500", text: "text-red-500" },
  };
  return map[m.attributes.last_status];
}

const allRegions: MonitorRegion[] = ["eu-west", "us-east", "ap-south"];

const regionColor: Record<MonitorRegion, string> = {
  "eu-west": "bg-blue-400",
  "us-east": "bg-purple-400",
  "ap-south": "bg-cyan-400",
};

function lastChecked(m: Monitor): string {
  if (m.attributes.last_status === 0 || !m.attributes.last_checked_at)
    return "—";
  const ts = new Date(m.attributes.last_checked_at).getTime();
  const diff = Math.floor((Date.now() - ts) / 1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  return `${Math.floor(diff / 3600)}h ago`;
}

function intervalLabel(seconds: number): string {
  if (seconds < 60) return `${seconds}s`;
  return `${Math.floor(seconds / 60)}m`;
}
</script>

<template>
  <div class="container flex flex-col gap-6 p-6">
    <!-- Table -->
    <div class="rounded-lg border border-border overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr
            class="border-b border-border bg-muted/40 text-muted-foreground text-xs uppercase tracking-wide"
          >
            <th class="px-4 py-3 text-left font-medium">Monitor</th>
            <th class="px-4 py-3 text-center font-medium">Status</th>
            <th class="px-4 py-3 text-center font-medium">Regions</th>
            <th class="px-4 py-3 text-center font-medium">Interval</th>
            <th class="px-4 py-3 text-center font-medium">Last checked</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="isPending">
            <tr
              v-for="i in 8"
              :key="i"
              class="border-b border-border last:border-0"
            >
              <td class="px-4 py-4" colspan="6">
                <div class="h-4 rounded bg-muted animate-pulse" />
              </td>
            </tr>
          </template>

          <template v-else-if="monitors.length === 0">
            <tr>
              <td
                class="px-4 py-16 text-center text-muted-foreground"
                colspan="6"
              >
                No monitors found.
              </td>
            </tr>
          </template>

          <template v-else>
            <tr
              v-for="monitor in monitors"
              :key="monitor.attributes.id"
              class="border-b border-border last:border-0 hover:bg-muted/50 transition-colors cursor-pointer"
            >
              <td class="px-4 py-4">
                <NuxtLink
                  :to="`/monitor/${monitor.attributes.id}`"
                  class="font-medium leading-tight hover:underline"
                  >{{ monitor.attributes.name }}</NuxtLink
                >
                <div
                  class="text-xs text-muted-foreground font-mono mt-0.5 truncate max-w-xs"
                >
                  {{ monitor.attributes.url }}
                </div>
              </td>

              <td class="px-4 py-4">
                <div class="flex items-center justify-center gap-2">
                  <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="monitorStatus(monitor).dot"
                  />
                  <span
                    class="font-medium"
                    :class="monitorStatus(monitor).text"
                  >
                    {{ monitorStatus(monitor).label }}
                  </span>
                </div>
              </td>

              <td class="px-4 py-4">
                <div class="flex items-center justify-center gap-1.5">
                  <span
                    v-for="region in allRegions"
                    :key="region"
                    :title="region"
                    class="w-3.5 h-3.5 rounded-full"
                    :class="
                      monitor.attributes.regions.includes(region)
                        ? regionColor[region]
                        : 'bg-border'
                    "
                  />
                </div>
              </td>

              <td class="px-4 py-4 text-muted-foreground text-center">
                {{ intervalLabel(monitor.attributes.check_interval) }}
              </td>

              <td class="px-4 py-4 text-muted-foreground text-center">
                {{ lastChecked(monitor) }}
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1">
      <Pagination
        :total="meta.total"
        :items-per-page="meta.per_page"
        :page="page"
        :sibling-count="1"
        show-edges
        @update:page="setPage"
      >
        <PaginationContent v-slot="{ items }">
          <PaginationFirst />
          <PaginationPrevious />
          <template
            v-for="(item, i) in items"
            :key="item.type === 'page' ? item.value : i"
          >
            <PaginationItem
              v-if="item.type === 'page'"
              :value="item.value"
              :is-active="item.value === page"
            >
              {{ item.value }}
            </PaginationItem>
            <PaginationEllipsis v-else-if="item.type === 'ellipsis'" />
          </template>
          <PaginationNext />
          <PaginationLast />
        </PaginationContent>
      </Pagination>
    </div>
  </div>
</template>
