export function SecurityTrust() {
  const points = [
    {
      title: 'Encrypted in your browser',
      detail: 'Your file is encrypted with AES-256-GCM before it leaves your device. We only ever receive ciphertext.',
      icon: 'lock',
    },
    {
      title: 'We never see your passcode',
      detail: 'Decryption happens in your browser. The key is derived from your passcode using Argon2id — industry-standard, memory-hard key derivation.',
      icon: 'key',
    },
    {
      title: 'No account required',
      detail: 'No sign-up, no email, no tracking. Share with a one-time pickup code and passcode only.',
      icon: 'user',
    },
    {
      title: 'One download, then gone',
      detail: 'Each share allows a single successful download. After that, or after expiry, the file is no longer available.',
      icon: 'clock',
    },
    {
      title: 'Wrong passcode protection',
      detail: 'After a limited number of wrong passcode attempts, the file is permanently deleted. No brute force.',
      icon: 'shield',
    },
  ];

  const badges = [
    'AES-256-GCM',
    'Argon2id',
    'E2E encrypted',
    'No server access to keys',
  ];

  return (
    <div className="otshare-card p-6 sm:p-8">
      <div className="text-center mb-6">
        <h2 className="text-xl font-semibold text-white mb-2">Security &amp; trust</h2>
        <p className="text-zinc-500 text-sm">
          Built so you can share sensitive files without trusting the server with your data.
        </p>
      </div>

      <div className="flex flex-wrap gap-2 justify-center mb-8">
        {badges.map((b) => (
          <span
            key={b}
            className="px-3 py-1.5 rounded-lg bg-violet-500/10 border border-violet-500/20 text-violet-300 text-xs font-medium"
          >
            {b}
          </span>
        ))}
      </div>

      <ul className="space-y-4">
        {points.map((p) => (
          <li key={p.title} className="flex gap-4">
            <div className="w-10 h-10 rounded-xl bg-white/[0.06] border border-white/[0.08] flex items-center justify-center shrink-0">
              <TrustIcon name={p.icon} className="w-5 h-5 text-violet-400" />
            </div>
            <div>
              <h3 className="text-sm font-semibold text-white mb-0.5">{p.title}</h3>
              <p className="text-zinc-500 text-sm leading-relaxed">{p.detail}</p>
            </div>
          </li>
        ))}
      </ul>

      <p className="mt-6 text-zinc-600 text-xs text-center">
        For full technical details and SOC2 alignment, see the project&apos;s SECURITY.md.
      </p>
    </div>
  );
}

function TrustIcon({ name, className }) {
  const icons = {
    lock: (
      <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
    ),
    key: (
      <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
      </svg>
    ),
    user: (
      <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
    ),
    clock: (
      <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    shield: (
      <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
      </svg>
    ),
  };
  return icons[name] ?? icons.lock;
}
