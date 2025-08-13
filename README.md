

## Testing

### Laravel Testing

```shell
php artisan test
```

### E2E Testing

```shell
npx cypress run
npx cypress open   # with UI
```

---

## API

### GraphQL

```graphql
# Example 1
query {
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
      periodicity
    }
  }
}

# Example 2
status(monitorIdentifier: "monitor-123") {
    date
    ok
    responseTime
  }
```