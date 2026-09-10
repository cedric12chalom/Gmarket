import { useState } from 'react'
import { Palette } from 'lucide-react'

const PRESET_COLORS = [
  { name: 'Blanc', hex: '#ffffff' },
  { name: 'Noir', hex: '#1a1a1a' },
  { name: 'Rouge', hex: '#dc2626' },
  { name: 'Bleu', hex: '#2563eb' },
  { name: 'Vert', hex: '#16a34a' },
  { name: 'Jaune', hex: '#eab308' },
  { name: 'Rose', hex: '#ec4899' },
  { name: 'Orange', hex: '#ea580c' },
  { name: 'Violet', hex: '#7c3aed' },
  { name: 'Gris', hex: '#6b7280' },
  { name: 'Beige', hex: '#d4a574' },
  { name: 'Marron', hex: '#92400e' },
]

interface Props {
  imageUrl: string
  alt?: string
  className?: string
}

/**
 * Changeur de couleur de t-shirt sur fond blanc.
 * Utilise mix-blend-mode CSS pour teinter la zone blanche de l'image.
 * Visible uniquement quand l'image a un fond blanc (détecté ou choisi par l'utilisateur).
 */
export default function TShirtColorChanger({ imageUrl, alt = '', className = '' }: Props) {
  const [activeColor, setActiveColor] = useState('#ffffff')
  const [showPicker, setShowPicker] = useState(false)

  const isOriginal = activeColor === '#ffffff'

  return (
    <div className={`relative ${className}`}>
      {/* Image produit */}
      <div className="relative overflow-hidden rounded-2xl bg-white">
        <img src={imageUrl} alt={alt} className="w-full h-full object-contain relative z-10" />

        {/* Overlay couleur — mix-blend-mode teinte les zones blanches */}
        {!isOriginal && (
          <div className="absolute inset-0 z-20 pointer-events-none" style={{
            backgroundColor: activeColor,
            mixBlendMode: 'multiply',
          }} />
        )}

        {/* Deuxième couche pour éclaircir (mode screen) afin de ne pas assombrir trop */}
        {!isOriginal && (
          <div className="absolute inset-0 z-20 pointer-events-none" style={{
            backgroundColor: activeColor,
            mixBlendMode: 'screen',
            opacity: 0.3,
          }} />
        )}
      </div>

      {/* Bouton toggle palette */}
      <button type="button" onClick={() => setShowPicker(!showPicker)}
        className="absolute top-3 right-3 z-30 bg-white/90 backdrop-blur rounded-full p-2 shadow-lg hover:bg-white transition"
        title="Changer la couleur">
        <Palette className="w-5 h-5 text-primary" />
      </button>

      {/* Picker couleurs */}
      {showPicker && (
        <div className="absolute bottom-3 left-3 right-3 z-30 bg-white/95 backdrop-blur rounded-2xl p-4 shadow-xl">
          <p className="text-xs font-semibold text-gray-700 mb-3">Simuler cette couleur sur le t-shirt :</p>
          <div className="flex flex-wrap gap-2">
            {PRESET_COLORS.map((c) => (
              <button key={c.hex} type="button" onClick={() => setActiveColor(c.hex)}
                className={`group flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border-2 transition ${activeColor === c.hex ? 'border-primary shadow' : 'border-gray-200 hover:border-gray-300'}`}>
                <span className="w-4 h-4 rounded-full border border-gray-200" style={{ backgroundColor: c.hex }} />
                <span className="text-gray-700">{c.name}</span>
              </button>
            ))}
          </div>
          {!isOriginal && (
            <p className="text-xs text-gray-400 mt-2 italic">Aperçu virtuel — la couleur réelle peut varier.</p>
          )}
        </div>
      )}
    </div>
  )
}
