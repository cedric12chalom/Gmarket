import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Package } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import api from '../services/api'

export default function CommandesPage() {
  const { user } = useAuthStore()
  const [commandes, setCommandes] = useState<any[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!user) return
    api.get('/api/commandes/me')
      .then((r) => setCommandes(r.data.commandes))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [user])

  if (!user) return (
    <div className="text-center py-20">
      <p className="text-gray-500 mb-4">Connectez-vous pour voir vos commandes.</p>
      <Link to="/connexion" className="text-accent font-semibold hover:underline">Se connecter →</Link>
    </div>
  )

  const statutColor = (s: string) => {
    if (s === 'livree') return 'bg-green-100 text-green-700'
    if (s === 'expediee') return 'bg-blue-100 text-blue-700'
    if (s === 'annulee') return 'bg-red-100 text-red-700'
    return 'bg-yellow-100 text-yellow-700'
  }

  return (
    <div className="max-w-3xl mx-auto">
      <h1 className="text-3xl font-bold text-primary mb-8">Mes commandes</h1>

      {loading ? (
        <div className="text-center py-12 text-gray-400">Chargement...</div>
      ) : commandes.length === 0 ? (
        <div className="text-center py-20">
          <Package className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-500 mb-4">Vous n'avez pas encore de commande.</p>
          <Link to="/catalogue" className="text-accent font-semibold hover:underline">Explorer le catalogue →</Link>
        </div>
      ) : (
        <div className="space-y-4">
          {commandes.map((c) => (
            <div key={c.id} className="bg-white border border-gray-100 rounded-2xl overflow-hidden">
              {/* En-tête */}
              <div className="flex items-center justify-between p-4 bg-gray-50 border-b border-gray-100">
                <div className="flex items-center gap-3">
                  <span className="font-bold text-sm text-primary">Commande #{c.id}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statutColor(c.statut)}`}>
                    {c.statutLibelle}
                  </span>
                </div>
                <span className="text-xs text-gray-500">{new Date(c.createdAt).toLocaleDateString('fr-FR')}</span>
              </div>

              {/* Lignes */}
              <div className="p-4 space-y-3">
                {c.lignes?.map((l: any) => (
                  <div key={l.id} className="flex items-center gap-3">
                    <img src={l.produit?.imagePrincipale} className="w-12 h-12 rounded-lg object-cover bg-gray-100" />
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-semibold text-primary truncate">{l.produit?.nom}</p>
                      <p className="text-xs text-gray-500">{l.couleur} / {l.taille} × {l.quantite}</p>
                    </div>
                    <span className="text-sm font-medium">{l.sousTotal?.toLocaleString()} FCFA</span>
                  </div>
                ))}
              </div>

              {/* Footer */}
              <div className="flex items-center justify-between p-4 border-t border-gray-100 bg-gray-50/50">
                <div className="text-sm text-gray-500">
                  {c.boutique?.nom && <span>Boutique : <span className="font-medium text-primary">{c.boutique.nom}</span></span>}
                </div>
                <span className="font-bold text-primary">{c.montantTotal?.toLocaleString()} FCFA</span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
