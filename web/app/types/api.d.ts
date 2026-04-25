declare namespace App {
  namespace DTO {
    namespace Auth {
      export type User = {
        readonly id: number;
        readonly email: string;
      };
    }
    namespace Monitor {
      export type Monitor = {
        readonly id: number;
        readonly name: string;
        readonly url: string;
        readonly method: App.Enum.Monitor.MonitorMethod;
        readonly check_interval: number;
        readonly timeout: number;
        readonly expected_status: number;
        readonly regions: App.Enum.Monitor.MonitorRegion[];
        readonly is_active: boolean;
        readonly next_check_at: string | null;
        readonly last_status: App.Enum.Monitor.MonitorStatus;
      };
      export type MonitorLogEntry = {
        readonly monitor_id: number;
        readonly checked_at: string;
        readonly region: App.Enum.Monitor.MonitorRegion;
        readonly status_code: number;
        readonly response_time_ms: number;
        readonly ttfb_ms: number;
        readonly is_up: boolean;
        readonly error: string | null;
      };
      export type MonitorProbe = {
        readonly monitorId: number;
        readonly url: string;
        readonly method: App.Enum.Monitor.MonitorMethod;
        readonly timeout: number;
        readonly expected_status: number;
      };
    }
  }
  namespace Enum {
    namespace Monitor {
      export type MonitorMethod = "GET" | "POST" | "HEAD";
      export type MonitorRegion = "eu-west" | "us-east" | "ap-south";
      export type MonitorStatus = 0 | 1 | 2;
    }
  }
}
declare namespace Illuminate {
  export type CursorPaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
      url: string | null;
      label: string;
      active: boolean;
    }[];
    meta: {
      path: string;
      per_page: number;
      next_cursor: string | null;
      next_page_url: string | null;
      prev_cursor: string | null;
      prev_page_url: string | null;
    };
  };
  export type CursorPaginatorInterface<TKey, TValue> =
    Illuminate.CursorPaginator<TKey, TValue>;
  export type LengthAwarePaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
      url: string | null;
      label: string;
      active: boolean;
    }[];
    meta: {
      total: number;
      current_page: number;
      first_page_url: string;
      from: number | null;
      last_page: number;
      last_page_url: string;
      next_page_url: string | null;
      path: string;
      per_page: number;
      prev_page_url: string | null;
      to: number | null;
    };
  };
  export type LengthAwarePaginatorInterface<TKey, TValue> =
    Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
  namespace LaravelData {
    export type CursorPaginatedDataCollection<TKey, TValue> =
      Illuminate.CursorPaginator<TKey, TValue>;
    export type PaginatedDataCollection<TKey, TValue> =
      Illuminate.LengthAwarePaginator<TKey, TValue>;
  }
}
