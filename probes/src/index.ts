import { executeProbe, postResult } from './probe';
import type { Env, MonitorProbe } from './types';

export default {
	async queue(batch: MessageBatch<MonitorProbe>, env: Env): Promise<void> {
		for (const message of batch.messages) {
			try {
				const result = await executeProbe(message.body, env.REGION);
				await postResult(result, env.API_URL, env.INTERNAL_TOKEN);
				message.ack();
			} catch (err) {
				console.error(`probe failed for monitor ${message.body.monitor_id}:`, err);
				message.retry();
			}
		}
	},
} satisfies ExportedHandler<Env>;
