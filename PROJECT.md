# otshar.io

## Secure One-Time File Share (Laravel + React + CLI)

------------------------------------------------------------------------

# 1. Project Overview

**otshar.io** is a secure, ephemeral file-sharing platform that allows
users to:

-   Upload a file
-   Lock it using their own passcode
-   Receive a system-generated one-time pickup code
-   Download the encrypted file using the pickup code
-   Decrypt locally using the lock passcode
-   File automatically expires or deletes after use

The system is designed to:

-   Be security-first
-   Avoid user accounts
-   Avoid link-based long URLs
-   Work via browser AND terminal (CLI)
-   Store only encrypted data server-side
-   Delete files automatically after expiry or one-time use

------------------------------------------------------------------------

# 2. Core Architecture Principles

## 2.1 Separation of Responsibilities

  Component             Responsibility
  --------------------- ---------------------------------------------
  Browser / CLI         Encrypt + Decrypt
  Laravel API           Store encrypted blob + enforce pickup rules
  Storage (S3/Spaces)   Store ciphertext only

Server never: - Sees plaintext - Stores user passcode - Can decrypt file

------------------------------------------------------------------------

# 3. Core Concepts

## 3.1 Two-Code Model

### 1. Pickup Code (System Generated)

-   Format: `SHORTID-PIN`
-   Example: `K7P4-839217`
-   Used only to retrieve encrypted blob
-   Expires quickly
-   Enforced server-side

### 2. Lock Passcode (User Chosen)

-   6--12 digits (or short phrase)
-   Used only to derive encryption key
-   Never stored server-side
-   Required to decrypt file locally

------------------------------------------------------------------------

# 4. High-Level Flow

## 4.1 Upload Flow

1.  User selects file
2.  User enters Lock Passcode
3.  React encrypts file locally:
    -   Generate random fileKey (32 bytes)
    -   Encrypt file using AES-256-GCM
    -   Derive wrapKey using Argon2id (from lock passcode)
    -   Wrap fileKey using wrapKey
4.  Send ciphertext + metadata to Laravel
5.  Laravel stores encrypted blob in S3
6.  Laravel returns Pickup Code

## 4.2 Download Flow

1.  User enters Pickup Code
2.  Laravel verifies pickup code
3.  Laravel returns:
    -   Download token (single use)
    -   crypto metadata
4.  Client downloads ciphertext
5.  User enters Lock Passcode
6.  Client derives key and decrypts locally
7.  File downloads to user
8.  Server marks file consumed (if one-time)

------------------------------------------------------------------------

# 5. Tech Stack

## Backend

-   Laravel (latest stable)
-   PHP 8.2+
-   Postgres (or MySQL)
-   Redis (rate limiting + queue)
-   S3-compatible storage (DigitalOcean Spaces)
-   Cloudflare (WAF + rate limiting)

## Frontend

-   React (Vite or Next.js frontend-only)
-   WebCrypto API
-   Argon2id WASM (recommended)

## CLI (v1)

-   Python CLI
-   Same crypto scheme as browser
-   Uses same API

------------------------------------------------------------------------

# 6. Database Schema

## shares table

id (uuid, primary)\
short_id (string, unique)\
pickup_hash (char 64)\
object_key (string)\
expires_at (timestamp)\
max_downloads (int)\
download_count (int default 0)\
failed_attempts (int default 0)\
locked_until (timestamp nullable)\
kdf (json)\
crypto_meta (json)\
original_name (string nullable)\
mime (string nullable)\
size_bytes (bigint)\
created_at\
updated_at

## share_tokens table

id (uuid)\
share_id (uuid index)\
token_hash (char 64 unique)\
expires_at (timestamp)\
used_at (timestamp nullable)\
created_at

------------------------------------------------------------------------

# 7. API Specification (v1)

Base path: `/api/v1`

## Create Share

POST `/shares`

## Upload Encrypted File

POST `/shares/{id}/upload`

## Redeem Pickup Code

POST `/redeem`

## Download Ciphertext

GET `/download?token=...`

------------------------------------------------------------------------

# 8. Crypto Specification

## File Encryption

-   AES-256-GCM
-   Random 32-byte fileKey
-   12-byte nonce

## Key Derivation

-   Argon2id
-   t=3
-   memory=64MB
-   parallelism=1
-   16-byte random salt

## Key Wrapping

-   AES-GCM wrap
-   Store wrapped_file_key, salt, nonces, kdf params in crypto_meta

------------------------------------------------------------------------

# 9. Security Controls

-   Rate limit redeem attempts
-   Lock share after repeated failures
-   HMAC-based pickup verification
-   SHA256-hashed download tokens
-   Private object storage
-   Automatic expiry cleanup

------------------------------------------------------------------------

# 10. Default Limits

Max file size: 100MB\
Default expiry: 30 minutes\
Default downloads: 1\
Token expiry: 5 minutes

------------------------------------------------------------------------

# 11. Roadmap

Phase 1: MVP (API + Encryption + One-time download)\
Phase 2: CLI + Ads + Pro tier\
Phase 3: Malware scan + QR support

------------------------------------------------------------------------

END OF SPEC
