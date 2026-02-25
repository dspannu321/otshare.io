import { useState } from 'react';
import { decryptFile, downloadBlob } from '../crypto';
import { PickupCodeInput } from './CharBoxInput';
import { redeem as apiRedeem, download as apiDownload, confirmDownload } from '../api';

export function DownloadFlow({ apiBase }) {
  const [pickupCode, setPickupCode] = useState('');
  const [passcode, setPasscode] = useState('');
  const [step, setStep] = useState('enter_code');
  const [redeemData, setRedeemData] = useState(null);
  const [error, setError] = useState('');
  const [attemptsLeft, setAttemptsLeft] = useState(null);
  const [expired, setExpired] = useState(false);

  const pickupCodeForApi = pickupCode.length >= 10
    ? pickupCode.slice(0, 4) + '-' + pickupCode.slice(4, 10)
    : pickupCode;

  const handleRedeem = async (e) => {
    e.preventDefault();
    const code = pickupCodeForApi.replace(/\s/g, '').trim();
    if (code.length < 10) {
      setError('Enter the full pickup code (e.g. K7P4-839217).');
      return;
    }
    setError('');
    setAttemptsLeft(null);
    setExpired(false);
    try {
      const data = await apiRedeem(apiBase, code);
      setRedeemData(data);
      setStep('enter_passcode');
    } catch (err) {
      setError(err.message || 'Redeem failed.');
    }
  };

  const handleDecryptAndDownload = async (e) => {
    e.preventDefault();
    const trimmed = passcode.trim();
    if (!trimmed || !redeemData) return;
    setError('');
    setStep('downloading');
    setAttemptsLeft(null);
    setExpired(false);
    try {
      const ciphertext = await apiDownload(apiBase, redeemData.download_token);
      let plaintext;
      try {
        plaintext = await decryptFile(ciphertext, trimmed, redeemData.crypto_meta, redeemData.kdf);
      } catch (decryptErr) {
        const confirmData = await confirmDownload(apiBase, redeemData.download_token, false);
        setAttemptsLeft(confirmData.attempts_left ?? 0);
        setExpired(confirmData.expired === true);
        setError(confirmData.expired
          ? 'No attempts left. The file has been permanently deleted.'
          : 'Wrong passcode.'
        );
        setPasscode('');
        setStep('enter_passcode');
        return;
      }
      await confirmDownload(apiBase, redeemData.download_token, true);
      const blob = new Blob([plaintext], { type: redeemData.mime || 'application/octet-stream' });
      downloadBlob(blob, redeemData.original_name || 'download');
      setStep('done');
    } catch (err) {
      setError(err.message || 'Something went wrong.');
      setStep('enter_passcode');
    }
  };

  const reset = () => {
    setPickupCode('');
    setPasscode('');
    setRedeemData(null);
    setStep('enter_code');
    setError('');
    setAttemptsLeft(null);
    setExpired(false);
  };

  if (step === 'done') {
    return (
      <div className="otshare-card p-6 sm:p-8">
        <div className="flex items-start gap-4 mb-6">
          <div className="w-12 h-12 rounded-2xl bg-violet-500/20 flex items-center justify-center shrink-0">
            <svg className="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <h2 className="text-xl font-semibold text-white mb-1">Download complete</h2>
            <p className="text-zinc-500 text-sm">File decrypted and saved to your device.</p>
          </div>
        </div>
        <button type="button" onClick={reset} className="otshare-btn-secondary w-full">
          Get another file
        </button>
      </div>
    );
  }

  if (step === 'enter_passcode') {
    const exhausted = expired || attemptsLeft === 0;
    return (
      <form onSubmit={handleDecryptAndDownload} className="otshare-card p-6 sm:p-8">
        {exhausted ? (
          <div className="mb-4 rounded-xl bg-red-500/10 border border-red-500/20 p-5">
            <p className="text-red-400 font-medium">No attempts left</p>
            <p className="text-zinc-400 text-sm mt-1">The file has been permanently deleted for security. Ask the sender to share it again.</p>
            <button type="button" onClick={reset} className="mt-4 otshare-btn-secondary w-full">
              Enter a different code
            </button>
          </div>
        ) : (
          <>
            <p className="text-zinc-400 text-sm mb-4">
              Enter the <strong className="text-zinc-300">lock passcode</strong> the sender gave you.
            </p>
            <input
              type="password"
              value={passcode}
              onChange={(e) => setPasscode(e.target.value)}
              placeholder="Passcode"
              className="otshare-input mb-4"
              autoComplete="off"
            />
            {attemptsLeft != null && attemptsLeft > 0 && (
              <p className="text-violet-400/90 text-sm mb-2">{attemptsLeft} attempt(s) left.</p>
            )}
            {error && (
              <div className="mb-4 flex items-center gap-2 rounded-xl bg-red-500/10 border border-red-500/20 px-4 py-3 text-red-400 text-sm">
                <svg className="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {error}
              </div>
            )}
            <div className="flex gap-3">
              <button type="button" onClick={reset} className="otshare-btn-secondary shrink-0">
                Back
              </button>
              <button
                type="submit"
                disabled={step === 'downloading' || !passcode.trim()}
                className="otshare-btn-primary flex-1 flex items-center justify-center gap-2"
              >
                {step === 'downloading' ? (
                  <>
                    <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Decrypting…
                  </>
                ) : (
                  'Decrypt and download'
                )}
              </button>
            </div>
          </>
        )}
      </form>
    );
  }

  return (
    <form onSubmit={handleRedeem} className="otshare-card p-6 sm:p-8">
      <label className="block text-sm font-semibold text-zinc-400 mb-3">Pickup code</label>
      <PickupCodeInput value={pickupCode} onChange={setPickupCode} className="mb-3" />
      <p className="text-zinc-600 text-xs mb-5 text-center">
        Format: XXXX-XXXXXX (e.g. K7P4-839217). Then enter the lock passcode from the sender.
      </p>
      {error && (
        <div className="mb-5 flex items-center gap-2 rounded-xl bg-red-500/10 border border-red-500/20 px-4 py-3 text-red-400 text-sm">
          <svg className="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {error}
        </div>
      )}
      <button
        type="submit"
        disabled={pickupCode.length < 10}
        className="otshare-btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Continue
      </button>
    </form>
  );
}
