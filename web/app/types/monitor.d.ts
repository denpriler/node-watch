declare namespace App {
  namespace DTO {
    namespace Monitor {
      export type MonitorAttributes = {
        readonly id: number;
        readonly name: string;
        readonly url: string;
        readonly method: App.Enum.Monitor.MonitorMethod;
        readonly check_interval: number;
        readonly timeout: number;
        readonly expected_status: number;
        readonly regions: App.Enum.Monitor.MonitorRegion[];
        readonly is_active: boolean;
        readonly last_status: App.Enum.Monitor.MonitorStatus;
        readonly last_checked_at: string | null;
      };
      export type Monitor = {
        readonly id: string;
        readonly type: string;
        readonly attributes: MonitorAttributes;
        readonly relationships?: Record<string, unknown>;
      };
    }
  }
}
