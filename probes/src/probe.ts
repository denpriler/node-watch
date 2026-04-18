import type { MonitorProbe, ProbeResult } from './types';

export async function executeProbe(probe: MonitorProbe, region: string): Promise<ProbeResult> {
	const checkedAt = new Date().toISOString();
	const startTime = Date.now();

	let statusCode = 0;
	let ttfbMs = 0;
	let responseTimeMs = 0;
	let errorMsg: string | null = null;

	try {
		const response = await fetch(probe.url, {
			method: probe.method,
			signal: AbortSignal.timeout(probe.timeout * 1000),
			redirect: 'follow',
		});

		statusCode = response.status;

		// Измеряем TTFB: читаем первый chunk из stream
		const reader = response.body?.getReader();
		if (reader) {
			await reader.read();
			ttfbMs = Date.now() - startTime;
			await reader.cancel();
		} else {
			ttfbMs = Date.now() - startTime;
		}

		responseTimeMs = Date.now() - startTime;
	} catch (err) {
		responseTimeMs = Date.now() - startTime;
		ttfbMs = responseTimeMs;
		errorMsg = err instanceof Error ? err.message : String(err);
	}

	const isUp = errorMsg === null && statusCode === probe.expected_status;

	return {
		monitor_id: probe.monitor_id,
		region,
		status_code: statusCode,
		response_time_ms: responseTimeMs,
		ttfb_ms: ttfbMs,
		is_up: isUp,
		error: errorMsg,
		checked_at: checkedAt,
	};
}

export async function postResult(result: ProbeResult, apiUrl: string, token: string): Promise<void> {
	const response = await fetch(`${apiUrl}/api/internal/probe-result`, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Internal-Token': token,
		},
		body: JSON.stringify(result),
	});

	if (!response.ok) {
		const body = await response.text();
		throw new Error(`API responded ${response.status}: ${body}`);
	}
}
