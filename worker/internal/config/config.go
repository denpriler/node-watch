package config

import (
	"fmt"
	"os"
)

type Config struct {
	KafkaBroker   string
	KafkaUsername string
	KafkaPassword string
	KafkaTopic    string
	KafkaGroupID  string
	ResultAPIURL  string
	InternalToken string
	Region        string
}

// flyRegionMap maps Fly.io region codes to our region names and Kafka topics.
var flyRegionMap = map[string][2]string{
	"ams": {"eu-west", "monitor.eu-west"},
	"iad": {"us-east", "monitor.us-east"},
	"nrt": {"ap-south", "monitor.ap-south"},
}

// workerRegionMap maps WORKER_REGION values (local dev) to Kafka topics.
var workerRegionMap = map[string]string{
	"eu-west":  "monitor.eu-west",
	"us-east":  "monitor.us-east",
	"ap-south": "monitor.ap-south",
}

func Load() (*Config, error) {
	var region, topic string

	if flyRegion := os.Getenv("FLY_REGION"); flyRegion != "" {
		entry, ok := flyRegionMap[flyRegion]
		if !ok {
			return nil, fmt.Errorf("unknown FLY_REGION %q — expected one of: ams, iad, nrt", flyRegion)
		}
		region, topic = entry[0], entry[1]
	} else if workerRegion := os.Getenv("WORKER_REGION"); workerRegion != "" {
		t, ok := workerRegionMap[workerRegion]
		if !ok {
			return nil, fmt.Errorf("unknown WORKER_REGION %q — expected one of: eu-west, us-east, ap-south", workerRegion)
		}
		region, topic = workerRegion, t
	} else {
		return nil, fmt.Errorf("set FLY_REGION (production) or WORKER_REGION (local dev)")
	}

	return &Config{
		KafkaBroker:   mustEnv("KAFKA_BROKER"),
		KafkaUsername: os.Getenv("KAFKA_USERNAME"),
		KafkaPassword: os.Getenv("KAFKA_PASSWORD"),
		KafkaTopic:    topic,
		KafkaGroupID:  fmt.Sprintf("node-watch-worker-%s", region),
		ResultAPIURL:  mustEnv("RESULT_API_URL"),
		InternalToken: mustEnv("INTERNAL_TOKEN"),
		Region:        region,
	}, nil
}

func mustEnv(key string) string {
	v := os.Getenv(key)
	if v == "" {
		panic(fmt.Sprintf("required env var %q is not set", key))
	}
	return v
}
