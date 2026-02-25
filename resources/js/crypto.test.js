import { describe, it, expect } from 'vitest';
import {
  encryptFile,
  decryptFile,
  deriveWrapKeyPBKDF2,
  deriveWrapKey,
  downloadBlob,
} from './crypto';

const isNode = typeof process !== 'undefined' && process.versions?.node;

describe('crypto', () => {
  const passcode = 'test-passcode-123';
  const plaintext = new TextEncoder().encode('Hello, secret file!');

  describe('deriveWrapKeyPBKDF2', () => {
    it('returns a CryptoKey', async () => {
      const salt = new Uint8Array(16);
      crypto.getRandomValues(salt);
      const key = await deriveWrapKeyPBKDF2(passcode, salt);
      expect(key).toBeInstanceOf(CryptoKey);
      expect(key.type).toBe('secret');
      expect(key.algorithm.name).toBe('AES-GCM');
    });
  });

  describe('deriveWrapKey', () => {
    it('with kdf type pbkdf2 returns a CryptoKey', async () => {
      const salt = new Uint8Array(16);
      crypto.getRandomValues(salt);
      const key = await deriveWrapKey(passcode, salt, { type: 'pbkdf2', iterations: 1000 });
      expect(key).toBeInstanceOf(CryptoKey);
      expect(key.algorithm.name).toBe('AES-GCM');
    });
  });

  describe('encryptFile / decryptFile', () => {
    it.skipIf(isNode)('roundtrips with correct passcode (Argon2id)', async () => {
      const { ciphertext, crypto_meta, kdf } = await encryptFile(plaintext.buffer, passcode);
      expect(kdf.type).toBe('argon2id');
      const decrypted = await decryptFile(ciphertext, passcode, crypto_meta, kdf);
      expect(new Uint8Array(decrypted)).toEqual(new Uint8Array(plaintext));
    }, 30000);

    it.skipIf(isNode)('throws on wrong passcode', async () => {
      const { ciphertext, crypto_meta, kdf } = await encryptFile(plaintext.buffer, passcode);
      await expect(
        decryptFile(ciphertext, 'wrong-passcode', crypto_meta, kdf)
      ).rejects.toThrow();
    }, 30000);

    it.skipIf(isNode)('decrypt with kdf null uses PBKDF2; Argon2id payload then fails', async () => {
      const { ciphertext, crypto_meta } = await encryptFile(plaintext.buffer, passcode);
      await expect(
        decryptFile(ciphertext, passcode, crypto_meta, null)
      ).rejects.toThrow();
    }, 30000);
  });

  describe('downloadBlob', () => {
    it.skipIf(isNode)('sanitizes filename and does not throw', () => {
      const blob = new Blob(['x']);
      expect(() => downloadBlob(blob, '../../../etc/passwd')).not.toThrow();
    });
  });
});
