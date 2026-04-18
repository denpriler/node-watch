package config

import (
	"fmt"
	"os"
	"strings"
)

type RegionConfig struct {
	KafkaTopic string
	CFQueueID  string
}

type Config struct {
	KafkaBrokers  []string
	KafkaSASLUser string
	KafkaSASLPass string
	CFAccountID   string
	CFAPIToken    string
	Regions       []RegionConfig
}

func Load() (*Config, error) {
	brokers := requireEnv("KAFKA_BROKERS")
	if brokers == "" {
		return nil, fmt.Errorf("KAFKA_BROKERS is required")
	}

	cfg := &Config{
		KafkaBrokers:  strings.Split(brokers, ","),
		KafkaSASLUser: requireEnv("KAFKA_SASL_USERNAME"),
		KafkaSASLPass: requireEnv("KAFKA_SASL_PASSWORD"),
		CFAccountID:   requireEnv("CF_ACCOUNT_ID"),
		CFAPIToken:    requireEnv("CF_API_TOKEN"),
		Regions: []RegionConfig{
			{KafkaTopic: "monitor.eu-west", CFQueueID: requireEnv("CF_QUEUE_ID_EU_WEST")},
			{KafkaTopic: "monitor.us-east", CFQueueID: requireEnv("CF_QUEUE_ID_US_EAST")},
			{KafkaTopic: "monitor.ap-south", CFQueueID: requireEnv("CF_QUEUE_ID_AP_SOUTH")},
		},
	}

	if err := cfg.validate(); err != nil {
		return nil, err
	}

	return cfg, nil
}

func (c *Config) validate() error {
	if len(c.KafkaBrokers) == 0 || c.KafkaBrokers[0] == "" {
		return fmt.Errorf("KAFKA_BROKERS is required")
	}
	if c.KafkaSASLUser == "" {
		return fmt.Errorf("KAFKA_SASL_USERNAME is required")
	}
	if c.KafkaSASLPass == "" {
		return fmt.Errorf("KAFKA_SASL_PASSWORD is required")
	}
	if c.CFAccountID == "" {
		return fmt.Errorf("CF_ACCOUNT_ID is required")
	}
	if c.CFAPIToken == "" {
		return fmt.Errorf("CF_API_TOKEN is required")
	}
	for _, r := range c.Regions {
		if r.CFQueueID == "" {
			return fmt.Errorf("CF queue ID for topic %s is required", r.KafkaTopic)
		}
	}
	return nil
}

func requireEnv(key string) string {
	return os.Getenv(key)
}
