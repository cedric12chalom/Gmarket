import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Search, SlidersHorizontal } from 'lucide-react'
import api from '../services/api'

export default function CataloguePage() {
  const [produits, setProduits] = useState<any[]>([])
  const [categories, setCategories] = useState<any[]>([])
  const [catId, setCatId] = useState(0)
  const [q, setQ] = useState('')

  useEffect(() => {
    api.get('/api/categories').then((r) => setCategories(r.data.categories))
  }, [])

  useEffect(() => {
    const params = new URLSearchParams()
    if (catId > 0) params.set('categorie', String(catId))
    api.get(`/api/produits?${params}`).then((r) => setProduits(r.data.produits))
  }, [catId])

  const filtered = q ? produits.filter((p) => p.nom.toLowerCase().includes(q.toLowerCase())) : produits

  return (
    <div className="flex gap-8">
      {/* Sidebar filtres */}
      <aside className="hidden md:block w-56 shrink-0">
        <h3 className="font-bold text-primary mb-4 flex items-center gap-2"><SlidersHorizontal className="w-4 h-4" /> Catégories</h3>
        <button onClick={() => setCatId(0)} className={`block w-full text-left px-3 py-2 rounded-lg text-sm mb-1 transition ${catId === 0 ? 'bg-accent/10 text-primary font-semibold' : 'hover:bg-gray-100 text-gray-600'}`}>
          Toutes
        </button>
        {categories.map((c) => (
          <button key={c.id} onClick={() => setCatId(c.id)} className={`block w-full text-left px-3 py-2 rounded-lg text-sm mb-1 transition ${catId === c.id ? 'bg-accent/10 text-primary font-semibold' : 'hover:bg-gray-100 text-gray-600'}`}>
            {c.nom}
          </button>
        ))}
      </aside>

      {/* Grille produits */}
      <div className="flex-1">
        <div className="flex items-center gap-4 mb-6">
          <div className="flex items-center border rounded-xl px-3 flex-1 max-w-md">
            <Search className="w-4 h-4 text-gray-400" />
            <input value={q} onChange={(e) => setQ(e.target.value)} className="w-full px-3 py-2.5 outline-none text-sm" placeholder="Rechercher un produit..." />
          </div>
          <span className="text-sm text-gray-500">{filtered.length} produit(s)</span>
        </div>

        {/* Mobile categories */}
        <div className="md:hidden flex gap-2 overflow-x-auto pb-4 mb-4">
          <button onClick={() => setCatId(0)} className={`shrink-0 px-4 py-2 rounded-full text-sm border ${catId === 0 ? 'bg-accent border-accent text-primary font-semibold' : 'border-gray-200 text-gray-600'}`}>
            Toutes
          </button>
          {categories.map((c) => (
            <button key={c.id} onClick={() => setCatId(c.id)} className={`shrink-0 px-4 py-2 rounded-full text-sm border ${catId === c.id ? 'bg-accent border-accent text-primary font-semibold' : 'border-gray-200 text-gray-600'}`}>
              {c.nom}
            </button>
          ))}
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {filtered.map((p) => (
            <Link key={p.id} to={`/produit/${p.id}`} className="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition group">
              <div className="aspect-square bg-gray-100 overflow-hidden">
                <img src={p.images?.[0] ?? p.imagePrincipale} alt={p.nom} className="w-full h-full object-cover group-hover:scale-105 transition" />
              </div>
              <div className="p-3">
                <p className="text-xs text-gray-400 mb-1">{p.boutique?.nom}</p>
                <h3 className="font-semibold text-sm text-primary line-clamp-1">{p.nom}</h3>
                <div className="flex items-center gap-2 mt-2">
                  <span className="font-bold text-primary text-sm">{p.prixActuel?.toLocaleString()} FCFA</span>
                  {p.pourcentageReduction > 0 && (
                    <span className="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">-{p.pourcentageReduction}%</span>
                  )}
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  )
}
