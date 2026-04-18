export interface MonitorProbe {
	monitor_id: number;
	url: string;
	method: string;
	timeout: number;
	expected_status: number;
}

export interface ProbeResult {
	monitor_id: number;
	region: string;
	status_code: number;
	response_time_ms: number;
	ttfb_ms: number;
	is_up: boolean;
	error: string | null;
	checked_at: string;
}

export interface Env {
	REGION: string;
	API_URL: string;
	INTERNAL_TOKEN: string;
}
