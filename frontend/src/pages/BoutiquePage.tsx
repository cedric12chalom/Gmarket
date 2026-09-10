import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { Star, Music } from 'lucide-react'
import api from '../services/api'

export default function BoutiquePage() {
  const { slug } = useParams()
  const [boutique, setBoutique] = useState<any>(null)
  const [produits, setProduits] = useState<any[]>([])
  const [avis, setAvis] = useState<any>(null)

  useEffect(() => {
    if (!slug) return
    api.get(`/api/boutique/${slug}`).then((r) => {
      const b = r.data.boutique
      setBoutique(b)
      api.get(`/api/produits?boutique=${b.id}`).then((r2) => setProduits(r2.data.produits))
      api.get(`/api/boutiques/${b.id}/avis`).then((r3) => setAvis(r3.data))
    })
  }, [slug])

  if (!boutique) return <div className="text-center py-20 text-gray-400">Chargement...</div>

  const palette = boutique.palette ?? {
    accent: boutique.themeAccent ?? '#1e293b',
    primary: '#1e293b',
    secondary: '#64748b',
    background: '#ffffff',
  }

  const sectionStyle = {
    backgroundColor: palette.background,
    border: '1px solid ' + palette.accent + '20',
  }

  return (
    <div style={{ backgroundColor: palette.background }}>
      {/* Bannière */}
      <div className="relative h-48 md:h-64 rounded-2xl overflow-hidden mb-6">
        <img src={boutique.banniere} alt={boutique.nom} className="w-full h-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
        <div className="absolute bottom-4 left-4 flex items-end gap-4">
          <img src={boutique.logo} alt="Logo" className="w-16 h-16 rounded-xl border-2 border-white shadow" />
          <div className="text-white">
            <h1 className="text-2xl font-bold">{boutique.nom}</h1>
            <div className="flex items-center gap-3 text-sm text-white/80 mt-1">
              {boutique.tiktokPseudo && <span className="flex items-center gap-1"><Music className="w-3 h-3" />{boutique.tiktokPseudo}</span>}
              {avis?.moyenne > 0 && <span className="flex items-center gap-1"><Star className="w-3 h-3 fill-accent text-accent" style={{ color: palette.accent }} />{avis.moyenne}/5</span>}
            </div>
          </div>
        </div>
      </div>

      {/* Description */}
      {boutique.description && <p className="text-gray-600 mb-6">{boutique.description}</p>}

      {/* Avis */}
      {avis?.avis?.length > 0 && (
        <div className="bg-white rounded-2xl p-6 border border-gray-100 mb-8" style={sectionStyle}>
          <h3 className="font-bold text-primary mb-4" style={{ color: palette.primary }}>Avis ({avis.count})</h3>
          <div className="space-y-3">
            {avis.avis.map((a: any) => (
              <div key={a.id} className="flex items-start gap-3 text-sm">
                <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0" style={{ backgroundColor: palette.accent + '20', color: palette.accent }}>{a.acheteur?.prenomNom?.[0] ?? '?'}</div>
                <div>
                  <p className="font-semibold">{a.acheteur?.prenomNom} — <span className="text-accent" style={{ color: palette.accent }}>{'★'.repeat(a.note)}</span></p>
                  <p className="text-gray-500">{a.commentaire}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Produits */}
      <h2 className="text-xl font-bold text-primary mb-4" style={{ color: palette.primary }}>Produits ({produits.length})</h2>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {produits.map((p) => (
          <Link key={p.id} to={`/produit/${p.id}`} className="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition group">
            <div className="aspect-square bg-gray-100 overflow-hidden">
              <img src={p.images?.[0] ?? p.imagePrincipale} alt={p.nom} className="w-full h-full object-cover group-hover:scale-105 transition" />
            </div>
            <div className="p-3">
              <h3 className="font-semibold text-sm text-primary line-clamp-1">{p.nom}</h3>
              <span className="font-bold text-sm">{p.prixActuel?.toLocaleString()} FCFA</span>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
