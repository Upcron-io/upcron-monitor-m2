# Upcron Monitor

## Language

### Partial Sync

**Partial Sync**:
A synchronization run in which one or more Magento cron jobs have a confirmed Heartbeat creation or update in Upcron before a later job fails. Every confirmed Heartbeat is persisted locally and displayed as synced, while the run reports the later failure.
_Avoid_: Rolled-back sync, no changes saved

**Fail-fast Sync**:
A Partial Sync stops submitting additional Heartbeats when an Upcron API request fails, then reports that failure together with the count of confirmed Heartbeats.
_Avoid_: Continuing after an API failure, silent partial success
