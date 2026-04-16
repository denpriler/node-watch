FROM golang:1.24-alpine AS builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o /worker ./cmd/worker

FROM alpine:3.21
# ca-certificates required for TLS connections to Redpanda Cloud
RUN apk add --no-cache ca-certificates tzdata
COPY --from=builder /worker /worker
ENTRYPOINT ["/worker"]
