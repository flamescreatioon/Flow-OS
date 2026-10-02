# Clock and Time Authority

Server time governs authentication expiry, processing, server audit fields, and sync ordering. Client time is business context only. Future time-dependent rules use injectable/testable clocks (including .NET `TimeProvider`) and test boundaries, leap years, DST, and clock skew.
