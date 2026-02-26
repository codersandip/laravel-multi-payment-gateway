# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - Production Release

### Added
- Multi-driver gateway architecture (Razorpay, Stripe CheckoutSessions, Cashfree, PayU).
- Strongly typed `ChargeData` payload injection.
- Failover system for automatic error-retry execution.
- Auto-Reconciliation Database Engine to sync missing webhooks.
- Laravel queue integration (`chargeAsync`).
- Hosted Webhook Catchers natively resolving signature verification.
- Laravel native HTML components rendering exact vendor specifications.
- Fully compatible with Laravel 10.x, 11.x, and 12.x.
