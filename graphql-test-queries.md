# GraphQL Test Queries

This file contains test queries for the monitor GraphQL API.

## Test Query 1: Get all projects
```graphql
query GetAllProjects {
  projects {
    identifier
    label
    description
    monitors {
      identifier
      label
      type
      periodicity
      host
      url
      badgeUrl
    }
  }
}
```

## Test Query 2: Get projects with nested monitors (simplified)
```graphql
query GetProjectsWithMonitors {
  projects {
    identifier
    label
    monitors {
      identifier
      label
      type
      badgeUrl
    }
  }
}
```

## Test Query 3: Get status for a specific monitor
```graphql
query GetMonitorStatus {
  status(monitorIdentifier: "ping-google") {
    date
    ok
    responseTime
  }
}
```

## Test Query 4: Get status for a monitor with time range
```graphql
query GetMonitorStatusWithTimeRange {
  status(
    monitorIdentifier: "ping-google"
    from: 1691884800
    to: 1691971200
  ) {
    date
    ok
    responseTime
  }
}
```

## Test Query 5: Combined query - Projects and specific monitor status
```graphql
query GetProjectsAndMonitorStatus {
  projects {
    identifier
    label
    description
    monitors {
      identifier
      label
      type
      host
      url
      badgeUrl
    }
  }
  status(monitorIdentifier: "ping-google") {
    date
    ok
    responseTime
  }
}
```

## Test Query 6: Get only project identifiers and labels
```graphql
query GetProjectsSummary {
  projects {
    identifier
    label
  }
}
```

## Test Query 7: Get detailed monitor information
```graphql
query GetDetailedMonitors {
  projects {
    identifier
    label
    monitors {
      identifier
      periodicity
      label
      type
      host
      url
      badgeUrl
    }
  }
}
```

## Test Query 8: Get recent status for multiple time periods
```graphql
query GetRecentStatus {
  lastHour: status(
    monitorIdentifier: "ping-google"
    from: 1691968800
  ) {
    date
    ok
    responseTime
  }
  lastDay: status(
    monitorIdentifier: "ping-google"
    from: 1691884800
  ) {
    date
    ok
    responseTime
  }
}
```

## Variables for testing
When testing with variables, you can use these example variables:

```json
{
  "monitorId": "ping-google",
  "fromTimestamp": 1691884800,
  "toTimestamp": 1691971200
}
```

## Parameterized Query Example
```graphql
query GetMonitorStatusWithVariables($monitorId: String!, $from: Int, $to: Int) {
  status(monitorIdentifier: $monitorId, from: $from, to: $to) {
    date
    ok
    responseTime
  }
}
```

## Testing Notes

1. Replace "ping-google" with actual monitor identifiers from your database
2. Timestamps are Unix timestamps (seconds since epoch)
3. The `identifier` fields return the database ID as a string
4. The `badgeUrl` field generates a URL like "/api/badge/{id}"
5. Monitor `type` field maps to the `monitor_type` database column
6. Monitor `host` field maps to the `hostname` database column

## Expected Response Format

### Projects Query Response:
```json
{
  "data": {
    "projects": [
      {
        "identifier": "1",
        "label": "My Project",
        "description": "Project description",
        "monitors": [
          {
            "identifier": "1",
            "label": "Google Ping",
            "type": "ping",
            "periodicity": 60,
            "host": "google.com",
            "url": null,
            "badgeUrl": "/api/badge/1"
          }
        ]
      }
    ]
  }
}
```

### Status Query Response:
```json
{
  "data": {
    "status": [
      {
        "date": "2025-08-13T10:30:00Z",
        "ok": true,
        "responseTime": 25
      }
    ]
  }
}
```
