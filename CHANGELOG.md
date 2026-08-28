# Changelog

All notable changes to the Lunu PrestaShop Payment Module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Migrated API endpoints from `https://api.lunupay.com/api/v1/...` (production) and `https://api.sandbox.lunupay.com/api/v1/...` (sandbox) to the `legacy-api/v1` path: `https://api.lunupay.com/legacy-api/v1/...` and `https://api.sandbox.lunupay.com/legacy-api/v1/...`
- New widget payment link format: `https://widget.lunupay.com/?order_id=...&success=...&cancel=...` (production) and `https://widget.sandbox.lunupay.com/?...` (sandbox)
- Widget link now uses the payment `id` from the create-payment API response (`order_id` parameter) instead of the `confirmation_token` hash-route parameters

## [2.2.1] - 2025-01-10

### Security
- **CRITICAL**: Enabled SSL certificate verification for all cURL requests (CURLOPT_SSL_VERIFYPEER, CURLOPT_SSL_VERIFYHOST)
- Improved SQL query security with proper type casting in LunuOrder model
- Removed hardcoded test API credentials from installation code

### Changed
- Replaced insecure file-based logging with PrestaShop's built-in Logger class
- Logs now automatically redact sensitive email addresses
- Updated copyright dates from 2019-2021 to 2019-2025 across all files
- Enhanced database schema with proper indexes on id_order, id_cart, and id_payment columns
- Improved SQL queries to include date_add and date_upd fields automatically
- Cleaned up README.md to remove exposed test credentials
- **Simplified widget/API version selection**: Removed automatic detection based on site URL
- Widget version now controlled only by Sandbox mode checkbox (production by default, sandbox when enabled)
- Updated domain references from lunu.io to lunupay.com where appropriate
- Changed widget URL from widget.lunu.io to widget.lunupay.com
- Changed API endpoints from api.lunu.io to api.lunupay.com

### Added
- Database indexes for better query performance
- Automatic sensitive data redaction in logs
- Comprehensive CHANGELOG.md file
- config.xml for PrestaShop Addons Marketplace compliance

### Removed
- Complex URL-based environment detection (dev.lunu.io, rc.lunu.io, testing.lunu.io)
- Automatic widget version switching based on merchant domain

### Documentation
- Updated README.md with clear instructions for obtaining API credentials
- Removed test credentials from documentation

## [2.2.0] - 2021-XX-XX

### Added
- Refund functionality for full and partial refunds
- Support for Lunu Gift payments
- Admin order page integration with payment details
- Callback URL handling for payment status updates
- Configurable payment timeout

### Features
- Bitcoin and cryptocurrency payment support
- Real-time payment status updates
- Sandbox mode for development and staging
- Multi-currency support
- PrestaShop 1.6+ to 8.x compatibility

## [Earlier Versions]

See git history for changes in versions prior to 2.2.0

