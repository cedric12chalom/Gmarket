import React from 'react';

interface LogoProps {
  className?: string;
}

/**
 * TerraLink mark: a leaf (growth, freshness) whose stem connects to two linked
 * nodes — the "Link" in TerraLink, representing producer and acheteur connected
 * through the platform. Kept as a single component so it renders identically
 * wherever it's used (navbar, sidebar, auth screens) instead of drifting.
 */
export const Logo: React.FC<LogoProps> = ({ className = 'w-9 h-9' }) => (
  <svg viewBox="0 0 100 100" className={className} xmlns="http://www.w3.org/2000/svg">
    <path
      d="M52 90
         C24 86 14 58 26 32
         C36 12 54 6 70 12
         C80 16 84 26 80 38
         C72 62 58 78 52 90
         Z"
      fill="#4a6b33"
    />
    <path
      d="M68 14 C50 34 40 58 32 82"
      fill="none" stroke="#f4f7f0" strokeWidth="3.5" strokeLinecap="round" opacity="0.55"
    />
    <path d="M32 82 C29 87 24 90 18 91" fill="none" stroke="#4a6b33" strokeWidth="3.5" strokeLinecap="round" />
    <circle cx="18" cy="91" r="6.5" fill="#c9622a" />
    <path d="M24 90 L36 88" stroke="#c9622a" strokeWidth="3" strokeLinecap="round" />
    <circle cx="40" cy="87" r="4" fill="#e07f47" />
  </svg>
);
