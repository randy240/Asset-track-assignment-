# Task 3 — General Software Engineering

## (a) What is going wrong?

There is a race condition because two supervisors can read the request while it is still pending before either one saves the approval. This can result in the same request being approved twice.

The supervisors are also using one login. This means the audit trail cannot show which person actually approved the request.

## (b) How would I fix it?

Each supervisor should have their own account. The user's ID should be saved in the audit record together with the request, action and time.

The approval should also be done as an atomic update. For example:

```sql
UPDATE dispense_requests
SET status = 'supervisor_approved'
WHERE id = ?
AND status = 'pending';
```

The system should check the affected rows. If one row was changed, the approval was successful. If no row was changed, the request had already been processed.

A transaction or row locking can also be used when more than one database operation is involved.

## (c) Should the two-approval design be reviewed?

Yes. Two approvals are useful for high-value or sensitive equipment, but they may not be necessary for every normal request. The system could use one approval for normal requests and two approvals for requests that need extra control.
