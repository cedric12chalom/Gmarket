import { useState } from 'react'
import { Palette } from 'lucide-react'

interface PaletteColors {
  accent: string
  primary: string
  secondary: string
  background: string
}

interface Props {
  value: PaletteColors | null
  onChange: (colors: PaletteColors | null) => void
  themeAccent?: string
}

const PRESETS: { name: string; colors: PaletteColors }[] = [
  { name: 'Classique', colors: { accent: '#334155', primary: '#1e293b', secondary: '#64748b', background: '#ffffff' } },
  { name: 'Sneakers', colors: { accent: '#facc15', primary: '#1e293b', secondary: '#71717a', background: '#ffffff' } },
  { name: 'Lingerie', colors: { accent: '#f9a8d4', primary: '#831843', secondary: '#a1a1aa', background: '#fdf2f8' } },
  { name: 'Streetwear', colors: { accent: '#0f172a', primary: '#0f172a', secondary: '#ef4444', background: '#ffffff' } },
  { name: 'Élégance', colors: { accent: '#1e293b', primary: '#0f172a', secondary: '#94a3b8', background: '#f8fafc' } },
  { name: 'Minimaliste', colors: { accent: '#94a3b8', primary: '#1e293b', secondary: '#cbd5e1', background: '#ffffff' } },
  { name: 'Vintage', colors: { accent: '#b45309', primary: '#451a03', secondary: '#a16207', background: '#fffbeb' } },
  { name: 'Premium', colors: { accent: '#d4af37', primary: '#0f172a', secondary: '#a3a3a3', background: '#fafaf9' } },
  { name: 'Tropical', colors: { accent: '#10b981', primary: '#064e3b', secondary: '#34d399', background: '#ecfdf5' } },
  { name: 'Neon', colors: { accent: '#a855f7', primary: '#1e1b4b', secondary: '#c084fc', background: '#ffffff' } },
]

const FIELDS: { key: keyof PaletteColors; label: string }[] = [
  { key: 'accent', label: 'Accent (boutons, highlights)' },
  { key: 'primary', label: 'Principal (textes, en-tête)' },
  { key: 'secondary', label: 'Secondaire (sous-titres)' },
  { key: 'background', label: 'Arrière-plan' },
]

export default function ColorPalettePicker({ value, onChange, themeAccent }: Props) {
  const [custom, setCustom] = useState<PaletteColors>(value ?? {
    accent: themeAccent ?? '#334155',
    primary: '#1e293b',
    secondary: '#64748b',
    background: '#ffffff',
  })
  const [useCustom, setUseCustom] = useState(!!value)

  const applyPreset = (preset: PaletteColors) => {
    setCustom(preset)
    if (useCustom) onChange(preset)
  }

  const updateField = (key: keyof PaletteColors, val: string) => {
    const next = { ...custom, [key]: val }
    setCustom(next)
    if (useCustom) onChange(next)
  }

  const toggleCustom = () => {
    const next = !useCustom
    setUseCustom(next)
    onChange(next ? custom : null)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold text-primary flex items-center gap-2">
          <Palette className="w-4 h-4" /> Personnalisation des couleurs
        </h3>
        <button type="button" onClick={toggleCustom}
          className={`relative w-12 h-6 rounded-full transition-colors ${useCustom ? 'bg-accent' : 'bg-gray-300'}`}>
          <span className={`absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${useCustom ? 'left-6' : 'left-0.5'}`} />
        </button>
      </div>

      {useCustom && (
        <>
          {/* Presets rapides */}
          <div>
            <p className="text-xs text-gray-500 mb-2">Palettes prédéfinies :</p>
            <div className="flex flex-wrap gap-2">
              {PRESETS.map((p) => (
                <button key={p.name} type="button" onClick={() => applyPreset(p.colors)}
                  className="flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-200 text-xs hover:border-accent transition">
                  <span className="flex -space-x-1">
                    {Object.values(p.colors).slice(0, 3).map((c, i) => (
                      <span key={i} className="w-3 h-3 rounded-full border border-white" style={{ backgroundColor: c }} />
                    ))}
                  </span>
                  {p.name}
                </button>
              ))}
            </div>
          </div>

          {/* Couleurs manuelles */}
          <div className="grid grid-cols-2 gap-3">
            {FIELDS.map(({ key, label }) => (
              <div key={key}>
                <label className="text-xs text-gray-600 mb-1 block">{label}</label>
                <div className="flex items-center gap-2 border rounded-xl px-2 py-1.5">
                  <input type="color" value={custom[key]} onChange={(e) => updateField(key, e.target.value)}
                    className="w-6 h-6 cursor-pointer border-0 p-0" />
                  <input type="text" value={custom[key]} onChange={(e) => updateField(key, e.target.value)}
                    className="flex-1 text-xs outline-none bg-transparent font-mono" />
                </div>
              </div>
            ))}
          </div>

          {/* Preview */}
          <div className="rounded-2xl border border-gray-200 overflow-hidden">
            <div className="p-4" style={{ backgroundColor: custom.background }}>
              <div className="flex items-center gap-3 mb-3">
                <div className="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold text-white" style={{ backgroundColor: custom.primary }}>
                  B
                </div>
                <div>
                  <p className="font-bold text-sm" style={{ color: custom.primary }}>Ma Boutique</p>
                  <p className="text-xs" style={{ color: custom.secondary }}>Aperçu du rendu</p>
                </div>
              </div>
              <button type="button" className="px-4 py-2 rounded-xl text-sm font-semibold text-white" style={{ backgroundColor: custom.accent }}>
                Bouton accent
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
