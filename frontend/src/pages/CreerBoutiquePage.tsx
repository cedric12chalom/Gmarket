import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Store, Check } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import ColorPalettePicker from '../components/ColorPalettePicker'
import api from '../services/api'

interface Theme {
  code: string
  libelle: string
  rendu: string
  accent: string
}

export default function CreerBoutiquePage() {
  const { user, fetchMe } = useAuthStore()
  const navigate = useNavigate()
  const [themes, setThemes] = useState<Theme[]>([])
  const [nom, setNom] = useState('')
  const [description, setDescription] = useState('')
  const [selectedTheme, setSelectedTheme] = useState('classique')
  const [customColors, setCustomColors] = useState<any>(null)
  const [tiktok, setTiktok] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const maBoutique = user?.boutiques?.[0]
  const isEdit = !!maBoutique

  useEffect(() => { api.get('/api/themes-boutique').then((r) => setThemes(r.data.themes)) }, [])

  // Pré-remplir en mode édition
  useEffect(() => {
    if (maBoutique) {
      setNom(maBoutique.nom)
      setDescription((maBoutique as any).description ?? '')
      setSelectedTheme(maBoutique.theme)
      setCustomColors((maBoutique as any).customColors ?? null)
      setTiktok(maBoutique.tiktokPseudo ?? '')
    }
  }, [maBoutique])

  if (!user) return <div className="text-center py-20 text-gray-400">Connectez-vous pour créer votre boutique.</div>

  const isVendeur = user.roles.includes('ROLE_VENDEUR')
  if (!isVendeur) return (
    <div className="text-center py-20">
      <Store className="w-12 h-12 text-gray-300 mx-auto mb-4" />
      <p className="text-gray-500 mb-4">Vous devez être inscrit en tant que vendeur pour créer une boutique.</p>
      <p className="text-sm text-gray-400">Inscrivez-vous avec le rôle "Vendeur" pour accéder à cette fonctionnalité.</p>
    </div>
  )

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const payload: any = {
        nom,
        description: description || null,
        theme: selectedTheme,
        tiktokPseudo: tiktok || null,
      }
      if (customColors) payload.customColors = customColors
      const r = isEdit
        ? await api.put(`/api/boutique/${maBoutique.id}`, payload)
        : await api.post('/api/boutique', payload)
      await fetchMe()
      navigate(`/boutique/${r.data.boutique.slug}`)
    } catch (err: any) {
      setError(err.response?.data?.error ?? 'Erreur lors de la création.')
    } finally {
      setLoading(false)
    }
  }

  const selectedThemeData = themes.find((t) => t.code === selectedTheme)

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="text-3xl font-bold text-primary mb-2">{isEdit ? 'Personnaliser ma boutique' : 'Créer ma boutique'}</h1>
      <p className="text-gray-500 mb-8">Choisissez un thème et personnalisez l'apparence de votre boutique.</p>

      <form onSubmit={submit} className="space-y-8">
        {error && <div className="bg-red-50 text-red-600 p-4 rounded-xl text-sm">{error}</div>}

        {/* Infos de base */}
        <div className="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
          <h2 className="font-bold text-primary">Informations</h2>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nom de la boutique *</label>
            <input required value={nom} onChange={(e) => setNom(e.target.value)} maxLength={120}
              className="w-full border rounded-xl px-4 py-3 text-sm outline-none focus:border-accent"
              placeholder="Ex : Dakar Sneakers, Mode by Aïcha..." />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={3}
              className="w-full border rounded-xl px-4 py-3 text-sm outline-none focus:border-accent resize-none"
              placeholder="Décrivez votre univers mode..." />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Pseudo TikTok</label>
            <input value={tiktok} onChange={(e) => setTiktok(e.target.value)}
              className="w-full border rounded-xl px-4 py-3 text-sm outline-none focus:border-accent"
              placeholder="@votre.pseudo" />
          </div>
        </div>

        {/* Sélection thème */}
        <div className="bg-white rounded-2xl border border-gray-100 p-6">
          <h2 className="font-bold text-primary mb-4">Choisir un thème</h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {themes.map((t) => (
              <button key={t.code} type="button" onClick={() => setSelectedTheme(t.code)}
                className={`p-4 rounded-xl border-2 text-left transition ${selectedTheme === t.code ? 'border-accent bg-accent/5' : 'border-gray-200 hover:border-gray-300'}`}>
                <div className="flex items-center gap-2 mb-2">
                  <span className="w-4 h-4 rounded-full border" style={{ backgroundColor: t.accent }} />
                  {selectedTheme === t.code && <Check className="w-3.5 h-3.5 text-accent ml-auto" />}
                </div>
                <p className="font-semibold text-sm text-primary">{t.libelle}</p>
                <p className="text-xs text-gray-500 mt-1 line-clamp-2">{t.rendu}</p>
              </button>
            ))}
          </div>

          {selectedThemeData && (
            <div className="mt-4 p-4 rounded-xl bg-gray-50 flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl flex items-center justify-center text-white text-lg font-bold" style={{ backgroundColor: selectedThemeData.accent }}>
                {nom ? nom[0].toUpperCase() : 'B'}
              </div>
              <div>
                <p className="font-semibold text-sm text-primary">{nom || 'Ma Boutique'}</p>
                <p className="text-xs text-gray-500">Thème {selectedThemeData.libelle}</p>
              </div>
            </div>
          )}
        </div>

        {/* Personnalisation couleurs */}
        <div className="bg-white rounded-2xl border border-gray-100 p-6">
          <ColorPalettePicker value={customColors} onChange={setCustomColors} themeAccent={selectedThemeData?.accent} />
        </div>

        <button type="submit" disabled={loading || !nom.trim()}
          className="w-full bg-primary text-white py-4 rounded-xl font-semibold hover:bg-slate-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
          <Store className="w-5 h-5" />
          {loading ? (isEdit ? 'Enregistrement...' : 'Création...') : (isEdit ? 'Enregistrer les modifications' : 'Créer ma boutique')}
        </button>
      </form>
    </div>
  )
}
