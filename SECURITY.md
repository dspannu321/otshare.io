# Security & SOC2-Aligned Hardening (otshare.io)

This document describes the security architecture, controls, and testing for otshare.io so you can operate and audit it like Fort Knox.

---

## 1. Overview

- **Model**: Server never sees plaintext or passcodes. Clients encrypt with a user-chosen passcode and upload ciphertext + metadata. Download returns ciphertext; decryption happens in the browser.
- **Standards**: Design follows SOC2-relevant practices (access control, encryption, availability, integrity, confidentiality) and common hardening (rate limits, headers, input validation, secure token handling).

---

## 2. Cryptography

### 2.1 Key Derivation (KDF)

- **New shares**: **Argon2id** (memory-hard, resistant to GPU/ASIC). Parameters:
  - Memory: 64 MiB (65536 KiB)
  - Time cost: 3
  - Parallelism: 4
  - Hash length: 32 bytes (AES-256 key)
  - Salt: 16 bytes, CSPRNG, stored in `crypto_meta.salt`
- **Legacy shares**: **PBKDF2-SHA256** (600,000 iterations) is supported for decryption when `kdf.type === 'pbkdf2'`. New uploads always use Argon2id.

### 2.2 Symmetric Encryption

- **Algorithm**: AES-256-GCM (authenticated encryption).
- **File key**: 32-byte random key encrypts the file. This key is then wrapped (encrypted) with the key derived from the passcode (Argon2id or PBKDF2).
- **Nonces**: 12-byte random IV per encryption; never reused.
- **Storage**: Server stores only ciphertext, wrapped key, nonces, and salt in `crypto_meta` / `kdf`. No key material or passcode is stored.

### 2.3 Pickup Code & Token Security

- **Pickup code**: Format `XXXX-XXXXXX` (e.g. `K7P4-839217`). Stored as **HMAC-SHA256**(code, app key). Verification uses `hash_equals` (constant-time).
- **Download token**: 64 random bytes per redeem. Stored as **HMAC-SHA256**(token, app key) so tokens are bound to the application secret and not guessable offline.

---

## 3. Backend Hardening

### 3.1 Rate Limiting (per IP, per minute)

| Endpoint              | Limit (default) | Purpose                    |
|-----------------------|----------------|----------------------------|
| POST /api/v1/shares   | 30             | Abuse / resource control   |
| POST upload           | 20             | Abuse / storage            |
| POST /api/v1/redeem   | 15             | Brute-force pickup codes   |
| GET /api/v1/download  | 30             | Bandwidth / abuse           |
| POST download/confirm| 20             | Brute-force passcode tries  |

Configurable via `config/otshare.php` and env (e.g. `OTSHARE_RATE_LIMIT_REDEEM`).

### 3.2 Security Headers

All API responses send:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `X-XSS-Protection: 1; mode=block`
- `Permissions-Policy`: restrict geolocation, microphone, camera
- `Strict-Transport-Security` (when request is HTTPS)

Applied via `SecurityHeaders` middleware to the API prefix.

### 3.3 Input Validation

- **Pickup code**: Required, max 32 chars, format `[A-Za-z0-9]{1,10}-[0-9]{1,10}` (no script or path chars).
- **crypto_meta**: Required object; total JSON size limited (`otshare.max_crypto_meta_size`, default 4096 bytes).
- **original_name**: Optional, max 255 chars, regex `^[\w\s.-]+$` (no path traversal).
- **mime**: Optional, max 128 chars, sane MIME regex.

### 3.4 No Sensitive Data in Responses or Logs

- Redeem returns the same generic message for invalid, expired, or missing pickup code: *"Invalid or expired pickup code."*
- Download/confirm errors do not reflect the real token or passcode.
- Controllers do not log passcodes, full tokens, or full pickup codes.

### 3.5 Passcode Attempts and File Deletion

- After **N** wrong passcode attempts (configurable, default 3), the file is **permanently deleted** and the share is expired.
- Download token remains valid until expiry so the client can see “No attempts left” and a clean error state.

