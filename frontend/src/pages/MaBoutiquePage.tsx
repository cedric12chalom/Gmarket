import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Package, TrendingUp, Star, Clock } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import api from '../services/api'

export default function MaBoutiquePage() {
  const { user } = useAuthStore()
  const [produits, setProduits] = useState<any[]>([])
  const [commandes, setCommandes] = useState<any[]>([])

  const isVendeur = user?.roles.includes('ROLE_VENDEUR')
  const maBoutique = user?.boutiques?.[0]

  useEffect(() => {
    if (maBoutique) {
      api.get(`/api/produits?boutique=${maBoutique.id}`).then((r) => setProduits(r.data.produits))
      api.get('/api/commandes/boutique/mes').then((r) => setCommandes(r.data.commandes)).catch(() => {})
    }
  }, [maBoutique])

  if (!user) return <div className="text-center py-20 text-gray-400">Connectez-vous pour accéder à votre boutique.</div>
  if (!isVendeur) return (
    <div className="text-center py-20">
      <p className="text-gray-500 mb-4">Vous n'avez pas encore de boutique.</p>
      <Link to="/inscription" className="text-accent font-semibold hover:underline">Créer un compte vendeur →</Link>
    </div>
  )

  if (!maBoutique) return (
    <div className="text-center py-20">
      <p className="text-gray-500 mb-4">Créez votre boutique pour commencer à vendre.</p>
      <Link to="/creer-boutique" className="inline-block bg-accent text-primary px-6 py-3 rounded-full font-bold hover:bg-yellow-400 transition">
        Créer ma boutique maintenant
      </Link>
    </div>
  )

  const stats = {
    produits: produits.length,
    commandes: commandes.length,
    ventes: commandes.reduce((s: number, c: any) => s + (c.montantTotal ?? 0), 0),
    enCours: commandes.filter((c: any) => c.statut === 'en_attente').length,
  }

  return (
    <div className="space-y-8">
      {/* Header boutique */}
      <div className="flex items-center gap-6">
        <div className="w-16 h-16 rounded-xl flex items-center justify-center text-xl font-bold" style={{ backgroundColor: maBoutique.accentColor + '20', color: maBoutique.accentColor }}>
          {maBoutique.nom[0]}
        </div>
        <div>
          <h1 className="text-2xl font-bold text-primary">{maBoutique.nom}</h1>
          <div className="flex items-center gap-3 text-sm">
            <Link to={`/boutique/${maBoutique.slug}`} className="text-accent hover:underline">Voir ma boutique →</Link>
            <span className="text-gray-400">|</span>
            <Link to="/creer-boutique" className="text-gray-500 hover:text-primary hover:underline">Personnaliser l'apparence</Link>
          </div>
          {maBoutique.customColors && (
            <div className="flex gap-1.5 mt-2">
              {Object.entries(maBoutique.customColors).map(([k, v]) => (
                <span key={k} title={k} className="w-4 h-4 rounded-full border border-gray-200" style={{ backgroundColor: String(v) }} />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { icon: Package, label: 'Produits', value: stats.produits },
          { icon: TrendingUp, label: 'Ventes (FCFA)', value: stats.ventes.toLocaleString() },
          { icon: Clock, label: 'En attente', value: stats.enCours },
          { icon: Star, label: 'Commandes', value: stats.commandes },
        ].map(({ icon: Icon, label, value }) => (
          <div key={label} className="bg-white border border-gray-100 rounded-2xl p-5 text-center">
            <Icon className="w-6 h-6 text-accent mx-auto mb-2" />
            <p className="text-2xl font-bold text-primary">{value}</p>
            <p className="text-xs text-gray-500 mt-1">{label}</p>
          </div>
        ))}
      </div>

      {/* Produits */}
      <section>
        <h2 className="text-xl font-bold text-primary mb-4">Mes produits</h2>
        {produits.length === 0 ? (
          <p className="text-gray-400 text-sm">Aucun produit pour le moment.</p>
        ) : (
          <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">
            {produits.map((p) => (
              <Link key={p.id} to={`/produit/${p.id}`} className="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                <img src={p.images?.[0] ?? p.imagePrincipale} className="w-14 h-14 rounded-xl object-cover bg-gray-100" />
                <div className="flex-1 min-w-0">
                  <p className="font-semibold text-sm text-primary truncate">{p.nom}</p>
                  <p className="text-xs text-gray-500">Stock total : {p.stockTotal}</p>
                </div>
                <span className="font-bold text-sm">{p.prixActuel?.toLocaleString()} FCFA</span>
              </Link>
            ))}
          </div>
        )}
      </section>

      {/* Commandes récentes */}
      <section>
        <h2 className="text-xl font-bold text-primary mb-4">Dernières commandes</h2>
        {commandes.length === 0 ? (
          <p className="text-gray-400 text-sm">Aucune commande pour le moment.</p>
        ) : (
          <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">
            {commandes.slice(0, 10).map((c) => (
              <div key={c.id} className="flex items-center gap-4 p-4">
                <div className="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-xs font-bold text-primary">
                  #{c.id}
                </div>
                <div className="flex-1">
                  <p className="text-sm font-semibold text-primary">{c.acheteur?.prenomNom ?? 'Client'}</p>
                  <p className="text-xs text-gray-500">{c.lignes?.length} article(s)</p>
                </div>
                <span className={`text-xs px-2 py-1 rounded-full font-medium ${
                  c.statut === 'livree' ? 'bg-green-100 text-green-700' :
                  c.statut === 'expediee' ? 'bg-blue-100 text-blue-700' :
                  'bg-yellow-100 text-yellow-700'
                }`}>
                  {c.statutLibelle}
                </span>
                <span className="font-bold text-sm">{c.montantTotal?.toLocaleString()} FCFA</span>
              </div>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}
