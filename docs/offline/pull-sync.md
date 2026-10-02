# Pull Synchronization

Pull is incremental server-to-client synchronization, not repeated full downloads. The server publishes an ordered change stream containing creates, updates, and deletions. It requires deterministic server-side ordering, later implemented through a monotonic sequence/change log or equivalent—not wall-clock timestamps or UUID ordering alone.

The client submits its scoped opaque cursor, receives a bounded change batch and next cursor, then atomically applies both. Cursors are initially expected to be organization and dataset/resource-family scoped; device scoping is added only when required. A stale/invalid cursor returns `REBASE_REQUIRED`.