---

## 4. Frontend Hardening

- **Download filename**: Sanitized (non-alphanumeric replaced, length capped) to avoid path traversal when saving the file.
- **No passcode or key material** sent to the server; only ciphertext and metadata.

---

## 5. SOC2 Mapping (Summary)

| Trust principle   | How we address it                                                                 |
|------------------|-----------------------------------------------------------------------------------|
| Security         | Argon2id/PBKDF2, AES-256-GCM, HMAC for codes/tokens, rate limits, security headers |
| Availability     | Rate limiting to reduce abuse; no single point where passcode is verified on server |
| Processing       | Input validation, size limits, format checks                                     |
| Confidentiality | E2E encryption; server never sees plaintext or passcode                          |
| Privacy          | No user accounts; minimal data stored; file deleted after failed passcode attempts |

---

## 6. Running Security & Functional Tests

### 6.1 Backend (PHPUnit)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan test
```

Relevant test suites:

- **Tests\Feature\ApiSecurityTest**: Security headers, redeem message consistency, pickup code validation, no token/pickup leak in errors, crypto_meta size limit, original_name validation.
- **Tests\Feature\ShareFlowTest**: Full create → upload → redeem → download → confirm flow; passcode failure exhaustion and file deletion.

### 6.2 Frontend (Vitest)

```bash
npm install
npm run test
```

- In **Node**, only PBKDF2 and `deriveWrapKey` tests run (Argon2 and `downloadBlob` need browser/WASM and DOM).
- Argon2 roundtrip and wrong-passcode behavior are covered by the same code paths; run the app in a browser to validate encrypt/decrypt with Argon2id manually if desired.

### 6.3 Full Stack

1. `php artisan test` — all backend tests.
2. `npm run test` — JS unit tests (PBKDF2 + derive key).
3. Manual: Upload a file, redeem code, download and decrypt in browser to confirm Argon2id E2E.

---

## 7. Deployment Checklist

- [ ] **HTTPS only** in production (HSTS will then be applied).
- [ ] **APP_KEY** set and kept secret; used for pickup hash and token HMAC.
- [ ] **OTSHARE_*** env vars reviewed (rate limits, expiry, max file size, max crypto_meta size).
- [ ] Storage (local or S3) for encrypted blobs is not publicly readable; access only via app.
- [ ] Logs and monitoring do not capture request/response bodies that could contain tokens or codes.
- [ ] After changing token hashing to HMAC, existing download tokens created before the change are invalid (by design).

---

## 8. Loopholes Addressed

- **Brute-force pickup code**: Rate limiting + HMAC storage (no offline check).
- **Brute-force passcode**: Rate limiting on confirm + limited attempts + file deletion after N failures.
- **Token leakage**: Tokens never in error bodies or logs; HMAC prevents guessing.
- **Info leakage on redeem**: Same message for invalid/expired/missing.
- **Path traversal**: original_name validated; download filename sanitized in client.
- **Oversized payloads**: crypto_meta size limit; file size limit.
- **Weak KDF**: Argon2id for new shares; PBKDF2 with high iterations for legacy.

---

## 9. File Reference

| Area        | Files |
|------------|--------|
| Crypto     | `resources/js/crypto.js` (Argon2id + PBKDF2, AES-GCM, deriveWrapKey, decryptFile with kdf) |
| Backend    | `app/Http/Controllers/Api/V1/*`, `app/Services/ShareTokenService.php`, `app/Services/PickupCodeService.php` |
| Security   | `app/Http/Middleware/SecurityHeaders.php`, `app/Providers/AppServiceProvider.php` (rate limiters), `config/otshare.php` |
| Tests      | `tests/Feature/ApiSecurityTest.php`, `tests/Feature/ShareFlowTest.php`, `resources/js/crypto.test.js` |

This should give you a single place to understand and prove that the system is highly secure and aligned with SOC2-style controls.
