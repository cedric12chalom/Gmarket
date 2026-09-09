import React from 'react';
import { FreshnessIndex } from '@/types';
import { freshnessLabel } from '@/utils/helpers';

interface FreshnessBadgeProps {
  index: FreshnessIndex;
  size?: 'sm' | 'md';
}

/**
 * TerraLink's signature freshness indicator: a small ring gauge rather than a
 * generic colored pill. This is the one visual element every product carries,
 * everywhere (catalogue card, detail page, lot QR display) — kept identical
 * across all three so it reads as a recognizable mark, not just a status color.
 */
const LEVELS: Record<FreshnessIndex, { fill: number; ring: string; dot: string }> = {
  tres_frais: { fill: 1, ring: '#4a6b33', dot: '#4a6b33' },   // full ring, deep olive
  frais: { fill: 0.66, ring: '#82a468', dot: '#82a468' },      // 2/3 ring, mid olive
  a_consommer: { fill: 0.33, ring: '#c9622a', dot: '#c9622a' },// 1/3 ring, terracotta
  expire: { fill: 0.08, ring: '#9f1d1d', dot: '#9f1d1d' },     // near-empty, warning red
};

export const FreshnessBadge: React.FC<FreshnessBadgeProps> = ({ index, size = 'md' }) => {
  const { fill, ring, dot } = LEVELS[index];
  const dimension = size === 'sm' ? 20 : 26;
  const stroke = size === 'sm' ? 3 : 3.5;
  const radius = (dimension - stroke) / 2;
  const circumference = 2 * Math.PI * radius;

  return (
    <div className="inline-flex items-center gap-1.5 bg-white/90 backdrop-blur-sm pl-1.5 pr-2.5 py-1 rounded-full shadow-sm">
      <svg width={dimension} height={dimension} viewBox={`0 0 ${dimension} ${dimension}`} className="-rotate-90 flex-shrink-0">
        <circle cx={dimension / 2} cy={dimension / 2} r={radius} fill="none" stroke="#e7e5e4" strokeWidth={stroke} />
        <circle
          cx={dimension / 2} cy={dimension / 2} r={radius} fill="none"
          stroke={ring} strokeWidth={stroke} strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={circumference * (1 - fill)}
        />
        <circle cx={dimension / 2} cy={dimension / 2} r={2} fill={dot} />
      </svg>
      <span className="font-display text-xs font-semibold" style={{ color: ring }}>
        {freshnessLabel(index)}
      </span>
    </div>
  );
};
